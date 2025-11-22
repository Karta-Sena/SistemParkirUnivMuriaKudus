<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// PENTING: Ubah localhost menjadi 127.0.0.1 untuk memaksa koneksi TCP/IP pada port 3307
$host = "127.0.0.1"; 
$user = "root";     
$pass = "";         
$db   = "umkparkingsystem"; 
$port = 3307;       

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Koneksi Database Gagal: " . $e->getMessage()); // Menangkap error agar tampilan lebih rapi (opsional)
}
?>