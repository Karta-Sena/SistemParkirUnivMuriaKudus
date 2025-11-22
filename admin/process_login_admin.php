<?php
// FILE: process_login_admin.php (FINAL DAN KOREKSI KOLOM DATABASE)

session_start();
// Pastikan file config.php tersedia
include '../config.php'; 

// === 1. PENANGANAN EROR KONEKSI DATABASE ===
if (!isset($conn) || $conn->connect_error) {
    error_log("Database Connection Failed (Admin Login): " . ($conn->connect_error ?? 'Koneksi $conn tidak terdefinisi.')); 
    $_SESSION['login_error'] = 'Koneksi database gagal. Silakan coba lagi nanti.';
    header('Location: login_admin.php');
    exit;
}
// ===========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input dari form. Input form bernama 'username' akan kita cari di kolom 'email'.
    $login_input = $_POST['username'] ?? ''; 
    $password_input = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password_input)) {
        $_SESSION['login_error'] = 'Email/ID dan kata sandi wajib diisi.';
        header('Location: login_admin.php');
        exit;
    }

    // QUERY: Mencari user berdasarkan kolom 'email' dan memastikan 'role' adalah 'admin'
    $sql = "SELECT id, email, password, nama, role 
            FROM users 
            WHERE email = ? 
            AND role = 'admin'";
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Admin Login Query Prepare failed: " . $conn->error);
        $_SESSION['login_error'] = 'Terjadi kesalahan internal sistem (Query Error).'; 
        header('Location: login_admin.php');
        exit;
    }

    $stmt->bind_param("s", $login_input); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password yang sudah di-hash
        if (password_verify($password_input, $user['password'])) {
            
            // Login Berhasil!
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['email']; // Gunakan email sebagai penanda sesi 'username'
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role']; 

            unset($_SESSION['login_error']);
            $stmt->close();
            
            // Arahkan ke dashboard admin
            header('Location: dashboard_admin.php'); 
            exit;

        } else {
            // Password salah
            $_SESSION['login_error'] = 'Kata sandi salah.';
            $stmt->close();
            header('Location: login_admin.php');
            exit;
        }

    } else {
        // User tidak ditemukan atau role bukan admin
        $_SESSION['login_error'] = 'Admin tidak terdaftar atau kredensial salah.';
        $stmt->close();
        header('Location: login_admin.php');
        exit;
    }
    
} else {
    // Jika diakses tanpa POST
    header('Location: login_admin.php');
    exit;
}


?>