<?php
// Pastikan session dimulai sebelum output HTML
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2. Cek Apakah User Sudah Login?
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
    exit();
}

// 3. Cek Apakah Role Sesuai? (Mencegah Petugas Masuk ke Halaman User)
// List role yang diizinkan masuk ke Overview
$allowed_roles = ['mahasiswa', 'dosen', 'tamu'];

// Ambil role dari session (pastikan lowercase untuk pencocokan)
$user_role = strtolower($_SESSION['role'] ?? '');

if (!in_array($user_role, $allowed_roles)) {
    // Jika role adalah 'petugas' atau 'admin', redirect ke dashboard mereka
    if ($user_role === 'petugas' || $user_role === 'admin') {
        header("Location: petugas/dashboard_petugas.php");
    } else {
        // Jika role tidak dikenal, kembalikan ke login
        header("Location: login.php");
    }
    exit();
}

// Default placeholder
$placeholder = 'assets/img/avatar-placeholder.png';

// Ambil data dari session jika tersedia
$uid = $_SESSION['user_id'] ?? null;
$displayName = $_SESSION['nama'] ?? null;
$displayRole = $_SESSION['role'] ?? null;
$displayAvatar = $_SESSION['avatar'] ?? null;

// Jika session ada user id tapi nama/avatar belum tersedia, ambil dari DB
if ($uid && (!$displayName || !$displayAvatar)) {
    if (file_exists(__DIR__ . '/config.php')) {
        include_once __DIR__ . '/config.php';
        $stmt = $conn->prepare("SELECT nama, role, avatar FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            $u = $res->fetch_assoc();
            $stmt->close();
            if ($u) {
                $displayName = $displayName ?? $u['nama'];
                $displayRole = $displayRole ?? $u['role'];
                $displayAvatar = $displayAvatar ?? $u['avatar'];
                // persist ke session
                $_SESSION['nama'] = $displayName;
                $_SESSION['role'] = $displayRole;
                $_SESSION['avatar'] = $displayAvatar;
            }
        }
    }
}

// Resolve avatar path
$avatarPath = $placeholder;
if (!empty($displayAvatar)) {
    // Cek apakah URL
    if (filter_var($displayAvatar, FILTER_VALIDATE_URL)) {
        $avatarPath = $displayAvatar;
    } else {
        // Cek apakah file ada
        $candidate = __DIR__ . '/' . $displayAvatar;
        if (file_exists($candidate)) {
            $avatarPath = $displayAvatar;
        }
    }
}

$plat_nomor_aktif = '-----';
$veh_stnk         = '-----';
$veh_jenis        = '-----';
$veh_image        = 'Css/Assets/3D Modeling Scooter.png'; // Default gambar motor

