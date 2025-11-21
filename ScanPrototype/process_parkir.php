<?php
// FILE: process_parkir.php (REVISI: Update status_parkir di tabel users)
// Deskripsi: mencatat MASUK / KELUAR, alokasi area, notifikasi, dan penanganan error yang aman.

header('Content-Type: application/json; charset=utf-8');
session_start();

// include config robust (naik sampai beberapa level jika perlu)
function findConfigUpwards($startDir, $filename = 'config.php', $maxUp = 6) {
    $dir = realpath($startDir);
    for ($i = 0; $i <= $maxUp; $i++) {
        $candidate = $dir . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($candidate)) return $candidate;
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    return false;
}

$cfg = findConfigUpwards(__DIR__, 'config.php', 6);
if ($cfg) {
    require_once $cfg;
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'config.php tidak ditemukan.']);
    exit;
}

// cek koneksi db
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_errno) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database tidak tersedia atau gagal.']);
    exit;
}

// util: add notification
function add_notification($conn, $user_id, $message) {
    // Diasumsikan tabel notifications sudah ada
    $sql = "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('is', $user_id, $message);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    return false;
}

// util: update user status
function update_user_status($conn, $user_id, $status) {
    $sql = "UPDATE users SET status_parkir = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('si', $status, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    return false;
}

// ambil input
$action = strtolower(trim($_POST['action'] ?? ''));
$user_id = (int) ($_POST['user_id'] ?? 0);
$plat_nomor = trim($_POST['plat_nomor'] ?? '');
// Dapatkan MAX_CAPACITY, diasumsikan 3 jika tidak diset (sesuai file lama)
$MAX_CAPACITY = isset($_POST['max_capacity']) ? (int)$_POST['max_capacity'] : 3; 

// validasi dasar
if ($user_id <= 0 || ($action !== 'masuk' && $action !== 'keluar') || $plat_nomor === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau tidak valid. Pastikan user_id, plat_nomor dan action (masuk/keluar) diberikan.']);
    exit;
}

// response default
$response = ['status' => 'error', 'message' => 'Processing failed.'];

