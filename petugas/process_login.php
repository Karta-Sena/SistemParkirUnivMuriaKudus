<?php
// FILE: process_login.php
session_start();
include 'config.php'; // Termasuk koneksi $conn (Objek mysqli)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_petugas.php");
    exit();
}

$login_id = $_POST['login_id'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login_id) || empty($password)) {
    $_SESSION['login_error'] = "Username/Email dan password harus diisi.";
    header("Location: login_petugas.php");
    exit();
}

// 1. Amankan input
// KOREKSI: Menggunakan metode OOP dari objek koneksi
$login_id_safe = $conn->real_escape_string($login_id);

// 2. Query ke database
$sql = "SELECT username, password, nama FROM petugas_parkir 
        WHERE username = '$login_id_safe' OR email = '$login_id_safe'";
        
// KOREKSI UTAMA: Ganti mysqli_query() menjadi $conn->query()
$result = $conn->query($sql);

// KOREKSI: Pengecekan menggunakan properti OOP ($result->num_rows)
if ($result && $result->num_rows === 1) { 
    // KOREKSI: Mengambil data menggunakan metode OOP
    $user = $result->fetch_assoc(); 

    // 3. Verifikasi Password
    // ASUMSI: Password disimpan di DB dalam bentuk MD5 (hash)
    if (md5($password) === $user['password']) {
        // Jika login berhasil
        $_SESSION['login_status'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama'];
        
        header("Location: dashboard_petugas_parkir.php");
        exit();
        
    } else {
        $_SESSION['login_error'] = "Password yang Anda masukkan salah.";
    }
} else {
    $_SESSION['login_error'] = "Username atau Email tidak terdaftar.";
}

// Jika ada kegagalan, kembali ke halaman login
header("Location: login_petugas.php");
exit();
?>