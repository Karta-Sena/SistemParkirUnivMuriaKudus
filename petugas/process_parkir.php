<?php
// FILE: process_parkir.php (FINAL - Logik Area Parkir dan Waktu + Notifikasi)
header('Content-Type: application/json');
session_start();
include 'config.php'; // Pastikan file koneksi database Anda di-include

$response = [
    'status' => 'error',
    'message' => 'Processing failed (Default/No Logic Run).',
];

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = "Koneksi database GAGAL.";
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';        
$user_id = (int) ($_POST['user_id'] ?? 0); 
$plat_nomor = $_POST['plat_nomor'] ?? ''; 
$MAX_CAPACITY = 3; 

if ($user_id === 0 || empty($action) || empty($plat_nomor)) {
    $response['message'] = "Data tidak lengkap atau ID Pengguna tidak valid.";
    echo json_encode($response);
    exit;
}

// =======================================================================
// FUNGSI UTILITY: Cari Area Kosong
// =======================================================================
function find_empty_area($conn, $max_capacity) {
    // Fungsi ini sama persis dengan yang Anda buat
    $sql = "SELECT 
                ap.kode_area, 
                COUNT(lp.kode_area) AS jumlah_terisi
            FROM area_parkir ap
            LEFT JOIN log_parkir lp ON ap.kode_area = lp.kode_area AND lp.status = 'masuk' AND lp.waktu_keluar IS NULL
            GROUP BY ap.kode_area
            ORDER BY jumlah_terisi ASC, ap.kode_area ASC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['jumlah_terisi'] < $max_capacity) {
                return $row['kode_area'];
            }
        }
    }
    return null; 
}

// =======================================================================
// FUNGSI UTILITY: Tambah Notifikasi ke DB
// =======================================================================
function add_notification($conn, $user_id, $message) {
    // is_read = 0 (Belum dibaca)
    $sql = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('is', $user_id, $message);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}

// =======================================================================
// LOGIC UTAMA: CATAT MASUK
// =======================================================================
if ($action === 'masuk') {
    $sql_check = "SELECT id, kode_area FROM log_parkir 
                  WHERE user_id = ? AND status = 'masuk' AND waktu_keluar IS NULL
                  ORDER BY waktu_masuk DESC LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param('i', $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $data = $result_check->fetch_assoc();
        $response['message'] = "GAGAL: Pengguna ini sudah tercatat parkir di Area " . htmlspecialchars($data['kode_area']) . ".";
        $stmt_check->close();
        echo json_encode($response); exit;
    }
    $stmt_check->close();
    
    $kode_area = find_empty_area($conn, $MAX_CAPACITY);
    
    if ($kode_area === null) {
        $response['message'] = "GAGAL: SEMUA AREA PARKIR PENUH! Maksimal $MAX_CAPACITY kendaraan per area.";
        echo json_encode($response); exit;
    }

    $sql_insert = "INSERT INTO log_parkir (user_id, plat_nomor, kode_area, waktu_masuk, status) 
                   VALUES (?, ?, ?, NOW(), 'masuk')";
    
    if (!($stmt_insert = $conn->prepare($sql_insert))) {
        $response['message'] = "Prepare Statement Insert GAGAL: " . $conn->error;
        echo json_encode($response); exit;
    }

    $stmt_insert->bind_param('iss', $user_id, $plat_nomor, $kode_area); 

    if ($stmt_insert->execute()) {
        $response['status'] = 'success'; 
        $response['message'] = "Parkir MASUK berhasil dicatat! Diberikan Area: " . htmlspecialchars($kode_area);
        $response['kode_area'] = $kode_area; 
        
        // **[PENAMBAHAN NOTIFIKASI]**
        $message_notif = "Kendaraan Anda ({$plat_nomor}) berhasil dicatat MASUK di Area {$kode_area}.";
        add_notification($conn, $user_id, $message_notif);
        // ************************

    } else {
        $response['message'] = "Gagal mencatat masuk (Execute): " . $stmt_insert->error;
    }
    $stmt_insert->close();
}

// =======================================================================
// LOGIC UTAMA: CATAT KELUAR
// =======================================================================
else if ($action === 'keluar') {
    // Ambil kode area sebelum di-UPDATE
    $area_q = $conn->prepare("SELECT kode_area FROM log_parkir WHERE user_id = ? AND status = 'masuk' AND waktu_keluar IS NULL LIMIT 1");
    $area_q->bind_param('i', $user_id);
    $area_q->execute();
    $area_res = $area_q->get_result();
    $area_data = $area_res->fetch_assoc();
    $kode_area_keluar = $area_data['kode_area'] ?? 'N/A';
    $area_q->close();

    $sql_update = "UPDATE log_parkir 
                   SET waktu_keluar = NOW(), status = 'keluar'
                   WHERE user_id = ? AND status = 'masuk' AND plat_nomor = ? AND waktu_keluar IS NULL
                   ORDER BY waktu_masuk DESC 
                   LIMIT 1";
    
    if (!($stmt_update = $conn->prepare($sql_update))) {
        $response['message'] = "Prepare Statement Update KELUAR GAGAL: " . $conn->error;
        echo json_encode($response); exit;
    }

    $stmt_update->bind_param('is', $user_id, $plat_nomor); 

    if ($stmt_update->execute()) {
        if ($stmt_update->affected_rows > 0) {
            $response['status'] = 'success'; 
            $response['message'] = "Parkir KELUAR berhasil dicatat! Area " . htmlspecialchars($kode_area_keluar) . " dikosongkan.";
            $response['kode_area'] = $kode_area_keluar;
            
            // **[PENAMBAHAN NOTIFIKASI]**
            $message_notif = "Kendaraan Anda ({$plat_nomor}) berhasil dicatat KELUAR. Sampai jumpa kembali!";
            add_notification($conn, $user_id, $message_notif);
            // ************************
            
        } else {
            $response['message'] = "GAGAL: Tidak ada sesi parkir aktif (status 'masuk') yang ditemukan untuk Plat " . htmlspecialchars($plat_nomor) . ".";
        }
    } else {
        $response['message'] = "Gagal mencatat keluar (Execute): " . $stmt_update->error;
    }
    $stmt_update->close();
}

echo json_encode($response);
?>