<?php
// FILE: process_parkir.php
// REVISI: Menangani Input Slot Manual & Update Status Slot Database

header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. Include Config (Naik 1 level jika file ini ada di folder petugas/)
// Sesuaikan path ini dengan struktur folder Anda
$configPath = '../config.php'; 
if (!file_exists($configPath)) {
    $configPath = 'config.php'; // Coba path root jika gagal
}

if (file_exists($configPath)) {
    include_once $configPath;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Config database tidak ditemukan.']);
    exit;
}

// 2. Ambil Data Input
$user_id    = $_POST['user_id'] ?? null;
$plat_nomor = $_POST['plat_nomor'] ?? null;
$action     = $_POST['action'] ?? null; // 'masuk' atau 'keluar'
$kode_slot  = $_POST['kode_area'] ?? null; // Ini adalah kode_slot (misal: A-01)

// Validasi Dasar
if (!$user_id || !$plat_nomor || !$action) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit;
}

// 3. LOGIKA MASUK
if ($action === 'masuk') {
    
    if (empty($kode_slot)) {
        echo json_encode(['status' => 'error', 'message' => 'Lokasi parkir wajib dipilih!']);
        exit;
    }

    // Cek apakah user sudah parkir (mencegah double scan)
    $cek_aktif = $conn->query("SELECT id FROM log_parkir WHERE user_id = '$user_id' AND status = 'masuk'");
    if ($cek_aktif->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'User ini sudah tercatat sedang parkir.']);
        exit;
    }

    // Cari ID Slot & Area berdasarkan Kode Slot (misal A-01)
    $sql_slot = "SELECT id, area_id, status FROM slot_parkir WHERE kode_slot = ?";
    $stmt_slot = $conn->prepare($sql_slot);
    $stmt_slot->bind_param("s", $kode_slot);
    $stmt_slot->execute();
    $res_slot = $stmt_slot->get_result()->fetch_assoc();
    $stmt_slot->close();

    if (!$res_slot) {
        echo json_encode(['status' => 'error', 'message' => 'Slot parkir tidak valid/ditemukan di database.']);
        exit;
    }

    if ($res_slot['status'] === 'terisi') {
        echo json_encode(['status' => 'error', 'message' => "Slot $kode_slot sudah terisi! Pilih slot lain."]);
        exit;
    }

    $slot_id = $res_slot['id'];
    $area_id = $res_slot['area_id'];

    // Mulai Transaksi Database
    $conn->begin_transaction();

    try {
        // A. Insert ke Log Parkir
        $sql_insert = "INSERT INTO log_parkir (user_id, plat_nomor, area_id, slot_id, kode_area, waktu_masuk, status) 
                       VALUES (?, ?, ?, ?, ?, NOW(), 'masuk')";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("isiis", $user_id, $plat_nomor, $area_id, $slot_id, $kode_slot);
        $stmt->execute();
        $stmt->close();

        // B. Update Status Slot jadi 'terisi'
        $conn->query("UPDATE slot_parkir SET status = 'terisi' WHERE id = $slot_id");

        // C. Update Status User jadi 'masuk'
        $conn->query("UPDATE users SET status_parkir = 'masuk' WHERE id = $user_id");

        // D. Kirim Notifikasi
        $msg = "Kendaraan $plat_nomor berhasil parkir di Slot $kode_slot.";
        $conn->query("INSERT INTO notifications (user_id, message, created_at) VALUES ($user_id, '$msg', NOW())");

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => "Berhasil Masuk ke $kode_slot"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()]);
    }
}

// 4. LOGIKA KELUAR
elseif ($action === 'keluar') {

    // Cari data parkir aktif terakhir
    $sql_find = "SELECT id, slot_id FROM log_parkir 
                 WHERE user_id = ? AND status = 'masuk' 
                 ORDER BY id DESC LIMIT 1";
    
    $stmt = $conn->prepare($sql_find);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        echo json_encode(['status' => 'error', 'message' => 'Kendaraan tidak sedang parkir (Sudah keluar atau belum masuk).']);
        exit;
    }

    $log_id = $res['id'];
    $slot_id_used = $res['slot_id'];

    $conn->begin_transaction();

    try {
        // A. Update Log Parkir (Waktu Keluar & Status)
        $conn->query("UPDATE log_parkir SET waktu_keluar = NOW(), status = 'keluar' WHERE id = $log_id");

        // B. Update Slot jadi 'tersedia' kembali (Jika slot ID ada)
        if ($slot_id_used) {
            $conn->query("UPDATE slot_parkir SET status = 'tersedia' WHERE id = $slot_id_used");
        }

        // C. Update Status User jadi 'keluar'
        $conn->query("UPDATE users SET status_parkir = 'keluar' WHERE id = $user_id");

        // D. Notifikasi
        $msg = "Kendaraan $plat_nomor telah keluar. Terima kasih.";
        $conn->query("INSERT INTO notifications (user_id, message, created_at) VALUES ($user_id, '$msg', NOW())");

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Berhasil Keluar. Slot telah dikosongkan.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal proses keluar: ' . $e->getMessage()]);
    }
}
?>