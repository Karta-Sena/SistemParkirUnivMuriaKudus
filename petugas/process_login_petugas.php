<?php
// FILE: petugas/process_login_petugas.php
session_start();
include '../config.php'; 

$username_input = $_POST['username'] ?? '';
$password_input = $_POST['password'] ?? '';

// 1. Validasi Input Kosong
if (empty($username_input) || empty($password_input)) {
    $_SESSION['login_error'] = 'ID Petugas wajib diisi.';
    header('Location: login_petugas.php');
    exit;
}

// 2. ATURAN KHUSUS: ID dan Password harus SAMA PERSIS
// Ini untuk memastikan petugas "scan" atau ketik ID yang sama
if ($username_input !== $password_input) {
    $_SESSION['login_error'] = 'Untuk keamanan cepat, Password harus sama dengan ID Petugas.';
    header('Location: login_petugas.php');
    exit;
}

// 3. Query Khusus Petugas
// Kita mencari user dimana email (sebagai ID) cocok DAN role-nya WAJIB 'petugas'
// Akun 'mahasiswa' atau 'dosen' tidak akan ditemukan oleh query ini
$sql = "SELECT id, nama, password, role FROM users WHERE email = ? AND role = 'petugas' LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['login_error'] = 'Kesalahan sistem database.';
    header('Location: login_petugas.php');
    exit;
}

$stmt->bind_param("s", $username_input);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $nama, $hashed_password, $role_db);
    $stmt->fetch();

    // 4. Verifikasi Password (Hashing Tetap Digunakan demi Keamanan Data)
    if (password_verify($password_input, $hashed_password)) {
        // LOGIN SUKSES
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['nama']    = $nama;
        $_SESSION['role']    = $role_db;
        
        header('Location: dashboard_petugas.php');
        exit;
    }
}

// LOGIN GAGAL
$_SESSION['login_error'] = 'ID Petugas tidak terdaftar atau salah.';
$stmt->close();
$conn->close();
header('Location: login_petugas.php');
exit;
?>