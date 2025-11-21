<?php
// check_status.php (robust) - menerima ?user_id=123 atau fallback ke session
header('Content-Type: application/json; charset=utf-8');

// mulai session jika belum
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// include config (gunakan path sesuai project Anda)
$cfg = __DIR__ . '/config.php';
if (!file_exists($cfg)) {
    echo json_encode(['success' => false, 'message' => 'config.php tidak ditemukan.']);
    exit;
}
require_once $cfg;

// cari user_id dari query string dulu, kalau tidak ada pakai session
$user_id = null;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int) $_GET['user_id'];
} elseif (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak diberikan.']);
    exit;
}

// cek koneksi db
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Database connection tidak tersedia.']);
    exit;
}

// ambil status_parkir dari tabel users (sesuaikan nama kolom)
try {
    $stmt = $conn->prepare("SELECT status_parkir FROM users WHERE id = ?");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
        exit;
    }

    // normalisasi nilai (lowercase, trim)
    $raw = strtolower(trim((string)$row['status_parkir']));
    // jika DB memakai 'Masuk'/'Keluar' atau variasi, ubah ke 'masuk'/'keluar'
    $status = ($raw === 'masuk' || $raw === 'm' || $raw === 'in') ? 'masuk' : 'keluar';

    echo json_encode(['success' => true, 'status_parkir' => $status]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal cek status: ' . $e->getMessage()]);
    exit;
}
