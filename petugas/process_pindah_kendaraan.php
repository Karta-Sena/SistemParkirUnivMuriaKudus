<?php
// FILE: petugas/process_pindah_area.php
session_start();
include '../config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id = (int) $_POST['log_id'];
    $area_baru = $_POST['area_baru'];
    
    $stmt = $conn->prepare("UPDATE log_parkir SET kode_area = ? WHERE id = ?");
    $stmt->bind_param('si', $area_baru, $log_id);
    
    if ($stmt->execute()) {
        $_SESSION['notif_petugas'] = ['type' => 'success', 'text' => "Berhasil pindah ke $area_baru"];
    } else {
        $_SESSION['notif_petugas'] = ['type' => 'error', 'text' => "Gagal pindah area."];
    }
    header('Location: petugas.php');
    exit;
}
?>