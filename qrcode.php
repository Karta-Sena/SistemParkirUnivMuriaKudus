<?php
// =================================================================================
// FILE: qrcode.php
// DESKRIPSI: Halaman QR Code (Struktur Overview 100% + Lanyard Physics)
// =================================================================================

// 1. Konfigurasi Header & Cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 2. API Check Status (AJAX Polling)
if (isset($_GET['action']) && $_GET['action'] === 'check_status') {
    header('Content-Type: application/json');
    $uid = $_SESSION['user_id'] ?? 0;
    if ($uid == 0) { echo json_encode(['status' => 'error']); exit; }

    if (file_exists(__DIR__ . '/config.php')) include_once __DIR__ . '/config.php';
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT status FROM log_parkir WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        echo json_encode(['status' => 'success', 'park_status' => $data['status'] ?? 'keluar']);
    }
    exit;
}

// 3. Data User & Session Logic
$uid = $_SESSION['user_id'] ?? 0;
if ($uid == 0) { header("Location: login.php"); exit; }

// Variabel Default
$placeholder   = 'assets/img/avatar-placeholder.png';
$displayName   = $_SESSION['nama'] ?? 'Pengguna';
$displayRole   = $_SESSION['role'] ?? 'User';
$displayAvatar = $_SESSION['avatar'] ?? null;
$plat          = '-----';
$stnk          = '-----';
$status_parkir = 'keluar'; 

// Ambil Data Terbaru
if (file_exists(__DIR__ . '/config.php'))
    include_once __DIR__ . '/config.php';
    
    // User
    $stmt = $conn->prepare("SELECT nama, role, avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u) {
        $displayName = $u['nama'];
        $displayRole = $u['role'];
        $displayAvatar = $u['avatar'];
        // Sync session
        $_SESSION['nama'] = $displayName;
        $_SESSION['role'] = $displayRole;
        $_SESSION['avatar'] = $displayAvatar;
    }

    // Log Parkir
    $query = "SELECT l.status, l.plat_nomor, a.kode_area 
              FROM log_parkir l
              LEFT JOIN area_parkir a ON l.area_id = a.id 
              WHERE l.user_id = ? 
              ORDER BY l.id DESC LIMIT 1";
              
    $stmt_log = $conn->prepare($query);
    $stmt_log->bind_param("i", $uid);
    $stmt_log->execute();
    $log = $stmt_log->get_result()->fetch_assoc();
    
    $status_parkir = $log['status'] ?? 'keluar';
    $plat_di_log   = $log['plat_nomor'] ?? '';
    $lokasi_parkir = $log['kode_area'] ?? '-';

    // Kendaraan
