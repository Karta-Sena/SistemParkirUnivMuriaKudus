<?php
// FILE: kendaraan.php
// DESCRIPTION: Dashboard Kendaraan (Struktur Overview + Fitur Lengkap)

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 1. SETUP DATA USER & SESSION
$placeholder = 'assets/img/avatar-placeholder.png';
$uid = $_SESSION['user_id'] ?? null;
$displayName = $_SESSION['nama'] ?? null;
$displayRole = $_SESSION['role'] ?? null; // Tambahan Role
$displayAvatar = $_SESSION['avatar'] ?? null; // Tambahan Avatar

if (!$uid) { header("Location: login.php"); exit; }

// Ambil data user jika session kosong
if ($uid && (!$displayName || !$displayAvatar)) {
    if (file_exists(__DIR__ . '/config.php')) {
        include_once __DIR__ . '/config.php';
        $stmt = $conn->prepare("SELECT nama, role, avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        if ($u) {
            $_SESSION['nama'] = $u['nama'];
            $_SESSION['role'] = $u['role'];
            $_SESSION['avatar'] = $u['avatar'];
            $displayName = $u['nama'];
            $displayRole = $u['role'];
            $displayAvatar = $u['avatar'];
        }
    }
}

// Setup Avatar Path
$avatarPath = $placeholder;
if (!empty($displayAvatar)) {
    if (filter_var($displayAvatar, FILTER_VALIDATE_URL)) {
        $avatarPath = $displayAvatar;
    } else {
        $candidate = __DIR__ . '/' . $displayAvatar;
        if (file_exists($candidate)) $avatarPath = $displayAvatar;
    }
}
$profileLink = $uid ? "profile.php?id=" . intval($uid) : "profile.php";

// 2. LOGIC FITUR (TAMBAH/EDIT/HAPUS/PILIH)
if (!isset($_SESSION['active_vehicle_id'])) $_SESSION['active_vehicle_id'] = 0;

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/config.php';

    // A. TAMBAH
    if (isset($_POST['add_vehicle'])) {
        $plat = strtoupper(trim($_POST['plat_nomor']));
        $stnk = trim($_POST['no_stnk']);
        $jenis = $_POST['jenis'];
        
        $cek = $conn->prepare("SELECT id FROM kendaraan WHERE plat_nomor = ?");
        $cek->bind_param("s", $plat);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
             $msg = "<script>alert('Gagal: Plat nomor sudah terdaftar!');</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO kendaraan (user_id, plat_nomor, no_stnk, jenis) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $uid, $plat, $stnk, $jenis);
            $stmt->execute();
            header("Location: kendaraan.php"); exit;
        }
    }

    // B. EDIT
    if (isset($_POST['edit_vehicle'])) {
        $id_edit = intval($_POST['vehicle_id']);
        $plat = strtoupper(trim($_POST['plat_nomor']));
        $stnk = trim($_POST['no_stnk']);
        $jenis = $_POST['jenis'];

        $stmt = $conn->prepare("UPDATE kendaraan SET plat_nomor=?, no_stnk=?, jenis=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sssii", $plat, $stnk, $jenis, $id_edit, $uid);
        if($stmt->execute()) {
            $msg = "<script>alert('Data kendaraan berhasil diperbarui!');</script>";
        }
    }

    // C. PILIH
    if (isset($_POST['select_vehicle'])) {
        $_SESSION['active_vehicle_id'] = intval($_POST['vehicle_id']);
        header("Location: kendaraan.php"); exit;
    }
}

// D. HAPUS
if (isset($_GET['hapus'])) {
    include_once __DIR__ . '/config.php';
    $id_hapus = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM kendaraan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id_hapus, $uid);
    $stmt->execute();
    if ($_SESSION['active_vehicle_id'] == $id_hapus) $_SESSION['active_vehicle_id'] = 0;
    header("Location: kendaraan.php"); exit;
}

