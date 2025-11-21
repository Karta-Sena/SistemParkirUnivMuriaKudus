<?php
// FILE: process_kendaraan.php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
include 'config.php';

// Fungsi untuk mengirim respons JSON dan menghentikan eksekusi
function sendJson($status, $message, $conn) {
    ob_end_clean(); 
    
    if ($conn && $conn->ping()) {
        $conn->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Keamanan: Cek sesi
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$user_id || !in_array($role, ['mahasiswa', 'dosen', 'tamu'])) { // Tambah 'tamu' untuk keamanan
    sendJson('error', 'Sesi tidak valid. Silakan login ulang.', $conn);
}

$action = $_REQUEST['action'] ?? '';

// --- LOGIKA UTAMA (POST) untuk Tambah/Edit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $plat_nomor = trim($_POST['plat_nomor'] ?? '');
    $no_stnk = trim($_POST['no_stnk'] ?? '');
    $jenis = $_POST['jenis'] ?? '';
    $warna = trim($_POST['warna'] ?? '');

    if (empty($plat_nomor) || empty($no_stnk) || empty($jenis) || empty($warna)) {
        sendJson('error', 'Semua data wajib diisi.', $conn);
    }
    
    // Validasi Duplikasi STNK/Plat
    $sql_cek = "SELECT id FROM kendaraan WHERE (plat_nomor = ? OR no_stnk = ?) AND user_id = ?";
    $params_cek = "ssi";
    $values_cek = [$plat_nomor, $no_stnk, $user_id];
    
    if ($action === 'update' && $id) {
        $sql_cek .= " AND id != ?";
        $params_cek .= "i";
        $values_cek[] = $id;
    }

    $stmt_cek = $conn->prepare($sql_cek);
    if (!$stmt_cek) { sendJson('error', 'Kesalahan SQL Prep Cek Duplikasi: ' . $conn->error, $conn); }
    $stmt_cek->bind_param($params_cek, ...$values_cek);
    $stmt_cek->execute();
    $stmt_cek->store_result();
    
    if ($stmt_cek->num_rows > 0) {
        sendJson('error', 'Plat atau Nomor STNK sudah terdaftar pada akun Anda.', $conn);
    }
    $stmt_cek->close();

    if ($action === 'add') {
        $sql = "INSERT INTO kendaraan (user_id, plat_nomor, no_stnk, jenis, warna) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $plat_nomor, $no_stnk, $jenis, $warna);
        
        if ($stmt->execute()) {
            sendJson('success', 'Kendaraan berhasil ditambahkan!', $conn);
        } else {
            sendJson('error', 'Gagal tambah data. Error: ' . $stmt->error, $conn);
        }
        $stmt->close();

    } elseif ($action === 'update' && $id) {
        $sql = "UPDATE kendaraan SET plat_nomor = ?, no_stnk = ?, jenis = ?, warna = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $plat_nomor, $no_stnk, $jenis, $warna, $id, $user_id);
        
        if ($stmt->execute()) {
            sendJson('success', 'Kendaraan berhasil diperbarui!', $conn);
        } else {
            sendJson('error', 'Gagal update data. Error: ' . $stmt->error, $conn);
        }
        $stmt->close();
    } else {
         sendJson('error', 'Aksi POST tidak valid atau ID hilang.', $conn);
    }

// --- LOGIKA HAPUS (GET) ---
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "DELETE FROM kendaraan WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        sendJson('success', 'Kendaraan berhasil dihapus.', $conn);
    } else {
        sendJson('error', 'Gagal hapus data. Error: ' . $stmt->error, $conn);
    }
    $stmt->close();

} else {
    sendJson('error', 'Permintaan tidak valid.', $conn);
}
?>