// -----------------------
// ACTION: MASUK
// -----------------------
if ($action === 'masuk') {
    $conn->begin_transaction();
    try {
        // 1) cek apakah user sudah memiliki sesi parkir aktif di log_parkir
        $sql_check = "SELECT id, kode_area FROM log_parkir WHERE user_id = ? AND status = 'masuk' AND waktu_keluar IS NULL ORDER BY waktu_masuk DESC LIMIT 1";
        if (!($stmt_check = $conn->prepare($sql_check))) {
            throw new Exception('Prepare failed (check): ' . $conn->error);
        }
        $stmt_check->bind_param('i', $user_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check && $res_check->num_rows > 0) {
            $row = $res_check->fetch_assoc();
            $stmt_check->close();
            $conn->rollback();
            $response['message'] = "GAGAL: Pengguna sudah tercatat parkir di Area " . htmlspecialchars($row['kode_area']);
            echo json_encode($response);
            exit;
        }
        $stmt_check->close();

        // 2) pilih area dengan jumlah terisi < MAX_CAPACITY
        $sql_area = "SELECT ap.kode_area FROM area_parkir ap FOR UPDATE";
        if (!($res_area = $conn->query($sql_area))) {
            throw new Exception('Gagal mengambil daftar area: ' . $conn->error);
        }
        $areas = [];
        while ($r = $res_area->fetch_assoc()) {
            $areas[] = $r['kode_area'];
        }

        if (empty($areas)) {
            $conn->rollback();
            $response['message'] = 'Tidak ditemukan konfigurasi area parkir di sistem.';
            echo json_encode($response);
            exit;
        }

        $place_selected = null;
        foreach ($areas as $kode_area) {
            $sql_cnt = "SELECT COUNT(*) as cnt FROM log_parkir WHERE kode_area = ? AND status = 'masuk' AND waktu_keluar IS NULL";
            if (!($stmt_cnt = $conn->prepare($sql_cnt))) {
                throw new Exception('Prepare count failed: ' . $conn->error);
            }
            $stmt_cnt->bind_param('s', $kode_area);
            $stmt_cnt->execute();
            $res_cnt = $stmt_cnt->get_result();
            $cnt_row = $res_cnt->fetch_assoc();
            $stmt_cnt->close();

            $count_here = (int)($cnt_row['cnt'] ?? 0);
            if ($count_here < $MAX_CAPACITY) {
                $place_selected = $kode_area;
                break;
            }
        }

        if ($place_selected === null) {
            $conn->rollback();
            $response['message'] = "GAGAL: Semua area parkir penuh (kapasitas per area: $MAX_CAPACITY).";
            echo json_encode($response);
            exit;
        }

        // 3) Insert log_parkir
        $sql_insert = "INSERT INTO log_parkir (user_id, plat_nomor, kode_area, waktu_masuk, status) VALUES (?, ?, ?, NOW(), 'masuk')";
        if (!($stmt_ins = $conn->prepare($sql_insert))) {
            throw new Exception('Prepare insert failed: ' . $conn->error);
        }
        $stmt_ins->bind_param('iss', $user_id, $plat_nomor, $place_selected);
        if (!$stmt_ins->execute()) {
            $stmt_ins->close();
            throw new Exception('Execute insert failed: ' . $stmt_ins->error);
        }
        $stmt_ins->close();

        // 4) Update status di tabel users
        if (!update_user_status($conn, $user_id, 'masuk')) {
             // Non-fatal, hanya log error
             error_log('Failed to update user status in users table for ID: ' . $user_id);
        }
        
        // 5) add notification (tidak wajib sukses)
        $msgNotif = "Kendaraan {$plat_nomor} berhasil dicatat MASUK di Area {$place_selected}.";
        add_notification($conn, $user_id, $msgNotif);

        // Commit transaksi
        $conn->commit();

        $response['status'] = 'success';
        $response['message'] = "Parkir MASUK berhasil dicatat. Area: " . htmlspecialchars($place_selected);
        $response['kode_area'] = $place_selected;
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        error_log('process_parkir masuk error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()]);
        exit;
    }
}

// -----------------------
// ACTION: KELUAR
// -----------------------
if ($action === 'keluar') {
    $conn->begin_transaction();
    try {
        // ambil sesi parkir aktif
        $sql_sel = "SELECT id, kode_area, plat_nomor FROM log_parkir WHERE user_id = ? AND status = 'masuk' AND waktu_keluar IS NULL ORDER BY waktu_masuk DESC LIMIT 1";
        if (!($stmt_sel = $conn->prepare($sql_sel))) {
            throw new Exception('Prepare select current session failed: ' . $conn->error);
        }
        $stmt_sel->bind_param('i', $user_id);
        $stmt_sel->execute();
        $res_sel = $stmt_sel->get_result();
        $cur = $res_sel->fetch_assoc();
        $stmt_sel->close();

        if (!$cur) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'GAGAL: Tidak ditemukan sesi parkir aktif untuk pengguna ini.']);
            exit;
        }

        // optional: verify provided plat_nomor matches the active one
        if ($plat_nomor !== $cur['plat_nomor']) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Plat nomor tidak cocok dengan sesi parkir aktif.']);
            exit;
        }

        // Update session keluar (limit 1 latest)
        $sql_upd = "UPDATE log_parkir SET waktu_keluar = NOW(), status = 'keluar' WHERE id = ? AND status = 'masuk' AND waktu_keluar IS NULL";
        if (!($stmt_upd = $conn->prepare($sql_upd))) {
            throw new Exception('Prepare update failed: ' . $conn->error);
        }
        $id_to_update = (int)$cur['id'];
        $stmt_upd->bind_param('i', $id_to_update);
        if (!$stmt_upd->execute()) {
            $stmt_upd->close();
            throw new Exception('Execute update failed: ' . $stmt_upd->error);
        }
        $affected = $stmt_upd->affected_rows;
        $stmt_upd->close();

        if ($affected <= 0) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'GAGAL: Tidak ada sesi parkir yang berhasil di-update.']);
            exit;
        }
        
        // Update status di tabel users
        if (!update_user_status($conn, $user_id, 'keluar')) {
             // Non-fatal, hanya log error
             error_log('Failed to update user status in users table for ID: ' . $user_id);
        }

        // add notification
        $msgNotif = "Kendaraan {$plat_nomor} berhasil dicatat KELUAR. Terima kasih!";
        add_notification($conn, $user_id, $msgNotif);

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Parkir KELUAR berhasil dicatat.', 'kode_area' => $cur['kode_area']]);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        error_log('process_parkir keluar error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()]);
        exit;
    }
}

// default fallback
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali. Gunakan action=masuk atau action=keluar.']);
exit;