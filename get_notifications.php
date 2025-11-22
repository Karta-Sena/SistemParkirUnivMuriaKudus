<?php
// FILE: get_notifications.php
header('Content-Type: application/json');

// 1. Mulai Sesi
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 2. Cek Login & Koneksi
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include 'config.php';
$user_id = $_SESSION['user_id'];
$action  = $_GET['action'] ?? 'fetch'; // Default action: ambil data

// -----------------------------------------------------------
// ACTION: FETCH (Ambil Data Notifikasi & Hitung Unread)
// -----------------------------------------------------------
if ($action === 'fetch') {
    
    // A. Hitung Jumlah Belum Dibaca
    $sql_count = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $count_res = $stmt->get_result()->fetch_assoc();
    $unread_count = $count_res['unread'];
    $stmt->close();

    // B. Ambil 5 Pesan Terakhir
    $sql_list = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($sql_list);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res_list = $stmt->get_result();
    
    $messages = [];
    while ($row = $res_list->fetch_assoc()) {
        // Format waktu agar enak dibaca (contoh: 22 Nov 14:30)
        $row['formatted_time'] = date('d M H:i', strtotime($row['created_at']));
        $messages[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'unread' => $unread_count,
        'data'   => $messages
    ]);
    exit;
}

// -----------------------------------------------------------
// ACTION: MARK_READ (Tandai Semua Sudah Dibaca)
// -----------------------------------------------------------
if ($action === 'mark_read') {
    $sql_update = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    $stmt->close();
    exit;
}
?>