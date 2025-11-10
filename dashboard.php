<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];

switch ($role) {
    case 'mahasiswa':
    case 'dosen':
    case 'tamu':
        header('Location: dashboard_user.php');
        break;
    
    case 'admin':
        header('Location: dashboard_admin.php');
        break;
    case 'petugas':
        header('Location: dashboard_petugas.php');
        break;
    
    default:
        session_destroy();
        header('Location: login.php');
        break;
}
exit;
?>