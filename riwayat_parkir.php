<?php
// =================================================================================
// FILE: riwayat_parkir.php
// DESKRIPSI: Halaman Riwayat Parkir - Struktur 100% dari overview.php
// =================================================================================

// 1. Session Start
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 2. Default placeholder
$placeholder = 'assets/img/avatar-placeholder.png';

// 3. Ambil data dari session jika tersedia
$uid = $_SESSION['user_id'] ?? null;
$displayName = $_SESSION['nama'] ?? null;
$displayRole = $_SESSION['role'] ?? null;
$displayAvatar = $_SESSION['avatar'] ?? null;

// 4. Redirect jika belum login
if (!$uid) { header("Location: login.php"); exit; }

// 5. Jika session ada user id tapi nama/avatar belum tersedia, ambil dari DB
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
                $_SESSION['nama'] = $displayName;
                $_SESSION['role'] = $displayRole;
                $_SESSION['avatar'] = $displayAvatar;
            }
        }
    }
}

// 6. Resolve avatar path
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

// 7. Profile link
$profileLink = $uid ? "profile.php?id=" . intval($uid) : "profile.php";

// =================================================================================
// 8. LOGIKA DATA RIWAYAT PARKIR
// =================================================================================
$history = [];
$currentLocation = null;
$currentStatus = 'keluar';
$currentAreaName = 'TIDAK ADA';

if (file_exists(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
    
    // Query riwayat parkir (50 data terakhir)
    $sql = "SELECT l.*, l.kode_area AS slot_lokasi, a.nama_area 
            FROM log_parkir l 
            LEFT JOIN area_parkir a ON l.area_id = a.id 
            WHERE l.user_id = ? 
            ORDER BY l.id DESC LIMIT 50";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $firstRow = true;
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
            
            // Cek status terkini dari data paling baru
            if ($firstRow) {
                if (strtolower($row['status'] ?? 'keluar') === 'masuk') {
                    $currentLocation = $row['slot_lokasi'] ?? 'AREA'; 
                    $currentAreaName = $row['nama_area'] ?? 'Area Parkir';
                    $currentStatus = 'masuk';
                }
                $firstRow = false;
            }
        }
        $stmt->close();
    }
}

