<?php
// FILE: process_pindah_area.php - FINAL V2.0 (KAPASITAS BERBASIS)
session_start();
include 'config.php'; 

// Proteksi akses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header('Location: login_petugas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id = (int) ($_POST['log_id'] ?? 0); 
    $user_id = (int) ($_POST['user_id'] ?? 0); 
    $plat_nomor = $_POST['plat_nomor'] ?? '';
    $area_baru = strtoupper(trim($_POST['area_baru'] ?? '')); 
    $area_lama = strtoupper(trim($_POST['area_lama'] ?? ''));

    if ($log_id <= 0 || $user_id <= 0 || empty($area_baru) || empty($area_lama) || $area_baru === $area_lama) {
        $_SESSION['notif_petugas'] = ['type' => 'error', 'text' => "Data pemindahan tidak valid atau area tujuan sama dengan area asal."];
        header('Location: dashboard_petugas_parkir.php');
        exit;
    }
    
    // =================================================================
    // V2.0: VALIDASI KAPASITAS AREA TUJUAN BARU (MAX 5)
    // =================================================================
    $sql_check_target = "
        SELECT 
            ap.kapasitas_maks,
            COUNT(lp.id) AS active_count
        FROM 
            area_parkir ap
        LEFT JOIN 
            log_parkir lp ON ap.kode_area = lp.kode_area AND lp.status = 'masuk'
        WHERE 
            ap.kode_area = ?
        GROUP BY 
            ap.kapasitas_maks
    ";

    $stmt_check = $conn->prepare($sql_check_target);
    $stmt_check->bind_param('s', $area_baru);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $area_target_data = $result_check->fetch_assoc();
    $stmt_check->close();

    // Cek jika area tujuan penuh atau tidak valid
    if (!$area_target_data) {
        $_SESSION['notif_petugas'] = ['type' => 'error', 'text' => "Area tujuan ($area_baru) tidak terdaftar."];
        header('Location: dashboard_petugas_parkir.php');
        exit;
    }
    
    // Cek jika jumlah kendaraan aktif sudah mencapai atau melebihi kapasitas
    if ($area_target_data['active_count'] >= $area_target_data['kapasitas_maks']) {
        $_SESSION['notif_petugas'] = ['type' => 'error', 'text' => "Gagal: Area tujuan ($area_baru) sudah penuh ({$area_target_data['active_count']} dari {$area_target_data['kapasitas_maks']})."];
        header('Location: dashboard_petugas_parkir.php');
        exit;
    }
    // =================================================================
    
    
    // Lanjutkan dengan Transaksi Database
    $conn->begin_transaction();
    $success = true;

    try {
        // HAPUS SQL 1 & 2 LAMA (Update status area_parkir), karena sekarang dinamis.

        // 1. Update log parkir aktif dengan kode area baru
        $sql3 = "UPDATE log_parkir SET kode_area = ? WHERE id = ? AND status = 'masuk'";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param('si', $area_baru, $log_id);
        if (!$stmt3->execute()) $success = false;
        $stmt3->close();
        
        // 2. KIRIM NOTIFIKASI ke User pemilik kendaraan
        $message = "Kendaraan Anda (Plat $plat_nomor) telah dipindahkan dari Area $area_lama ke **Area $area_baru** oleh Petugas. Lokasi parkir baru Anda telah diperbarui.";
        $sql4 = "INSERT INTO notifications (user_id, message, is_read, type) VALUES (?, ?, 0, 'pindah_area')";
        $stmt4 = $conn->prepare($sql4);
        $stmt4->bind_param('is', $user_id, $message);
        if (!$stmt4->execute()) $success = false;
        $stmt4->close();
        
        if ($success) {
            $conn->commit();
            $_SESSION['notif_petugas'] = ['type' => 'success', 'text' => "Pemindahan kendaraan ke Area $area_baru berhasil!"];
        } else {
            $conn->rollback();
            $_SESSION['notif_petugas'] = ['type' => 'error', 'text' => "Gagal memproses pemindahan area. Transaksi dibatalkan."];
        }

    } catch (\Exception $e) {
        $conn->rollback();
        $_SESSION['notif_petugas'] = ['type' => 'error', 'text' => "Kesalahan: " . $e->getMessage()];
    }
    
    header('Location: dashboard_petugas_parkir.php');
    exit;
}
?>