if ($uid) {
    if (file_exists(__DIR__ . '/config.php')) {
        include_once __DIR__ . '/config.php';
        
        // 1. Cek Log Parkir (Apakah User Sedang Parkir?)
        $query_log = "SELECT l.status, l.plat_nomor, l.kode_area AS slot_lokasi, a.nama_area 
              FROM log_parkir l 
              LEFT JOIN area_parkir a ON l.area_id = a.id 
              WHERE l.user_id = ? 
              ORDER BY l.id DESC LIMIT 1";

        $stmt_log = $conn->prepare($query_log);
        $stmt_log->bind_param("i", $uid);
        $stmt_log->execute();
        $log = $stmt_log->get_result()->fetch_assoc();

        // Variabel Default Tampilan Lokasi
        $displayLocCode = '-----';
        $displayLocName = '';
        $isParked = false;

        $target_plat = null;
        $target_id   = 0;

        if (($log['status'] ?? 'keluar') === 'masuk') {
            // PRIORITAS 1: Sedang parkir
            $target_plat = $log['plat_nomor'];
            
            // Gunakan 'slot_lokasi' (ambil dari tabel log) agar tampil spesifik (misal: A1-05)
            $displayLocCode = $log['slot_lokasi'] ?? 'AREA'; 
            $displayLocName = $log['nama_area'] ?? '';
            $isParked = true;
        } else {
            // PRIORITAS 2: Tidak parkir, ambil session
            $target_id = $_SESSION['active_vehicle_id'] ?? 0;
        }

        // 2. Query Detail Kendaraan (Berdasarkan Plat atau ID)
        $sql_k = "SELECT * FROM kendaraan WHERE user_id = ?";
        $types = "i";
        $params = [$uid];

        if ($target_plat) {
            $sql_k .= " AND plat_nomor = ? LIMIT 1";
            $types .= "s";
            $params[] = $target_plat;
        } elseif ($target_id > 0) {
            $sql_k .= " AND id = ? LIMIT 1";
            $types .= "i";
            $params[] = $target_id;
        } else {
            // PRIORITAS 3 (Fallback): Ambil kendaraan terakhir ditambahkan
            $sql_k .= " ORDER BY id DESC LIMIT 1";
        }

        $stmt_k = $conn->prepare($sql_k);
        if ($stmt_k) {
            $stmt_k->bind_param($types, ...$params);
            $stmt_k->execute();
            $res_k = $stmt_k->get_result()->fetch_assoc();
            
            if ($res_k) {
                $plat_nomor_aktif = $res_k['plat_nomor'];
                $veh_stnk         = $res_k['no_stnk'];
                $veh_jenis        = ucfirst($res_k['jenis']); // Huruf kapital awal
                
                // Ganti gambar jika jenisnya mobil
                if (strtolower($res_k['jenis']) === 'mobil') {
                    $veh_image = 'Css/Assets/3D Modeling Car.png';
                }
            }
        }
    }
}

// [BARU] Ambil 3 Riwayat Terakhir untuk Overview
$history_overview = [];
if ($uid && isset($conn)) { // $conn sudah ada dari include config.php sebelumnya
    $stmt_hist = $conn->prepare("SELECT plat_nomor, waktu_masuk, status FROM log_parkir WHERE user_id = ? ORDER BY id DESC LIMIT 3");
    $stmt_hist->bind_param("i", $uid);
    $stmt_hist->execute();
    $res_hist = $stmt_hist->get_result();
    while ($row = $res_hist->fetch_assoc()) {
        $history_overview[] = $row;
    }
    $stmt_hist->close();
}

