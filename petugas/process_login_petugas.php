<?php
// FILE: process_login_petugas.php (FINAL)
session_start();

// Hapus baris ini setelah debugging selesai:
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

include 'config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = 'petugas';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Username/Email dan password wajib diisi.';
    header('Location: login_petugas.php');
    exit;
}

// Cari user di tabel 'users'
$sql = "SELECT id, nama, password, role FROM users WHERE (email = ? OR nim = ? OR nidn = ?) AND role = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Fatal error saat prepare query
    $_SESSION['login_error'] = 'Kesalahan sistem (DB Query error).';
    $conn->close();
    header('Location: login_petugas.php');
    exit;
}

$stmt->bind_param("ssss", $username, $username, $username, $role);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $nama, $hashed_password, $role_db);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        // Login Sukses
        $_SESSION['user_id'] = $user_id;
        $_SESSION['nama'] = $nama;
        $_SESSION['role'] = $role_db;
        
        // Tutup koneksi dan redirect ke dashboard
        $stmt->close();
        $conn->close();
        header('Location: dashboard_petugas_parkir.php');
        exit;
    }
}

// Login Gagal (Password salah atau Akun tidak ditemukan)
$_SESSION['login_error'] = 'Kredensial Petugas tidak valid.';

if (isset($stmt)) $stmt->close();
if (isset($conn) && $conn->ping()) {
    $conn->close();
}

header('Location: login_petugas.php');
exit;
?>