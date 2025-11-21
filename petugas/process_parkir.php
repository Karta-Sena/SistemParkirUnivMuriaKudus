<?php
// FILE: petugas/process_parkir.php
header('Content-Type: application/json');
session_start();
// [PENTING] Mundur satu folder
include '../config.php';

$response = ['status' => 'error', 'message' => 'Processing failed.'];

$action = $_POST['action'] ?? '';        
$user_id = (int) ($_POST['user_id'] ?? 0); 
$plat_nomor = $_POST['plat_nomor'] ?? ''; 
$MAX_CAPACITY = 3; // Sesuaikan kapasitas per area

if ($user_id === 0 || empty($action) || empty($plat_nomor)) {
    echo json_encode(['status'=>'error', 'message'=>'Data tidak lengkap.']); exit;
}

// FUNGSI: Cari Area Kosong
function find_empty_area($conn, $max_capacity) {
    $sql = "SELECT ap.kode_area, COUNT(lp.id) AS terisi 
            FROM area_parkir ap 
            LEFT JOIN log_parkir lp ON ap.kode_area = lp.kode_area AND lp.status = 'masuk' 
            GROUP BY ap.kode_area ORDER BY terisi ASC, ap.kode_area ASC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['terisi'] < $max_capacity) return $row['kode_area'];
        }
    }
    return null;
}

// LOGIC MASUK
if ($action === 'masuk') {
    // Cek apakah sudah masuk?
    $cek = $conn->query("SELECT id FROM log_parkir WHERE user_id=$user_id AND status='masuk'");
    if ($cek->num_rows > 0) {
        echo json_encode(['status'=>'error', 'message'=>'User ini sudah tercatat parkir (Masuk).']); exit;
    }

    $kode_area = find_empty_area($conn, $MAX_CAPACITY);
    if (!$kode_area) {
        echo json_encode(['status'=>'error', 'message'=>'PARKIR PENUH!']); exit;
    }

    $stmt = $conn->prepare("INSERT INTO log_parkir (user_id, plat_nomor, kode_area, waktu_masuk, status) VALUES (?, ?, ?, NOW(), 'masuk')");
    $stmt->bind_param('iss', $user_id, $plat_nomor, $kode_area);
    
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success', 'message'=>"Masuk Berhasil! Area: $kode_area"]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal insert DB.']);
    }
}
// LOGIC KELUAR
else if ($action === 'keluar') {
    $stmt = $conn->prepare("UPDATE log_parkir SET waktu_keluar=NOW(), status='keluar' WHERE user_id=? AND status='masuk' AND plat_nomor=?");
    $stmt->bind_param('is', $user_id, $plat_nomor);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status'=>'success', 'message'=>'Keluar Berhasil!']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal catat keluar (Mungkin kendaraan tidak sedang parkir).']);
    }
}
?>