// Ambil Data
$vehicles = [];
if (file_exists(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
    $stmt = $conn->prepare("SELECT * FROM kendaraan WHERE user_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}

// Palet Warna Random
$cardPalettes = [
    ['bg' => '#ffffff', 'text' => '#1e293b', 'btn' => '#3b82f6'], 
    ['bg' => '#f0f9ff', 'text' => '#0369a1', 'btn' => '#0ea5e9'], 
    ['bg' => '#f0fdf4', 'text' => '#15803d', 'btn' => '#22c55e'], 
    ['bg' => '#fff7ed', 'text' => '#9a3412', 'btn' => '#f97316'], 
    ['bg' => '#faf5ff', 'text' => '#6b21a8', 'btn' => '#a855f7'], 
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kendaraan - Dashboard Parkir UMK</title>
  
  <link rel="stylesheet" href="Css/dashboard_layout.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@100..900&display=swap" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.29/bundled/lenis.min.js"></script>

  <style>
    /* STYLE KHUSUS HALAMAN KENDARAAN (Internal Override) */
    html { scroll-behavior: smooth; }
    
    /* Reset padding page-content agar parallax full width */
    .page-content {
        padding: 0 !important;
        width: 100%;
        background: transparent !important; 
        min-height: 100vh;
    }

    .parallax-wrapper {
        width: 100%;
        position: relative;
        padding-bottom: 0vh; 
    }

    /* STICKY CONTAINER (LOGIKA PARALLAX) */
    .card-container {
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;    /* Center Vertikal */
        justify-content: center;/* Center Horizontal */
        padding: 0 20px;
        box-sizing: border-box;
    }

    /* WRAPPER LUAR (GLASSMORPHISM) */
    .glass-card-wrapper {
        width: 100%;
        max-width: 950px;
        height: auto;
        min-height: 480px;
        
        background: rgba(255, 255, 255, 0.15); 
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        
        border: 2px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
        
        border-radius: 32px;
        padding: 20px; 
        
        transform-origin: top center; 
        will-change: transform;
        
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* KARTU DALAM */
    .vehicle-card {
        width: 100%;
        height: 100%;
        min-height: 420px;
        border-radius: 24px;
        padding: 40px;
        display: flex;
        gap: 40px;
        align-items: center; 
        justify-content: space-between;
        position: relative;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }

    .card-left {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 15px;
        z-index: 2;
        height: 100%;
    }

    .card-right {
        flex: 1;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-scale-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .card-right img {
        width: 100%;
        max-width: 350px;
        height: auto;
        object-fit: contain;
        filter: drop-shadow(0 20px 30px rgba(0,0,0,0.2));
        transform: scale(1.1);
    }

    /* TYPOGRAPHY KARTU */
    .vehicle-header h2 {
        font-size: 2rem;
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1;
        margin-bottom: 5px;
        letter-spacing: 1px;
    }
    .vehicle-header p { font-size: 0.9rem; opacity: 0.7; }
    .plate-number {
        font-family: 'Geist Mono', monospace;
        font-size: 3rem;
        font-weight: 700;
        line-height: 1;
        margin: 15px 0;
        letter-spacing: -1px;
    }
    .stnk-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(0,0,0,0.06); padding: 8px 16px;
        border-radius: 50px; font-weight: 600; font-size: 0.9rem;
        width: fit-content;
    }

    /* TOMBOL AKSI */
    .action-buttons { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
    .btn-action {
        padding: 10px 20px; border-radius: 12px; border: none;
        font-weight: 700; font-size: 0.9rem; cursor: pointer;
        display: flex; align-items: center; gap: 8px;
        text-decoration: none; transition: transform 0.2s;
    }
    .btn-action:hover { transform: translateY(-2px); }
    .btn-select { background: #1e293b; color: #fff; }
    .btn-select.active { background: #10b981; pointer-events: none; }
    .btn-edit { background: rgba(255,255,255,0.5); border: 1px solid rgba(0,0,0,0.1); color: inherit; }
    .btn-delete { background: rgba(220, 38, 38, 0.1); color: #dc2626; }

    /* STYLE CARD TAMBAH BARU */
    .vehicle-card.add-new {
        background: #fff !important; border: 3px dashed #cbd5e1;
        flex-direction: column; justify-content: center; align-items: center;
        text-align: center; cursor: pointer; gap: 20px;
    }
    .vehicle-card.add-new:hover { background: #f8fafc !important; border-color: #3b82f6; }
    .add-icon-large { font-size: 5rem; color: #cbd5e1; transition: color 0.3s; }
    .vehicle-card.add-new:hover .add-icon-large { color: #3b82f6; }

    /* RESPONSIVE */
    @media (max-width: 991px) {
        .vehicle-card { flex-direction: column-reverse; text-align: center; padding: 30px; gap: 20px; }
        .card-left { align-items: center; }
        .card-right img { max-width: 200px; margin-bottom: 10px; }
        .plate-number { font-size: 2.5rem; }
        .action-buttons { justify-content: center; }
    }

    /* MODAL */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
        z-index: 1000; display: none; justify-content: center; align-items: center; padding: 20px;
    }
    .modal-content {
        background: #fff; width: 100%; max-width: 450px; padding: 30px; border-radius: 24px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.3); color: #1e293b;
    }
    .form-group { margin-bottom: 15px; }
    .form-input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; }
    .btn-submit { width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px; }
  </style>
</head>
<body>

  <aside class="sidebar" id="sidebar">
    <div class="internal-wrap" aria-hidden="false">
      <button id="btnInSidebar" class="btn-circle" aria-label="Collapse sidebar" title="Collapse sidebar" type="button">
        <svg id="iconInSidebar" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M15 6 L9 12 L15 18" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>

    <div class="sidebar-logo"><img src="Lambang UMK.png" alt="Logo UMK" /></div>
    
    <nav class="sidebar-nav">
      <a href="overview.php" class="nav-item"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></svg><span class="nav-text">Overview</span></a>
      <a href="qrcode.php" class="nav-item"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></svg><span class="nav-text">QR Code</span></a>
      <a href="kendaraan.php" class="nav-item active"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></svg><span class="nav-text">Kendaraan</span></a>
      <a href="riwayat_parkir.php" class="nav-item"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></svg><span class="nav-text">Riwayat Parkir</span></a>
    </nav>
  </aside>

  <div class="btn-col" id="btnCol" aria-hidden="true">
    <button id="btnInCol" class="btn-circle hidden" aria-label="Expand sidebar" title="Expand sidebar" type="button">
      <svg id="iconInCol" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
  </div>

  <nav class="bottom-nav">
    <a href="overview.php" class="nav-item"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M7,21h10c2.2,0,4-1.8,4-4v-6.5c0-1.3-0.6-2.4-1.6-3.2l-5-3.8C13,2.5,11,2.5,9.6,3.6l-5,3.7C3.6,8.1,3,9.2,3,10.5V17C3,19.2,4.8,21,7,21z M5,10.5c0-0.6,0.3-1.2,0.8-1.6l5-3.8c0.4-0.3,0.8-0.4,1.2-0.4s0.8,0.1,1.2,0.4l5,3.8c0.5,0.4,0.8,1,0.8,1.6V17c0,1.1-0.9,2-2,2H7c-1.1,0-2-0.9-2-2V10.5z"/></svg><span>Overview</span></a>
    <a href="qrcode.php" class="nav-item"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M17,3h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c1.1,0,2,0.9,2,2v1.1c0,0.6,0.4,1,1,1s1-0.4,1-1V7C21,4.8,19.2,3,17,3z"/><path d="M20,15c-0.6,0-1,0.4-1,1v1c0,1.1-0.9,2-2,2h-1c-0.6,0-1,0.4-1,1s0.4,1,1,1h1c2.2,0,4-1.8,4-4v-1C21,15.4,20.6,15,20,15z"/><path d="M8,19H7c-1.1,0-2-0.9-2-2v-1c0-0.6-0.4-1-1-1s-1,0.4-1,1v1c0,2.2,1.8,4,4,4h1c0.6,0,1-0.4,1-1S8.6,19,8,19z"/><path d="M4,9c0.6,0,1-0.4,1-1V7c0-1.1,0.9-2,2-2h1c0.6,0,1-0.4,1-1S8.6,3,8,3H7C4.8,3,3,4.8,3,7v1C3,8.5,3.4,9,4,9z"/><path d="M20,11H4c-0.6,0-1,0.4-1,1s0.4,1,1,1h16c0.6,0,1-0.4,1-1S20.6,11,20,11z"/></svg><span>QR Code</span></a>
    <a href="kendaraan.php" class="nav-item active"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M5,18.85797V20c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1h10v1c0,0.55225,0.44775,1,1,1s1-0.44775,1-1v-1.14203c1.72028-0.44727,3-1.99969,3-3.85797v-1c0-1.5155-0.85706-2.82086-2.10272-3.49939L19.75421,10H20c0.55225,0,1-0.44775,1-1s-0.44775-1-1-1h-0.81732l-0.59967-2.09863C18.0957,4.19287,16.51416,3,14.7373,3H9.2627c-1.77686,0-3.3584,1.19287-3.8457,2.90088L4.8172,8H4C3.44775,8,3,8.44775,3,9s0.44775,1,1,1h0.24573l-0.14301,0.50061C2.85706,11.17914,2,12.48456,2,14v1C2,16.85828,3.27972,18.41071,5,18.85797z M7.33984,6.4502C7.58398,5.59619,8.37451,5,9.2627,5h5.47461c0.88818,0,1.67871,0.59619,1.92285,1.45068L17.67432,10H6.32568L7.33984,6.4502z M4,14c0-1.10303,0.89697-2,2-2h12c1.10303,0,2,0.89697,2,2v1c0,1.10303-0.89697,2-2,2H6c-1.10303,0-2-0.89697-2-2V14z"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></svg><span>Kendaraan</span></a>
    <a href="riwayat_parkir.php" class="nav-item"><svg class="nav-icon-svg" viewBox="0 0 24 24"><path d="M3.8281,16.2427l3.9297,3.9287c1.1328,1.1333,2.6396,1.7573,4.2422,1.7573s3.1094-0.624,4.2422-1.7573l3.9297-3.9287c2.3389-2.3394,2.3389-6.146,0-8.4854l-3.9297-3.9287C15.1094,2.6953,13.6025,2.0713,12,2.0713s-3.1094,0.624-4.2422,1.7573L3.8281,7.7573C1.4893,10.0967,1.4893,13.9033,3.8281,16.2427z M5.2422,9.1714l3.9297-3.9287C9.9277,4.4873,10.9316,4.0713,12,4.0713s2.0723,0.416,2.8281,1.1714l3.9297,3.9287c1.5596,1.5596,1.5596,4.0977,0,5.6572l-3.9297,3.9287c-1.5117,1.5107-4.1445,1.5107-5.6563,0l-3.9297-3.9287C3.6826,13.269,3.6826,10.731,5.2422,9.1714z"/><path d="M10.5996,17c0.5527,0,1-0.4478,1-1v-2.2002H13c1.875,0,3.4004-1.5254,3.4004-3.3999S14.875,7,13,7h-2.4004c-0.5527,0-1,0.4478-1,1v4.7998V16C9.5996,16.5522,10.0469,17,10.5996,17z M14.4004,10.3999c0,0.772-0.6279,1.3999-1.4004,1.3999h-1.4004V9H13C13.7725,9,14.4004,9.6279,14.4004,10.3999z"/></svg><span>Riwayat</span></a>
  </nav>

  <header class="top-header">
    <div class="logo-mobile"><img src="Lambang UMK.png" alt="Logo UMK" /></div>
    <div class="header-parent header-parent-desktop" aria-hidden="false">
      <div class="notif-wrapper">
        <button class="header-child-notif" id="notif-btn" aria-label="Notifikasi" aria-expanded="false" aria-controls="notifDropdown"><i class="fa-solid fa-bell"></i><span class="notif-badge" aria-hidden="true"></span></button>
        <div class="notif-dropdown" id="notifDropdown" aria-hidden="true"><div class="dropdown-header"><h3>Notifikasi</h3><button class="mark-as-read-btn">Tandai semua telah dibaca</button></div><div class="dropdown-body"><a href="#" class="notif-item"><div class="notif-content"><div class="notif-title">Sistem</div><div class="notif-text">Selamat datang!</div></div></a></div></div>
      </div>
      <div class="user-wrapper">
        <button class="header-child-profile" id="user-btn" aria-label="Profil Pengguna" aria-expanded="false" aria-controls="userDropdown"><img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-placeholder avatar"><div class="user-text user-info"><span class="user-name"><?php echo htmlspecialchars($displayName ?? 'Pengguna'); ?></span><span class="user-role"><?php echo htmlspecialchars($displayRole ?? ''); ?></span></div><span class="caret">▼</span></button>
        <div class="user-dropdown" id="userDropdown" aria-hidden="true"><div class="dropdown-body"><a href="<?php echo htmlspecialchars($profileLink); ?>" class="dropdown-item"><i class="fa-solid fa-user-circle"></i><span>Profil Saya</span></a><div class="dropdown-divider"></div><a href="logout.php" class="dropdown-item danger"><i class="fa-solid fa-sign-out-alt"></i><span>Keluar</span></a></div></div>
      </div>
    </div>
    <div class="header-parent-mobile" aria-hidden="true">
      <div class="notif-wrapper">
        <button class="header-child-button" aria-label="Notifikasi" id="notif-btn-mobile"><i class="fa-solid fa-bell"></i><span class="notif-badge" aria-hidden="true"></span></button>
        <div class="notif-dropdown" id="notifDropdownMobile" aria-hidden="true"><div class="dropdown-header"><h3>Notifikasi</h3><button class="mark-as-read-btn">Tandai semua telah dibaca</button></div><div class="dropdown-body"><a href="#" class="notif-item"><div class="notif-content"><div class="notif-title">Sistem</div><div class="notif-text">Selamat datang!</div></div></a></div></div>
      </div>
      <div class="user-wrapper">
        <button class="header-child-button" aria-label="Profil Pengguna" id="user-btn-mobile"><img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-placeholder-mobile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;"></button>
        <div class="user-dropdown" id="userDropdownMobile" aria-hidden="true"><div class="dropdown-body"><a href="<?php echo htmlspecialchars($profileLink); ?>" class="dropdown-item"><i class="fa-solid fa-user-circle"></i><span>Profil Saya</span></a><div class="dropdown-divider"></div><a href="logout.php" class="dropdown-item danger"><i class="fa-solid fa-sign-out-alt"></i><span>Keluar</span></a></div></div>
      </div>
    </div>
  </header>

  <main class="main-content" id="mainContent">
    <div class="page-content">
        
        <?php if($msg) echo $msg; ?>
        
        <div class="parallax-wrapper" id="parallaxWrapper">
            
            <div class="card-container" data-index="0">
                <div class="glass-card-wrapper">
                    <div class="vehicle-card add-new" onclick="openAddModal()">
                        <div class="add-icon-large"><i class="fa-solid fa-circle-plus"></i></div>
                        <div>
                            <h2 style="font-size:1.5rem; color:#64748b; margin:0;">Tambah Kendaraan</h2>
                            <p style="color:#94a3b8; margin-top:5px;">Klik untuk mendaftarkan kendaraan baru</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php 
            foreach ($vehicles as $i => $v): 
                $realIndex = $i + 1;
                $pal = $cardPalettes[$i % count($cardPalettes)];
                $imgSrc = ($v['jenis'] == 'mobil') ? 'Css/Assets/3D Modeling Car.png' : 'Css/Assets/3D Modeling Scooter.png';
                $isActive = ($v['id'] == $_SESSION['active_vehicle_id']);
            ?>
            <div class="card-container" data-index="<?php echo $realIndex; ?>">
                <div class="glass-card-wrapper">
                    <div class="vehicle-card" style="background: <?php echo $pal['bg']; ?>; color: <?php echo $pal['text']; ?>;">
                        
                        <div class="card-left">
                            <div class="vehicle-header">
                                <h2><?php echo htmlspecialchars($v['jenis']); ?></h2>
                                <p>Kendaraan Pribadi</p>
                            </div>
                            
                            <div class="plate-number"><?php echo htmlspecialchars($v['plat_nomor']); ?></div>
                            
                            <div class="stnk-badge">
                                <i class="fa-regular fa-id-card"></i> 
                                <span>No. STNK: <?php echo htmlspecialchars($v['no_stnk']); ?></span>
                            </div>

                            <div class="action-buttons">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="select_vehicle" value="1">
                                    <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                                    <button type="submit" class="btn-action btn-select <?php echo $isActive ? 'active' : ''; ?>">
                                        <?php if($isActive): ?>
                                            <i class="fa-solid fa-check-circle"></i> Digunakan
                                        <?php else: ?>
                                            <i class="fa-regular fa-circle"></i> Pilih
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <button onclick='openEditModal(<?php echo json_encode($v); ?>)' class="btn-action btn-edit">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>

                                <a href="?hapus=<?php echo $v['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus kendaraan ini?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </div>

                        <div class="card-right">
                            <div class="image-scale-wrapper">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Vehicle 3D">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
  </main>

  <div class="modal-overlay" id="vehicleModal">
      <div class="modal-content">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
              <h3 id="modalTitle" style="margin:0; font-size:1.25rem;">Form Kendaraan</h3>
              <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
          </div>
          <form action="kendaraan.php" method="POST" id="vehicleForm">
              <input type="hidden" name="add_vehicle" id="addFlag" value="1">
              <input type="hidden" name="edit_vehicle" id="editFlag" disabled>
              <input type="hidden" name="vehicle_id" id="vehicleId">

              <div class="form-group">
                  <label style="display:block; margin-bottom:5px; font-weight:600;">Plat Nomor</label>
                  <input type="text" name="plat_nomor" id="inpPlat" class="form-input" placeholder="Contoh: H 1234 AB" required>
              </div>
              <div class="form-group">
                  <label style="display:block; margin-bottom:5px; font-weight:600;">Nomor STNK</label>
                  <input type="text" name="no_stnk" id="inpStnk" class="form-input" placeholder="Nomor sesuai STNK" required>
              </div>
              <div class="form-group">
                  <label style="display:block; margin-bottom:5px; font-weight:600;">Jenis Kendaraan</label>
                  <select name="jenis" id="inpJenis" class="form-input" required>
                      <option value="motor">Motor</option>
                      <option value="mobil">Mobil</option>
                  </select>
              </div>
              <button type="submit" class="btn-submit" id="modalBtn">Simpan Kendaraan</button>
          </form>
      </div>
  </div>
  
  <script src="Js/dashboard_main.js" defer></script>
  <div class="page-overlay" id="pageOverlay"></div>

  <script>
    // --- MODAL LOGIC ---
    const modal = document.getElementById('vehicleModal');
    const form = document.getElementById('vehicleForm');
    const title = document.getElementById('modalTitle');
    const btn = document.getElementById('modalBtn');
    
    const inpPlat = document.getElementById('inpPlat');
    const inpStnk = document.getElementById('inpStnk');
    const inpJenis = document.getElementById('inpJenis');
    const inpId = document.getElementById('vehicleId');
    const addFlag = document.getElementById('addFlag');
    const editFlag = document.getElementById('editFlag');

    function openAddModal() {
        form.reset(); title.textContent = "Tambah Kendaraan Baru"; btn.textContent = "Simpan Baru";
        addFlag.disabled = false; editFlag.disabled = true; modal.style.display = 'flex';
    }

    function openEditModal(data) {
        title.textContent = "Edit Kendaraan"; btn.textContent = "Simpan Perubahan";
        inpPlat.value = data.plat_nomor; inpStnk.value = data.no_stnk; inpJenis.value = data.jenis.toLowerCase(); inpId.value = data.id;
        addFlag.disabled = true; editFlag.disabled = false; modal.style.display = 'flex';
    }

    function closeModal() { modal.style.display = 'none'; }
    window.onclick = function(e) { if(e.target == modal) closeModal(); }

    // --- PARALLAX SCROLL ---
    const cards = document.querySelectorAll('.card-container');
    const wrappers = document.querySelectorAll('.glass-card-wrapper');
    const parallaxWrapper = document.getElementById('parallaxWrapper');
    let currentScroll = window.scrollY;
    let targetScroll = window.scrollY;

    function smoothScroll() {
        currentScroll += (targetScroll - currentScroll) * 0.1;
        const windowHeight = window.innerHeight;
        const scrollHeight = parallaxWrapper.scrollHeight;
        const maxScroll = scrollHeight - windowHeight;
        
        const globalProgress = Math.max(0, Math.min(1, currentScroll / maxScroll));

        cards.forEach((card, i) => {
            const wrapper = wrappers[i];
            if (!wrapper) return;
            const totalCards = cards.length;
            
            // Scale effect
            const targetScale = 1 - ((totalCards - i) * 0.05);
            const rangeStart = i * 0.20;
            const rangeEnd = 1;
            
            let cardProgress = 0;
            if (globalProgress >= rangeStart) {
                cardProgress = Math.min(1, (globalProgress - rangeStart) / (rangeEnd - rangeStart));
            }
            
            const currentScale = 1 - (cardProgress * (1 - targetScale));
            wrapper.style.transform = `scale(${currentScale})`;
            wrapper.style.filter = `brightness(${1 - (cardProgress * 0.15)})`;
        });
        requestAnimationFrame(smoothScroll);
    }
    window.addEventListener('scroll', () => { targetScroll = window.scrollY; });
    smoothScroll();
  </script>

</body>
</html>