// 9. Setup tampilan status
if ($currentStatus === 'masuk') {
    $displayLocCode = $currentLocation; 
    $displayLocName = !empty($currentAreaName) ? "(".$currentAreaName.")" : "(TERPARKIR)";
    $displayLocColor = "#EAB308"; 
    $showStatusBadge = true; // Flag untuk menampilkan badge AKTIF
} else {
    $displayLocCode = "-----"; // Strip panjang
    $displayLocName = "";      // Kosongkan
    $displayLocColor = "#94a3b8"; 
    $showStatusBadge = false;  // Sembunyikan badge
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat - Dashboard Parkir UMK</title>
  
  <link rel="stylesheet" href="Css/dashboard_layout.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Geist+Mono:wght@100..900&display=swap" rel="stylesheet">

  <style>
    /* ==========================================================================
       GABUNGAN CSS DARI RIWAYAT_PARKIR.CSS (DENGAN REVISI VISUAL)
       ========================================================================== */

    :root {
        --font-main: 'Manrope', sans-serif;
        --font-num: 'Geist Mono', monospace;
    }

    /* 1. RIWAYAT DASHBOARD GRID LAYOUT (Stacked) */
    .riwayat-dashboard {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
    }

    /* 2. CARD LOKASI AKTIF (REVISI: CLEAN & WHITE) */
    .card-lokasi-aktif {
        position: relative;
        overflow: hidden;
        min-height: 220px;
        background: #ffffff;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        
        /* Hapus border gradient kiri */
        border-left: none !important;
        border: 1px solid rgba(0,0,0,0.05);
    }

    /* Hapus Icon Besar di Background */
    .card-lokasi-aktif::after, 
    .card-lokasi-aktif::before {
        display: none !important;
        content: none !important;
    }

    .card-lokasi-aktif .card-header {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .loc-label {
        font-family: var(--font-main);
        font-size: 0.9rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Refresh Button Style */
    .card-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        border: 1px solid rgba(0,0,0,0.08);
        background: #f8fafc;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .card-icon-btn:hover {
        background: #e2e8f0;
        color: #334155;
        transform: rotate(15deg);
    }

    .loc-content {
        margin-top: 10px;
    }

    /* Angka/Kode -> Geist Mono */
    .loc-value-big {
        font-family: var(--font-num);
        font-size: 4rem;
        font-weight: 600;
        line-height: 1;
        letter-spacing: -2px;
        margin-bottom: 8px;
    }

    /* Nama Gedung -> Manrope */
    .loc-value-small {
        font-family: var(--font-main);
        font-size: 1.2rem;
        font-weight: 800;
        color: #EAB308; /* Kuning */
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* 3. CARD RIWAYAT LOG (REVISI: HEADER CLEAN) */
    .card-riwayat-log {
        min-height: 400px;
        background: #ffffff;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0,0,0,0.05);
    }

    /* Hapus border dashed di header */
    .card-riwayat-log .card-header {
        padding-bottom: 0;
        border-bottom: none; 
        margin-bottom: 25px;
    }

    .card-riwayat-log .card-title strong {
        font-family: var(--font-main);
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
    }

    /* 4. TABLE STYLES */
    .table-container {
        width: 100%;
        overflow-x: auto;
        margin: 0;
    }

    .riwayat-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }

    .riwayat-table th {
        text-align: left;
        padding: 15px 10px;
        font-family: var(--font-main);
        font-size: 0.75rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1px;
        /* Hapus dashed, ganti solid halus */
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }

    .riwayat-table td {
        padding: 18px 10px;
        font-size: 0.9rem;
        color: #334155;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }

    .riwayat-table tbody tr:last-child td {
        border-bottom: none;
    }

    .riwayat-table tbody tr:hover {
        background: rgba(0, 0, 0, 0.01);
    }

    /* Font Data -> Geist Mono */
    .plat-nomor, .lokasi-code, .waktu-data {
        font-family: var(--font-num);
        font-weight: 500;
        color: #334155;
    }
    .plat-nomor { font-weight: 700; color: #0f172a; font-size: 1rem; }
    .lokasi-code { font-weight: 600; }
    .waktu-data { font-size: 0.85rem; color: #64748b; }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 8px;
        font-family: var(--font-main);
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.status-parked {
        background: #dcfce7;
        color: #166534;
    }

    .status-badge.status-out {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
        font-family: var(--font-main);
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.4;
    }

    /* Animasi Refresh */
    .spin-anim { animation: spin 0.6s linear; }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* Responsive Mobile */
    @media (max-width: 768px) {
        .card-lokasi-aktif, .card-riwayat-log { padding: 24px; }
        .loc-value-big { font-size: 3rem; }
    }
  </style>
</head>
<body>
  <!-- ========== SIDEBAR (DESKTOP) ========== -->
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
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></svg>
        <span class="nav-text">Overview</span>
      </a>
      <a href="qrcode.php" class="nav-item">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></svg>
        <span class="nav-text">QR Code</span>
      </a>
      <a href="kendaraan.php" class="nav-item">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></svg>
        <span class="nav-text">Kendaraan</span>
      </a>
      <a href="riwayat_parkir.php" class="nav-item active">
        <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></svg>
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
    <a href="overview.php" class="nav-item">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></svg>
      <span>Overview</span>
    </a>
    <a href="qrcode.php" class="nav-item">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></svg>
      <span>QR Code</span>
    </a>
    <a href="kendaraan.php" class="nav-item">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></svg>
      <span>Kendaraan</span>
    </a>
    <a href="riwayat_parkir.php" class="nav-item active">
      <svg class="nav-icon-svg" version="1.1" viewBox="0 0 24 24"><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></svg>
      <span>Riwayat Parkir</span>
    </a>
  </nav>

  <!-- ========== HEADER ========== -->
  <header class="top-header">
    <div class="logo-mobile">
      <img src="Lambang UMK.png" alt="Logo UMK" />
    </div>
    
    <!-- HEADER DESKTOP -->
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
    
    <!-- HEADER MOBILE -->
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
        <section class="riwayat-dashboard" style="display: flex; flex-direction: column; gap: 24px;">
          
          <article class="card card-lokasi-aktif">
            <div class="card-header">
              <div class="card-title">
                <strong style="font-family: 'Manrope', sans-serif; font-size: 0.95rem; color: #64748b; letter-spacing: 0.5px;">LOKASI PARKIR SAAT INI</strong>
                
                <?php if ($showStatusBadge): ?>
                    <span class="status-indicator" style="color: #166534; background:#dcfce7; padding:4px 8px; border-radius:6px; font-size:0.7rem; margin-left:8px;">AKTIF</span>
                <?php endif; ?>
              </div>
              
              <button class="card-icon-btn" id="btnRefreshPage" type="button" aria-label="refresh">
                <i class="fa-solid fa-rotate-right"></i>
              </button>
            </div>

            <div class="card-content" style="padding-top: 10px;">
              <div style="display: flex; flex-direction: column;">
                <div class="loc-value-big" style="font-family: 'Geist Mono', monospace; font-size: 3.5rem; font-weight: 700; line-height: 1; color: <?php echo $displayLocColor; ?>; letter-spacing: -1px;">
                    <?php echo htmlspecialchars($displayLocCode); ?>
                </div>
                
                <?php if ($currentStatus === 'masuk'): ?>
                    <div class="loc-value-small" style="font-family: 'Manrope', sans-serif; font-size: 1.1rem; font-weight: 700; color: #EAB308; margin-top: 5px;">
                        <?php echo htmlspecialchars($displayLocName); ?>
                    </div>
                <?php endif; ?>
                
                </div>
            </div>
          </article>

          <article class="card card-riwayat-log">
            <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
                <div class="card-title">
                    <strong style="font-family: 'Manrope', sans-serif; font-size: 1.2rem; color: #0f172a;">Riwayat Parkir Terakhir</strong>
                    </div>
            </div>
            
            <div class="card-content">
            <div class="table-container">
                <table class="data-table riwayat-table" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="font-family: 'Manrope', sans-serif; color:#94a3b8; font-weight:800; border-bottom: 1px solid #f1f5f9;">PLAT NOMOR</th>
                        <th style="font-family: 'Manrope', sans-serif; color:#94a3b8; font-weight:800; border-bottom: 1px solid #f1f5f9;">LOKASI</th>
                        <th style="font-family: 'Manrope', sans-serif; color:#94a3b8; font-weight:800; border-bottom: 1px solid #f1f5f9;">WAKTU MASUK</th>
                        <th style="font-family: 'Manrope', sans-serif; color:#94a3b8; font-weight:800; border-bottom: 1px solid #f1f5f9;">WAKTU KELUAR</th>
                        <th style="font-family: 'Manrope', sans-serif; color:#94a3b8; font-weight:800; border-bottom: 1px solid #f1f5f9;">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($history)): ?>
                    <?php foreach ($history as $row): 
                        $status = strtolower($row['status'] ?? 'keluar');
                        $badgeClass = ($status === 'masuk') ? 'st-in' : 'st-out'; // Pastikan CSS badge ini ada/inline
                        $badgeText  = ($status === 'masuk') ? 'TERPARKIR' : 'KELUAR';
                        
                        $dateIn = $row['waktu_masuk'] ? date('d/m/y H:i', strtotime($row['waktu_masuk'])) : '-';
                        $dateOut = $row['waktu_keluar'] ? date('d/m/y H:i', strtotime($row['waktu_keluar'])) : '-';
                    ?>
                    <tr style="border-bottom: 1px solid #f8fafc;">
                        <td><span style="font-family: 'Geist Mono', monospace; font-weight: 600; color:#0f172a;"><?php echo htmlspecialchars($row['plat_nomor']); ?></span></td>
                        <td><span style="font-family: 'Geist Mono', monospace; font-weight: 600; color:#334155;"><?php echo htmlspecialchars($row['slot_lokasi'] ?? '-'); ?></span></td>
                        <td><span style="font-family: 'Geist Mono', monospace; font-size:0.85rem; color:#64748b;"><?php echo $dateIn; ?></span></td>
                        <td><span style="font-family: 'Geist Mono', monospace; font-size:0.85rem; color:#64748b;"><?php echo $dateOut; ?></span></td>
                        
                        <td>
                            <span style="font-family: 'Manrope', sans-serif; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; 
                                  background: <?php echo ($status==='masuk'?'#dcfce7':'#fee2e2'); ?>; 
                                  color: <?php echo ($status==='masuk'?'#166534':'#991b1b'); ?>;">
                                <?php echo $badgeText; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px; font-family:'Manrope', sans-serif;">Belum ada data riwayat.</td></tr>
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

  <script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnRefresh = document.getElementById('btnRefreshPage');
        
        if (btnRefresh) {
            btnRefresh.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah aksi default jika ada
                
                // Tambahkan animasi putar ke icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.transition = "transform 0.5s ease";
                    icon.style.transform = "rotate(360deg)";
                }
                
                // Reload halaman setelah 300ms
                setTimeout(function() {
                    window.location.reload();
                }, 300);
            });
        }
    });
    </script>
</body>
</html>