// --- LOGIKA PENGAMBILAN DATA KENDARAAN (REVISI) ---
    
    // PRIORITAS 1: Jika sedang PARKIR (Masuk), WAJIB ambil data dari Log (Realita Lapangan)
    if ($status_parkir === 'masuk' && !empty($plat_di_log)) {
        $stmt_k = $conn->prepare("SELECT plat_nomor, no_stnk FROM kendaraan WHERE plat_nomor = ? AND user_id = ? LIMIT 1");
        $stmt_k->bind_param("si", $plat_di_log, $uid);
    } 
    // PRIORITAS 2: Jika sedang DILUAR (Keluar), Cek apakah ada kendaraan yang DIPILIH user?
    else {
        // Ambil ID dari session (diset saat klik tombol 'Pilih' di kendaraan.php)
        $active_vid = $_SESSION['active_vehicle_id'] ?? 0;

        if ($active_vid > 0) {
            // Ambil kendaraan sesuai pilihan
            $stmt_k = $conn->prepare("SELECT plat_nomor, no_stnk FROM kendaraan WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt_k->bind_param("ii", $active_vid, $uid);
        } else {
            // PRIORITAS 3 (Fallback): Jika belum pilih, ambil kendaraan terakhir ditambahkan
            $stmt_k = $conn->prepare("SELECT plat_nomor, no_stnk FROM kendaraan WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmt_k->bind_param("i", $uid);
        }
    }
    
    // Eksekusi Query Final
    if (isset($stmt_k)) {
        $stmt_k->execute();
        $kendaraan = $stmt_k->get_result()->fetch_assoc();
        if ($kendaraan) {
            $plat = $kendaraan['plat_nomor'];
            $stnk = $kendaraan['no_stnk'];
        }
    }

// Avatar Path Logic
$avatarPath = $placeholder;
if (!empty($displayAvatar)) {
    if (filter_var($displayAvatar, FILTER_VALIDATE_URL)) {
        $avatarPath = $displayAvatar;
    } else {
        $candidate = __DIR__ . '/' . $displayAvatar;
        if (file_exists($candidate)) {
            $avatarPath = $displayAvatar;
        }
    }
}
$profileLink = "profile.php?id=" . intval($uid);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>QR Access - Dashboard Parkir UMK</title>
  
  <link rel="stylesheet" href="Css/dashboard_layout.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

  <style>
    /* =========================================
       STYLE KHUSUS QR PAGE (LANYARD & CARD)
    ========================================= */
    :root {
        --primary-blue: #2563eb;
        --glass-border: rgba(255, 255, 255, 0.4);
    }

    /* 1. Override Main Content untuk Lanyard Overlay */
    .main-content {
        position: relative;
        overflow: visible !important; 
        z-index: 10;
    }
    
    /* 2. Layout Wrapper - Gradient Background */
    .qr-layout-wrapper {
        width: 100%;
        min-height: calc(100vh - 80px);
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding-top: 80px;
        position: relative;
        background: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
    }

    /* 3. Swing Container (Physics Wrapper) */
    .swing-container {
        position: relative;
        width: 320px;
        z-index: 9999; /* Di atas Header */
        transform-origin: top center;
        transform-style: preserve-3d;
        perspective: 1000px;
        margin-top: -80px; 
    }

    /* 4. Tali Lanyard */
    .lanyard-strap {
        width: 26px;
        height: 150vh; 
        background: #1a1a1a;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: -45px; 
        z-index: 0;
        background-image: linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 3px 3px;
        box-shadow: inset 1px 0 2px rgba(0,0,0,0.5), inset -1px 0 2px rgba(0,0,0,0.5), 5px 0 10px rgba(0,0,0,0.2);
    }
    .lanyard-strap::after {
        content: ''; position: absolute; top: 0; bottom: 0; left: 3px; right: 3px;
        border-left: 1px dashed rgba(255,255,255,0.2); border-right: 1px dashed rgba(255,255,255,0.2);
    }

    /* 5. Besi Pengait */
    .metal-connector {
        width: 40px; height: 50px;
        position: absolute; top: -45px; left: 50%; transform: translateX(-50%); z-index: 10;
    }
    .d-ring {
        width: 34px; height: 25px; background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
        border-radius: 8px 8px 20px 20px; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .d-ring::after { content: ''; position: absolute; top: 3px; left: 3px; right: 3px; bottom: 3px; background: #1e3a8a; border-radius: 5px 5px 15px 15px; }
    .snap-hook {
        width: 18px; height: 35px; background: linear-gradient(90deg, #cbd5e1, #94a3b8);
        position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%);
        border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* 6. Holder Kartu (Glass) */
    .glass-parent {
        width: 100%; height: 560px; position: relative;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 30px; padding: 12px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.1);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    }
    .card-hole {
        width: 40px; height: 10px; background: rgba(255,255,255,0.5);
        border-radius: 10px; position: absolute; top: -18px; left: 50%; transform: translateX(-50%);
        z-index: 5; border: 1px solid rgba(255,255,255,0.6);
    }

    /* 7. Inner Card & Flip */
    .flip-card-container { width: 100%; height: 100%; perspective: 1000px; }
    .flip-card-inner {
        position: relative; width: 100%; height: 100%; text-align: center;
        transform-style: preserve-3d; transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .card-face {
        position: absolute; width: 100%; height: 100%; backface-visibility: hidden;
        border-radius: 24px; padding: 25px 20px;
        background: #ffffff; background-image: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(255,255,255,1);
        display: flex; flex-direction: column; align-items: center; justify-content: space-between;
        box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
    }
    .card-front { transform: rotateY(0deg); z-index: 2; }
    .card-back { transform: rotateY(180deg); z-index: 1; }

    /* 8. Komponen Kartu */
    .card-header-small { width: 100%; display: flex; justify-content: center; margin-bottom: 5px; }
    .umk-label { font-weight: 800; color: var(--primary-blue); font-size: 1rem; letter-spacing: 0.5px; }

    /* REVISI: Scanner Box Single Dashed Line */
    .scanner-box {
        position: relative; width: 220px; height: 220px; background: #fff; border-radius: 24px;
        display: flex; justify-content: center; align-items: center; 
        margin: 10px 0 30px 0; /* Jarak bawah diperbesar agar Pill tidak nempel */
        
        /* Single Dashed Border Langsung di Box */
        border: 2px dashed #cbd5e1; 
    }
    /* Hapus Pseudo-element border ganda jika ada */
    .scanner-box::before { display: none; }

    /* REVISI: Pill Button Spacing */
    .action-pill {
        position: absolute; 
        bottom: -25px; /* Turunkan pill agar ada jarak visual (overlap style) */
        left: 50%; transform: translateX(-50%);
        padding: 12px 32px; border-radius: 50px; 
        font-weight: 700; font-size: 0.9rem;
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25); z-index: 10;
        display: flex; align-items: center; gap: 8px; white-space: nowrap; cursor: pointer;
    }
    .pill-generate { background: var(--primary-blue); color: #fff; }
    .pill-generate:hover { background: #1d4ed8; transform: translateX(-50%) translateY(-2px); }
    .pill-masuk { background: #22c55e; color: #fff; cursor: default; }
    .pill-keluar { background: #ef4444; color: #fff; cursor: default; }

    /* REVISI: Dashed Divider Separator */
    .card-divider {
        width: 100%;
        height: 1px;
        border-top: 2px dashed #e2e8f0;
        margin: 10px 0 20px 0; /* Spacing antara Scanner dan Info */
    }

    /* QR Image & Info */
    .qr-target img { width: 170px; height: 170px; border-radius: 12px; border: 3px solid #1e293b; }
    
    .info-section { width: 100%; text-align: left; }
    .info-group { margin-bottom: 10px; }
    .info-label { display: block; font-size: 0.65rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 4px; }
    .info-val { display: block; font-size: 1rem; color: #1e293b; font-weight: 700; }
    .font-mono { font-family: 'Space Mono', monospace; letter-spacing: -0.5px; }
    .card-footer { margin-top: auto; font-size: 0.65rem; color: #94a3b8; margin-bottom: 5px; }

    /* Mobile Fixes */
    @media (max-width: 768px) {
        .swing-container { transform: scale(0.92); transform-origin: top center; }
        .lanyard-strap { height: 150vh; }
    }

    .scanner-box #no-vehicle-error {
        position: absolute; 
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex !important;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 24px;
        z-index: 5;
    }

    .scanner-box #no-vehicle-error p {
        text-align: center;
        max-width: 80%;
        line-height: 1.2;
    }

    .action-pill[style*="not-allowed"] {
        box-shadow: none !important;
        background-color: #94a3b8 !important;
        background: #94a3b8 !important;
    }
  </style>
</head>
<body>
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
      <a href="overview.php" class="nav-item">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="grid_system"/><g id="_icons"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17   C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6   V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></g></svg>
        <span class="nav-text">Overview</span>
      </a>
      <a href="qrcode.php" class="nav-item active">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></g></svg>
        <span class="nav-text">QR Code</span>
      </a>
      <a href="kendaraan.php" class="nav-item">
        <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203   c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1   s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627   c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061   C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502   C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z    M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></g></g></svg>
        <span class="nav-text">Kendaraan</span>
      </a>
      <a href="#" class="nav-item">
        <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287   c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573   L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287   C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572   l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004   c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999   h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></g></g></svg>
        <span class="nav-text">Riwayat Parkir</span>
      </a>
    </nav>
  </aside>

  <div class="btn-col" id="btnCol" aria-hidden="true">
    <button id="btnInCol" class="btn-circle hidden" aria-label="Expand sidebar" title="Expand sidebar" type="button">
      <svg id="iconInCol" viewBox="0 0 24 24" fill="none" aria-hidden>
        <path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <nav class="bottom-nav">
    <a href="overview.php" class="nav-item">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="grid_system"/><g id="_icons"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17   C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6   V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></g></svg>
      <span>Overview</span>
    </a>
    <a href="qrcode.php" class="nav-item active">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></g></svg>
      <span>QR Code</span>
    </a>
    <a href="kendaraan.php" class="nav-item">
      <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203   c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1   s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627   c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061   C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502   C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z    M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></g></g></svg>
      <span>Kendaraan</span>
    </a>
    <a href="#" class="nav-item">
      <svg class="nav-icon-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 24 24" xml:space="preserve"><g id="Layer_1"/><g id="Layer_2"><g><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287   c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573   L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287   C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572   l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004   c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999   h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></g></g></svg>
      <span>Riwayat Parkir</span>
    </a>
  </nav>

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
                <div class="notif-title">Sistem</div>
                <div class="notif-text">Selamat datang!</div>
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
                <div class="notif-title">Sistem</div>
                <div class="notif-text">Selamat datang!</div>
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

  <main class="main-content" id="mainContent">
    <div class="page-content">
        
        <div class="qr-layout-wrapper">
            
            <div class="swing-container" id="swingElement">
                
                <div class="lanyard-strap"></div>

                <div class="metal-connector">
                    <div class="d-ring"></div>
                    <div class="snap-hook"></div>
                </div>

                <div class="glass-parent">
                   <div class="card-hole"></div>

                   <div class="flip-card-container" id="cardContainer">
                      <div class="flip-card-inner" id="flipCard">

                          <div class="card-face card-front">
                              <div class="card-header-small">
                                  <span class="umk-label"><i class="fa-solid fa-building-columns"></i> KARTU PARKIR UMK </span>
                              </div>

                              <div class="scanner-box">
    
                                  <div id="qr-code-front" class="qr-target"></div>
                                  <div id="qr-code-back" class="qr-target" style="display:none;"></div>

                                  <div id="no-vehicle-error" style="display:none; text-align:center; padding: 25px;">
                                      <i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #f97316; margin-bottom: 15px;"></i>
                                      <p style="font-weight: 700; color: #cc6300; font-size: 1rem; margin-bottom: 5px;">DATA KENDARAAN KOSONG</p>
                                      <p style="font-size: 0.75rem; color: #64748b;">Silakan pilih atau tambahkan kendaraan.</p>
                                  </div>
                                  
                                  <div id="placeholder-front">
                                      <i class="fa-solid fa-fingerprint" style="font-size: 2rem; display:block; margin-bottom:10px; opacity:0.5; color: var(--primary-blue);"></i>
                                      TAP TO GENERATE
                                  </div>
                                  
                                  <div id="actionBtn" class="action-pill pill-generate">
                                      <i class="fa-solid fa-qrcode"></i> GENERATE
                                  </div>
                              </div>

                              <div class="card-divider"></div>

                              <div class="info-section">
                                  <div class="info-group">
                                      <span class="info-label">Nama Pengguna</span>
                                      <span class="info-val"><?php echo htmlspecialchars($displayName); ?></span>
                                  </div>
                                  <div style="display:flex; gap: 20px;">
                                      <div class="info-group">
                                          <span class="info-label">Plat Nomor</span>
                                          <span class="info-val font-mono"><?php echo htmlspecialchars($plat); ?></span>
                                      </div>
                                      <div class="info-group">
                                          <span class="info-label">No. STNK</span>
                                          <span class="info-val font-mono"><?php echo htmlspecialchars($stnk); ?></span>
                                      </div>
                                  </div>
                              </div>
                              <div class="card-footer">Sistem Parkir Universitas Muria Kudus v1.0</div>
                          </div>

                          <div class="card-face card-back">
                              <div class="card-header-small">
                                  <span class="umk-label">STATUS AKTIF</span>
                              </div>

                              <div class="scanner-box">
                                  <div id="qr-code-back" class="qr-target"></div>
                                  <div class="action-pill pill-keluar">
                                      <i class="fa-solid fa-arrow-right-from-bracket"></i> SCAN KELUAR
                                  </div>
                              </div>

                              <div class="card-divider"></div>

                              <div class="info-section">
                                  <div class="info-group">
                                      <span class="info-label">Nama Pengguna</span>
                                      <span class="info-val"><?php echo htmlspecialchars($displayName); ?></span>
                                  </div>
                                  <div style="display:flex; gap: 20px;">
                                      <div class="info-group">
                                          <span class="info-label">Plat Nomor</span>
                                          <span class="info-val font-mono"><?php echo htmlspecialchars($plat); ?></span>
                                      </div>
                                      <div class="info-group">
                                          <span class="info-label">Status</span>
                                          <span class="info-val" style="color:#22c55e;">TERPARKIR</span>
                                      </div>
                                  </div>
                                  
                                  <div class="info-group" style="margin-top: 10px;">
                                      <span class="info-label">Lokasi Parkir</span>
                                      <span class="info-val"><?php echo htmlspecialchars($lokasi_parkir); ?></span>
                                  </div>
                                </div>
                                  </div>
                              </div>
                              
                              <div class="card-footer">Tunjukkan QR ini kepada petugas saat keluar.</div>
                          </div>

                      </div>
                   </div>
                </div>
            </div>
        </div>
    </div>
  </main>
  
  <script src="Js/dashboard_main.js"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <script>
      window.userId = "<?php echo $uid; ?>";
      window.initialStatus = "<?php echo $status_parkir; ?>";
      // Tambahkan ini: Kirim data plat nomor ke JS
      window.platNomor = "<?php echo $plat; ?>"; 
  </script>

  <script src="Js/qrcode_logic.js?v=<?php echo time(); ?>"></script>
  
  <div class="page-overlay" id="pageOverlay"></div>
</body>
</html>