<?php
// FILE: petugas/logout.php
session_start();

// Hapus semua data sesi
$_SESSION = [];
session_unset();
session_destroy();

// Redirect KHUSUS ke halaman login petugas
header("Location: dashboard_petugas.php");
exit();
?>