// Profile link (self)
$profileLink = $uid ? "profile.php?id=" . intval($uid) : "profile.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Parkir UMK</title>
  
  <link rel="stylesheet" href="Css/dashboard_layout.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Geist+Mono:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
  <div id="dashboard-data" 
       data-uid="<?php echo $uid; ?>" 
       data-plat="<?php echo htmlspecialchars($plat_nomor_aktif); ?>" 
       style="display:none;"></div>
  <aside class="sidebar" id="sidebar">
    <div class="internal-wrap" aria-hidden="false">
      <button id="btnInSidebar" class="btn-circle" aria-label="Collapse sidebar" title="Collapse sidebar" type="button">
        <svg id="iconInSidebar" viewBox="0 0 24 24" fill="none" aria-hidden>
          <path d="M15 6 L9 12 L15 18" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <div class="sidebar-logo">
      <img src="Lambang UMK.png" alt="Logo UMK" />
    </div>
    
    <nav class="sidebar-nav">
      <a href="#" class="nav-item active">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="grid_system"/><g id="_icons"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17   C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6   V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></g></svg>
        <span class="nav-text">Overview</span>
      </a>
      <a href="qrcode.php" class="nav-item">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></g></svg>
        <span class="nav-text">QR Code</span>
      </a>
      <a href="kendaraan.php" class="nav-item">
        <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203   c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1   s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627   c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061   C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502   C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z    M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></g></g></svg>
        <span class="nav-text">Kendaraan</span>
      </a>
      <a href="riwayat_parkir.php" class="nav-item">
        <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287   c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573   L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287   C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572   l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004   c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999   h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></g></g></svg>
        <span class="nav-text">Riwayat Parkir</span>
      </a>
    </nav>
  </aside>

  <!-- ========== TOMBOL EXPAND (Muncul saat collapsed) ========== -->
  <div class="btn-col" id="btnCol" aria-hidden="true">
    <button id="btnInCol" class="btn-circle hidden" aria-label="Expand sidebar" title="Expand sidebar" type="button">
      <svg id="iconInCol" viewBox="0 0 24 24" fill="none" aria-hidden>
        <path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <!-- ========== BOTTOM NAV (MOBILE) ========== -->
  <nav class="bottom-nav">
    <a href="#" class="nav-item active">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="grid_system"/><g id="_icons"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17   C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6   V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></g></svg>
      <span>Overview</span>
    </a>
    <a href="qrcode.php" class="nav-item">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></g></svg>
      <span>QR Code</span>
    </a>
    <a href="kendaraan.php" class="nav-item">
      <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203   c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1   s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627   c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061   C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502   C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z    M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></g></g></svg>
      <span>Kendaraan</span>
    </a>
    <a href="riwayat_parkir.php" class="nav-item">
      <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287   c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573   L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287   C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572   l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004   c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999   h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></g></g></svg>
      <span>Riwayat Parkir</span>
    </a>
  </nav>

  <!-- ========== HEADER ========== -->
  <header class="top-header">
    <div class="logo-mobile">
      <img src="Lambang UMK.png" alt="Logo UMK" />
    </div>
    <div class="header-parent header-parent-desktop" aria-hidden="false">
      <div class="notif-wrapper">
        <button class="header-child-notif" id="notif-btn" aria-label="Notifikasi" aria-expanded="false" aria-controls="notifDropdown">
          <i class="fa-solid fa-bell"></i>
          <span class="notif-badge" aria-hidden="true"></span>
        </button>
        <div class="notif-dropdown" id="notifDropdown" aria-hidden="true">
          <div class="dropdown-header">
            <h3>Notifikasi</h3>
            <button class="mark-as-read-btn">Tandai semua telah dibaca</button>
          </div>
          <div class="dropdown-body">
            <a href="#" class="notif-item">
              <div class="notif-content">
                <div class="notif-title">Parkir Berhasil</div>
                <div class="notif-text">Anda parkir di B-01 (Teknik).</div>
              </div>
            </a>
            <a href="#" class="notif-item">
              <div class="notif-content">
                <div class="notif-title">Sistem</div>
                <div class="notif-text">QR Code Anda akan segera kedaluwarsa.</div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div class="user-wrapper">
        <button class="header-child-profile" id="user-btn" aria-label="Profil Pengguna" aria-expanded="false" aria-controls="userDropdown">
          <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-placeholder avatar">
          <div class="user-text user-info">
            <span class="user-name"><?php echo htmlspecialchars($displayName ?? 'Pengguna'); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($displayRole ?? ''); ?></span>
          </div>
          <span class="caret">▼</span>
        </button>
        <div class="user-dropdown" id="userDropdown" aria-hidden="true" data-username="<?php echo htmlspecialchars($displayName ?? 'Pengguna'); ?>" data-role="<?php echo htmlspecialchars($displayRole ?? ''); ?>">
          <div class="dropdown-body">
            <a href="<?php echo htmlspecialchars($profileLink); ?>" class="dropdown-item">
              <i class="fa-solid fa-user-circle"></i>
              <span>Profil Saya</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item danger">
              <i class="fa-solid fa-sign-out-alt"></i>
              <span>Keluar</span>
            </a>
          </div>
        </div>
      </div>
    </div>
    <div class="header-parent-mobile" aria-hidden="true">
      <div class="notif-wrapper">
        <button class="header-child-button" aria-label="Notifikasi" id="notif-btn-mobile">
          <i class="fa-solid fa-bell"></i>
          <span class="notif-badge" aria-hidden="true"></span>
        </button>
        <div class="notif-dropdown" id="notifDropdownMobile" aria-hidden="true">
          <div class="dropdown-header">
            <h3>Notifikasi</h3>
            <button class="mark-as-read-btn">Tandai semua telah dibaca</button>
          </div>
          <div class="dropdown-body">
            <a href="#" class="notif-item">
              <div class="notif-content">
                <div class="notif-title">Parkir Berhasil</div>
                <div class="notif-text">Anda parkir di B-01 (Teknik).</div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div class="user-wrapper">
        <button class="header-child-button" aria-label="Profil Pengguna" id="user-btn-mobile">
          <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-placeholder-mobile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
        </button>
        <div class="user-dropdown" id="userDropdownMobile" aria-hidden="true" data-username="<?php echo htmlspecialchars($displayName ?? 'Pengguna'); ?>" data-role="<?php echo htmlspecialchars($displayRole ?? ''); ?>">
          <div class="dropdown-body">
            <a href="<?php echo htmlspecialchars($profileLink); ?>" class="dropdown-item">
              <i class="fa-solid fa-user-circle"></i>
              <span>Profil Saya</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item danger">
              <i class="fa-solid fa-sign-out-alt"></i>
              <span>Keluar</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ========== MAIN CONTENT ========== -->
  <main class="main-content" id="mainContent">
    <div class="page-content">
      <div class="main-parent-container">
        <section class="dashboard">
          <article class="card card-qr" style="overflow: visible; perspective: 1000px; min-height: 260px;">
    
    <style>
        .ov-flip-container { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; transition: transform 0.6s; }
        .ov-flip-inner { position: relative; width: 100%; height: 100%; text-align: center; transform-style: preserve-3d; }
        .ov-face {
            position: absolute; width: 100%; height: 100%; backface-visibility: hidden;
            border-radius: 16px; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            background: #ffffff; border: 1px solid #e5e7eb;
            top: 0; left: 0; right: 0; bottom: 0;
        }
        .ov-back { transform: rotateY(180deg); background: #f0fdf4; border-color: #bbf7d0; }
        .ov-btn {
            margin-top: 12px; padding: 8px 20px; border-radius: 50px;
            font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1);
        }
        .ov-btn-gen { background: #2563eb; color: white; }
        .ov-btn-gen:hover { background: #1d4ed8; transform: translateY(-2px); }
        .ov-btn-scan { background: #22c55e; color: white; cursor: default; }
    </style>

    <div class="card-header">
        <div class="card-title">
            <strong>QR ACCESS</strong>
            <span id="ovStatusLabel" style="font-size: 10px; margin-left: 8px; padding: 2px 6px; background: #f1f5f9; border-radius: 4px; color: #64748b;">Inactive</span>
        </div>
        <button class="card-icon-btn" id="ovRefreshBtn" aria-label="refresh">⟳</button>
    </div>

    <div class="card-content-qr" style="padding: 10px; height: 180px; position: relative;">
        
        <div class="ov-flip-container" id="ovFlipCard">
            
            <div class="ov-face ov-front">
                <div id="ovPlaceholder" style="text-align:center;">
                    <i class="fa-solid fa-qrcode" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 8px;"></i>
                    <div style="font-size:0.8rem; color:#94a3b8;">Tap to Generate</div>
                </div>
                
                <div id="ovQrImgFront" style="display:none; width: 120px; height: 120px;"></div>
                
                <button class="ov-btn ov-btn-gen" id="ovBtnGenerate">
                    <i class="fa-solid fa-bolt"></i> GENERATE
                </button>
            </div>

            <div class="ov-face ov-back">
                <div style="font-size: 0.7rem; font-weight: 800; color: #15803d; margin-bottom: 30px;">TERPARKIR</div>
                
                <div id="ovQrImgBack" style="width: 120px; height: 120px;"></div>
                
                <div class="ov-btn" style="background: #dcfce7; color: #166534; box-shadow:none; cursor:default;">
                    <i class="fa-solid fa-check-circle"></i> AKTIF
                </div>
            </div>

        </div>
    </div>
</article>
          <article class="card card-lokasi" style="justify-content: flex-start;">
              <div class="card-header" style="margin-bottom: 10px;">
                  <div class="card-title" style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                      <strong style="font-family: 'Manrope', sans-serif; font-size: 0.95rem; color: #64748b; letter-spacing: 0.5px;">LOKASI PARKIR SAAT INI</strong>
                      
                      <?php if ($isParked): ?>
                          <span style="color: #166534; background:#dcfce7; padding:4px 8px; border-radius:6px; font-size:0.7rem; font-weight:700; font-family: 'Manrope', sans-serif;">AKTIF</span>
                      <?php endif; ?>
                  </div>
              </div>

              <div class="card-content" style="padding-top: 0;">
                  <div style="display: flex; flex-direction: column;">
                      <div style="font-family: 'Geist Mono', monospace; font-size: 3.5rem; font-weight: 700; line-height: 1.2; letter-spacing: -1px; color: <?php echo $isParked ? '#EAB308' : '#94a3b8'; ?>;">
                          <?php echo htmlspecialchars($displayLocCode); ?>
                      </div>
                      
                      <?php if ($isParked): ?>
                          <div style="font-family: 'Manrope', sans-serif; font-size: 1.1rem; font-weight: 700; color: #EAB308; margin-top: 0px; text-transform: uppercase;">
                              (<?php echo htmlspecialchars($displayLocName); ?>)
                          </div>
                      <?php endif; ?>
                  </div>
              </div>
          </article>
          <article class="card card-kendaraan">
            <div class="card-content">
              <h3 class="card-title">Kendaraan Yang Digunakan</h3>
              <div class="vehicle-display">
                <img src="<?php echo htmlspecialchars($veh_image); ?>" alt="Kendaraan 3D" class="vehicle-image">
              </div>
              <div class="vehicle-details-list">
                <div class="detail-item">
                  <span>STNK: (<?php echo htmlspecialchars($veh_stnk); ?>)</span>
                </div>
                <div class="detail-item">
                  <span>Plat Nomor: (<?php echo htmlspecialchars($plat_nomor_aktif); ?>)</span>
                </div>
                <div class="detail-item">
                  <span>Jenis: (<?php echo htmlspecialchars($veh_jenis); ?>)</span>
                </div>
              </div>
            </div>
          </article>
          <article class="card card-riwayat">
            <div class="card-content">
              <h3 class="card-title">Riwayat Parkir Terakhir</h3>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Plat Nomor</th>
                      <th>Masuk</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!empty($history_overview)): ?>
                    <?php foreach ($history_overview as $h): 
                        // Logika Warna Badge (Hijau=Masuk, Merah=Keluar)
                        $isMasuk = (strtolower($h['status']) === 'masuk');
                        $badgeClass = $isMasuk ? 'status-parked' : 'status-out';
                        $badgeText  = $isMasuk ? 'Terparkir' : 'Keluar';
                    ?>
                    <tr>
                      <td style="font-family: 'Geist Mono', monospace; font-weight: 600;"><?php echo htmlspecialchars($h['plat_nomor']); ?></td>
                      <td><?php echo date('H:i', strtotime($h['waktu_masuk'])); ?></td>
                      <td>
                        <span class="status-badge <?php echo $badgeClass; ?>">
                          <?php echo $badgeText; ?>
                        </span>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="3" style="text-align:center; padding: 20px; color: #94a3b8;">Belum ada data.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
                </table>
              </div>
            </div>
          </article>
        </section>
      </div>
    </div>
  </main>
  
  <script src="Js/dashboard_main.js" defer></script>
  
  <div class="page-overlay" id="pageOverlay"></div>

</body>
</html>
