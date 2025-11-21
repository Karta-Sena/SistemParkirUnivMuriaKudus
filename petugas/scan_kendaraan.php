<?php
// FILE: scan_kendaraan.php (API Mendapatkan Kendaraan - VERSI FINAL)
header('Content-Type: application/json');
include 'config.php'; 

$response = [
    'success' => false, 
    'vehicles' => [], 
    'active_plat_nomor' => null, 
    'active_status' => 'keluar', 
    'message' => ''
];

$user_id = $_GET['user_id'] ?? null;

if (!is_numeric($user_id)) {
    $response['message'] = 'ID pengguna tidak valid.';
    echo json_encode($response);
    exit;
}

// 1. QUERY KENDARAAN (PLAT, JENIS, WARNA)
$sql_vehicles = "SELECT plat_nomor, jenis, warna FROM kendaraan WHERE user_id = ?";
$stmt_vehicles = $conn->prepare($sql_vehicles);
$stmt_vehicles->bind_param("i", $user_id);
// Pengecekan error di sini akan membantu debugging!
if (!$stmt_vehicles->execute()) {
    $response['message'] = 'Gagal mengambil kendaraan: ' . $stmt_vehicles->error;
    echo json_encode($response); exit;
}
$result_vehicles = $stmt_vehicles->get_result();

$vehicles = [];
while ($row = $result_vehicles->fetch_assoc()) {
    $vehicles[] = $row;
}
$stmt_vehicles->close();


// 2. QUERY STATUS PARKIR AKTIF
// ***PERBAIKAN KRITIS: Ganti 'riwayat_parkir' menjadi 'log_parkir'***
$sql_status = "
    SELECT plat_nomor, status 
    FROM log_parkir 
    WHERE user_id = ? 
    ORDER BY waktu_masuk DESC 
    LIMIT 1";
    
$stmt_status = $conn->prepare($sql_status);
$stmt_status->bind_param("i", $user_id);
if (!$stmt_status->execute()) {
     // Ini akan muncul jika kolom atau tabel di log_parkir salah
    // $response['message'] = 'Gagal mengambil status parkir: ' . $stmt_status->error;
    // echo json_encode($response); exit; 
}
$result_status = $stmt_status->get_result();
$latest_entry = $result_status->fetch_assoc();
$stmt_status->close();


if ($latest_entry) {
    if ($latest_entry['status'] === 'Masuk') { 
        // Catatan: Pastikan status di DB Anda menggunakan huruf besar 'Masuk' atau huruf kecil 'masuk'
        $response['active_plat_nomor'] = $latest_entry['plat_nomor'];
        $response['active_status'] = 'masuk';
    }
}


// 3. FINALISASI RESPONS
if (!empty($vehicles)) {
    $response['success'] = true;
    $response['vehicles'] = $vehicles;
} else {
    $response['message'] = 'Tidak ditemukan kendaraan untuk pengguna ini.';
}

echo json_encode($response);
$conn->close();
?>