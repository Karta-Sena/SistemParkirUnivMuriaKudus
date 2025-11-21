<?php
// FILE: check_parking_status.php (FINAL STABIL + DATA LENGKAP)
header('Content-Type: application/json');
session_start();
// Pastikan file config.php sudah diatur zona waktunya (date_default_timezone_set('Asia/Jakarta');)
include 'config.php'; 

$user_id = (int) ($_SESSION['user_id'] ?? 0);

$last_known_time_raw = $_POST['last_time'] ?? ''; 

if (empty($last_known_time_raw) || $last_known_time_raw === 'null' || $last_known_time_raw === '0000-00-00 00:00:00') {
    $last_known_time = '0000-00-00 00:00:00';
    $is_initial_load = true; 
} else {
    $last_known_time = $last_known_time_raw;
    $is_initial_load = false; 
}

$response = [
    'status' => 'waiting',
    'message' => 'Menunggu proses pencatatan oleh petugas.',
    'last_time' => null,
    'kode_area' => null, // Tambahkan field ini
    'waktu_masuk' => null // Tambahkan field ini
];

if ($user_id > 0 && isset($conn) && !($conn instanceof mysqli && $conn->connect_error)) {
    
    // *PERBAIKAN: Tambahkan waktu_masuk ke query agar JS bisa menampilkan "Sejak:"*
    $sql = "SELECT 
                status, kode_area, waktu_masuk, 
                GREATEST(COALESCE(waktu_masuk, '0000-00-00 00:00:00'), COALESCE(waktu_keluar, '0000-00-00 00:00:00')) AS last_time_db
            FROM log_parkir 
            WHERE user_id = ? 
            ORDER BY last_time_db DESC 
            LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $log = $result->fetch_assoc();
        $stmt->close();
        
        if ($log) {
            $last_time_db = $log['last_time_db'];
            $current_status = $log['status'];
            $response['last_time'] = $last_time_db; 

            // =========================================================
            // LOGIKA PENTING: Mencegah Redirect Loop dan Memperbaiki Catat Masuk
            // =========================================================

            // KONDISI 1: TRANSAKSI BARU TELAH TERJADI (Waktu DB lebih baru dari browser)
            if ($last_time_db > $last_known_time) {
                
                // Pengecualian Redirect Loop: Jika INI ADALAH LOAD PERTAMA DAN STATUS LOG TERAKHIR ADALAH 'KELUAR'
                if ($is_initial_load && $current_status === 'keluar') {
                    // Biarkan status default 'waiting' yang terpakai.
                } 
                // Pengecualian Parkir Aktif Lama: Jika INI ADALAH LOAD PERTAMA DAN STATUS LOG TERAKHIR ADALAH 'MASUK'
                else if ($is_initial_load && $current_status === 'masuk') {
                     // Perlakukan sebagai Parkir Aktif Lama, JANGAN redirect
                    $response['status'] = 'parked_active'; 
                    $response['kode_area'] = $log['kode_area']; 
                    $response['waktu_masuk'] = $log['waktu_masuk']; // TAMBAHAN DATA
                }
                // Semua Kasus Lain dengan Waktu Baru (new_transaction / notifikasi pop-up)
                else {
                    $response['status'] = 'new_transaction';
                    $response['type'] = $current_status; 
                    $response['kode_area'] = $log['kode_area'];
                    $response['waktu_masuk'] = $log['waktu_masuk']; // TAMBAHAN DATA
                }
            } 
            
            // KONDISI 2: PARKIR AKTIF (Waktu sama/sudah diketahui, tapi status 'masuk')
            else if ($current_status === 'masuk') {
                $response['status'] = 'parked_active'; 
                $response['kode_area'] = $log['kode_area']; 
                $response['waktu_masuk'] = $log['waktu_masuk']; // TAMBAHAN DATA
            }
            
            // KONDISI 3: WAITING (Status 'keluar' atau tidak ada log) 
            // Default response ('waiting') akan digunakan
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = "Database query failed: " . $conn->error;
    }
}
echo json_encode($response);
?>