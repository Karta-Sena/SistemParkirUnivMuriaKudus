<?php
// FILE: register_petugas_tool.php
// Tools Sederhana untuk Mendaftarkan Akun Petugas Baru (Hash Generator)
// PENTING: Hapus atau amankan file ini setelah digunakan!

session_start();
include '../config.php'; // Sesuaikan path ini jika file ada di folder 'petugas' (misal: include '../config.php';)

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $id_petugas = trim($_POST['id_petugas']); // Ini akan jadi email & password
    $role = 'petugas';

    if (!empty($nama) && !empty($id_petugas)) {
        
        // 1. Cek apakah ID sudah ada?
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $id_petugas);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $message = "<div style='color:red;'>Error: ID Petugas '$id_petugas' sudah terdaftar!</div>";
        } else {
            // 2. HASH PASSWORD
            // Sesuai logika login Anda: Password = ID Petugas
            $password_hash = password_hash($id_petugas, PASSWORD_DEFAULT);

            // 3. INSERT KE DATABASE
            // Kolom 'email' kita isi dengan ID Petugas
            $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ssss", $nama, $id_petugas, $password_hash, $role);
                
                if ($stmt->execute()) {
                    $message = "<div style='color:green; font-weight:bold;'>
                                    Sukses! Petugas '$nama' berhasil didaftarkan.<br>
                                    ID Login: $id_petugas<br>
                                    (Gunakan ID ini untuk login)
                                </div>";
                } else {
                    $message = "<div style='color:red;'>Gagal Insert: " . $conn->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div style='color:red;'>Database Error: " . $conn->error . "</div>";
            }
        }
        $check->close();
    } else {
        $message = "<div style='color:red;'>Semua field wajib diisi!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tools Daftar Petugas</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; background: #f0f2f5; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 400px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #218838; }
        .note { font-size: 0.85rem; color: #666; margin-top: 15px; background: #fff3cd; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Registrasi Petugas Baru</h2>
    <?= $message ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Nama Petugas</label>
            <input type="text" name="nama" placeholder="Misal: Budi Santoso" required>
        </div>
        
        <div class="form-group">
            <label>ID Petugas (Username)</label>
            <input type="text" name="id_petugas" placeholder="Misal: petugas1" required>
        </div>

        <button type="submit">Daftarkan Petugas</button>
    </form>

    <div class="note">
        <strong>Catatan Sistem:</strong><br>
        Password akan otomatis diset sama dengan ID Petugas dan di-enkripsi (Hash) agar sesuai dengan sistem login.
    </div>
</div>

</body>
</html>