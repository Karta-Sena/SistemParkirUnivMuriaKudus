<?php
// FILE: check_status.php
// REVISI: Menggunakan tabel 'log_parkir' agar konsisten dengan qrcode.php

header('Content-Type: application/json; charset=utf-8');

// 1. Mulai session
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 2. Koneksi Database
$cfg = __DIR__ . '/config.php';
if (!file_exists($cfg)) {
    echo json_encode(['success' => false, 'message' => 'config.php tidak ditemukan.']);
    exit;
}
require_once $cfg;

// 3. Tentukan User ID (Prioritas: GET -> Session)
$user_id = null;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int) $_GET['user_id'];
} elseif (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak ditemukan.']);
    exit;
}

// 4. Validasi Koneksi
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

try {
    // --- LOGIKA UTAMA (DIPERBARUI) ---
    // Kita ambil status dari 'log_parkir' urutan terakhir (terbaru).
    // Ini memastikan data SAMA PERSIS dengan tampilan awal qrcode.php.
    
    $query = "SELECT status FROM log_parkir WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    // Jika tidak ada history log sama sekali, anggap user sedang 'keluar' (di luar area)
    $raw_status = $row ? $row['status'] : 'keluar';

    // 5. Normalisasi Data (Huruf kecil & Trim)
    $clean_status = strtolower(trim((string)$raw_status));
    
    // Mapping berbagai kemungkinan input DB ke standard 'masuk'/'keluar'
    // Sesuaikan jika di DB Anda pakai 'IN'/'OUT' atau '1'/'0'
    if ($clean_status === 'masuk' || $clean_status === 'in' || $clean_status === '1') {
        $final_status = 'masuk';
    } else {
        $final_status = 'keluar';
    }

    // 6. Kirim Response JSON
    echo json_encode([
        'success' => true, 
        'status_parkir' => $final_status,
        'source' => 'log_parkir' // Debug info (opsional)
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>