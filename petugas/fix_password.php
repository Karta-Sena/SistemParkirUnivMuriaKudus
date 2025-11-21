<?php
// Panggil koneksi database
include '../config.php';

// Password yang diinginkan
$password_baru = 'petugas';

// Enkripsi password menggunakan algoritma bawaan PHP (Bcrypt)
$password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

// Update database user petugas (ID 23 berdasarkan screenshot Anda)
// Kita update berdasarkan email='petugas'
$sql = "UPDATE users SET password = ? WHERE role = 'petugas' AND email = 'petugas'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $password_hash);

if ($stmt->execute()) {
    echo "<h1>✅ SUKSES!</h1>";
    echo "<p>Password untuk ID <b>petugas</b> berhasil diperbaiki.</p>";
    echo "<p>Hash baru di database: " . $password_hash . "</p>";
    echo "<br><a href='login_petugas.php'>Klik disini untuk Login</a>";
} else {
    echo "<h1>❌ GAGAL</h1>";
    echo "Error: " . $conn->error;
}
?>