<?php
// FILE: scan_kendaraan.php (robust version)
// API: Mengembalikan daftar kendaraan berdasarkan user_id
header('Content-Type: application/json; charset=utf-8');

// ----------------------
// Robust include config
// ----------------------
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
    echo json_encode([
        'success' => false,
        'vehicles' => [],
        'active_plat_nomor' => null,
        'active_status' => 'keluar',
        'message' => "config.php tidak ditemukan. Dicari dari: " . __DIR__
    ]);
    exit;
}

// ----------------------
// Validate DB connection
// ----------------------
if (!isset($conn) || !($conn instanceof mysqli)) {
    // jika ada object koneksi lain, coba adapt (opsional)
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'vehicles' => [],
        'active_plat_nomor' => null,
        'active_status' => 'keluar',
        'message' => 'Database connection ($conn) tidak tersedia atau bukan mysqli. Periksa config.php.'
    ]);
    exit;
}
if ($conn->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'vehicles' => [],
        'active_plat_nomor' => null,
        'active_status' => 'keluar',
        'message' => 'Gagal koneksi DB: ' . $conn->connect_error
    ]);
    exit;
}

// ----------------------
// Sanitasi input
// ----------------------
$user_id = $_GET['user_id'] ?? null;
if (!is_numeric($user_id)) {
    echo json_encode([
        'success' => false,
        'vehicles' => [],
        'active_plat_nomor' => null,
        'active_status' => 'keluar',
        'message' => 'ID pengguna tidak valid.'
    ]);
    exit;
}
$user_id = intval($user_id);

// ----------------------
// Siapkan respons awal
// ----------------------
$response = [
    'success' => false,
    'vehicles' => [],
    'active_plat_nomor' => null,
    'active_status' => 'keluar',
    'message' => ''
];

// ----------------------
// 1) Ambil kendaraan user
// ----------------------
$sql_vehicles = "SELECT plat_nomor, jenis, warna FROM kendaraan WHERE user_id = ?";
$stmt_vehicles = $conn->prepare($sql_vehicles);
if (!$stmt_vehicles) {
    $response['message'] = 'Gagal prepare query kendaraan: ' . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt_vehicles->bind_param("i", $user_id);
if (!$stmt_vehicles->execute()) {
    $response['message'] = 'Gagal eksekusi query kendaraan: ' . $stmt_vehicles->error;
    $stmt_vehicles->close();
    echo json_encode($response);
    exit;
}
$res_veh = $stmt_vehicles->get_result();
$vehicles = [];
while ($r = $res_veh->fetch_assoc()) {
    $vehicles[] = $r;
}
$stmt_vehicles->close();


// ----------------------
// 2) Ambil status parkir terakhir (log_parkir)
// Pastikan tabel 'log_parkir' ada. Jika nama tabel berbeda, sesuaikan.
// ----------------------
$sql_status = "SELECT plat_nomor, status FROM log_parkir WHERE user_id = ? ORDER BY waktu_masuk DESC LIMIT 1";
$stmt_status = $conn->prepare($sql_status);
if ($stmt_status) {
    $stmt_status->bind_param("i", $user_id);
    if ($stmt_status->execute()) {
        $res_status = $stmt_status->get_result();
        $latest = $res_status ? $res_status->fetch_assoc() : null;
        if ($latest) {
            $st = strtolower(trim($latest['status']));
            if ($st === 'masuk' || $st === 'm' || $st === 'in') {
                $response['active_plat_nomor'] = $latest['plat_nomor'];
                $response['active_status'] = 'masuk';
            }
        }
    } else {
        // non-fatal: tetap lanjutkan, hanya beri informasi
        $response['message'] .= 'Gagal execute status query: ' . $stmt_status->error . ' ';
    }
    $stmt_status->close();
} else {
    // fallback: jika tabel/kolom tidak ada, beri tahu
    $response['message'] .= 'Gagal prepare status query: ' . $conn->error . ' ';
}

// ----------------------
// 3) Finalisasi response
// ----------------------
if (!empty($vehicles)) {
    $response['success'] = true;
    $response['vehicles'] = $vehicles;
} else {
    $response['message'] = trim($response['message'] . ' Tidak ditemukan kendaraan untuk pengguna ini.');
}

// tutup koneksi jika memang config.php tidak digunakan lagi
// $conn->close(); // jangan selalu close kalau config.php dipakai reuse

echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
exit;
?>
