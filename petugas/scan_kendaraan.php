<?php
// FILE: petugas/scan_kendaraan.php
header('Content-Type: application/json');
// [PENTING] Mundur satu folder untuk config
include '../config.php'; 

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

// 1. QUERY KENDARAAN
$sql_vehicles = "SELECT plat_nomor, jenis, warna FROM kendaraan WHERE user_id = ?";
$stmt_vehicles = $conn->prepare($sql_vehicles);
$stmt_vehicles->bind_param("i", $user_id);
if (!$stmt_vehicles->execute()) {
    $response['message'] = 'Gagal mengambil kendaraan.';
    echo json_encode($response); exit;
}
$result_vehicles = $stmt_vehicles->get_result();
while ($row = $result_vehicles->fetch_assoc()) {
    $vehicles[] = $row;
}
$stmt_vehicles->close();

// 2. QUERY STATUS PARKIR AKTIF
$sql_status = "SELECT plat_nomor, status FROM log_parkir WHERE user_id = ? ORDER BY waktu_masuk DESC LIMIT 1";
$stmt_status = $conn->prepare($sql_status);
$stmt_status->bind_param("i", $user_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result();
$latest_entry = $result_status->fetch_assoc();
$stmt_status->close();

if ($latest_entry && strtolower($latest_entry['status']) === 'masuk') { 
    $response['active_plat_nomor'] = $latest_entry['plat_nomor'];
    $response['active_status'] = 'masuk';
}

if (!empty($vehicles)) {
    $response['success'] = true;
    $response['vehicles'] = $vehicles;
} else {
    $response['message'] = 'Tidak ditemukan kendaraan.';
}

echo json_encode($response);
$conn->close();
?>