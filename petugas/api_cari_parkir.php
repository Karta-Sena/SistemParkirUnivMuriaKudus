<?php
// FILE: petugas/api_cari_parkir.php
// API Sederhana untuk mengisi data dashboard dan pencarian
header('Content-Type: application/json');
session_start();
include '../config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

// Ambil input pencarian
$q = isset($_GET['q']) ? $_GET['q'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : ''; // masuk/keluar

// Query dasar
$sql = "SELECT 
            lp.plat_nomor, 
            lp.kode_area, 
            lp.status, 
            lp.waktu_masuk,
            u.nama as pemilik,
            k.jenis
        FROM log_parkir lp
        LEFT JOIN users u ON lp.user_id = u.id
        LEFT JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
        WHERE 1=1";

// Jika ada pencarian ketikan
if (!empty($q)) {
    $sql .= " AND (lp.plat_nomor LIKE '%$q%' OR u.nama LIKE '%$q%')";
}

// Jika filter status (misal: tombol 'Sedang Parkir' diklik)
if (!empty($status)) {
    $sql .= " AND lp.status = '$status'";
}

$sql .= " ORDER BY lp.waktu_masuk DESC LIMIT 20";

$result = $conn->query($sql);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'plat' => $row['plat_nomor'],
            'pemilik' => $row['pemilik'] ?? 'Tamu',
            'jenis' => $row['jenis'] ?? 'Umum',
            'slot' => $row['kode_area'] ?? '-',
            'status' => $row['status'],
            'waktu' => date('H:i', strtotime($row['waktu_masuk']))
        ];
    }
}

echo json_encode(['status' => 'success', 'data' => $data]);
?>