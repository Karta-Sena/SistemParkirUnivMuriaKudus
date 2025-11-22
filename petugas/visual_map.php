<?php
// FILE: petugas/visual_map.php
session_start();
include '../config.php';

// Proteksi Sesi
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header("Location: login_petugas.php");
    exit();
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
$role_label = ucfirst($_SESSION['role']);

// 1. Ambil Data Area Parkir untuk Tab Navigasi
$areas = [];
$sql_area = "SELECT * FROM area_parkir ORDER BY id ASC";
$res_area = $conn->query($sql_area);
if ($res_area) {
    while($row = $res_area->fetch_assoc()) {
        $areas[] = $row;
    }
}

// 2. Tentukan Area Aktif (Default area pertama)
$active_area_id = isset($_GET['area_id']) ? $_GET['area_id'] : ($areas[0]['id'] ?? 1);

// 3. Ambil Data Slot + Info Kendaraan yang sedang parkir di slot tersebut
// Kita melakukan LEFT JOIN ke log_parkir yang statusnya 'masuk' untuk melihat siapa yang menempati slot
$sql_slots = "SELECT 
                s.*, 
                l.plat_nomor, 
                l.waktu_masuk,
                u.nama as pemilik,
                k.jenis as jenis_kendaraan
              FROM slot_parkir s
              LEFT JOIN log_parkir l ON s.id = l.slot_id AND l.status = 'masuk'
              LEFT JOIN users u ON l.user_id = u.id
              LEFT JOIN kendaraan k ON l.plat_nomor = k.plat_nomor
              WHERE s.area_id = $active_area_id
              ORDER BY s.grid_row ASC, s.grid_col ASC";

$slots = [];
$res_slots = $conn->query($sql_slots);
if ($res_slots) {
    while($row = $res_slots->fetch_assoc()) {
        $slots[] = $row;
    }
}

// Helper untuk menghitung dimensi grid maksimal agar layout rapi
$max_row = 1;
$max_col = 1;
foreach ($slots as $s) {
    if ($s['grid_row'] > $max_row) $max_row = $s['grid_row'];
    if ($s['grid_col'] > $max_col) $max_col = $s['grid_col'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Map - Parkir UMK</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="dashboard_petugas.css">
    <link rel="stylesheet" href="visual_map.css">
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-group">
                <img src="../Lambang UMK.png" alt="Logo UMK" class="sidebar-logo">
            </div>
            <button class="sidebar-toggle-desktop" id="sidebarToggleBtn">
                <i class="fa-solid fa-angles-left"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard_petugas.php" class="nav-item">
                <i class="fa-solid fa-table-columns"></i><span>Dashboard</span>
            </a>
            <a href="scan_qrcode.php" class="nav-item">
                <i class="fa-solid fa-qrcode"></i><span>Scan Masuk/Keluar</span>
            </a>
            <a href="visual_map.php" class="nav-item active">
                <i class="fa-solid fa-map-location-dot"></i><span>Visual Map & Slot</span>
            </a>
            <a href="data_parkir.php" class="nav-item">
                <i class="fa-solid fa-car-side"></i><span>Data Parkir Aktif</span>
            </a>
            <a href="laporan.php" class="nav-item">
                <i class="fa-solid fa-chart-bar"></i><span>Laporan Harian</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <div class="page-info">
                    <h1 class="page-title">Visual Map Area</h1>
                    <p class="page-subtitle">Denah real-time ketersediaan slot parkir</p>
                </div>
            </div>
            <div class="header-right">
                <a href="logout_petugas.php" class="mobile-logout-btn" onclick="return confirm('Yakin ingin keluar?');">
                    <i class="fa-solid fa-power-off"></i>
                </a>

                <div class="user-profile">
                    <div class="user-avatar"><?= strtoupper(substr($nama_petugas, 0, 1)) ?></div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($nama_petugas) ?></span>
                        <span class="user-role"><?= htmlspecialchars($role_label) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="map-wrapper">

                <div class="map-controls">
                    <div class="area-tabs">
                        <?php foreach($areas as $area): ?>
                        <a href="?area_id=<?= $area['id'] ?>" 
                        class="area-tab <?= $area['id'] == $active_area_id ? 'active' : '' ?>">
                            <i class="fa-solid fa-layer-group"></i>
                            <?= htmlspecialchars($area['nama_area']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="map-legend">
                        <div class="legend-item">
                            <span class="dot available"></span> Tersedia
                        </div>
                        <div class="legend-item">
                            <span class="dot occupied"></span> Terisi
                        </div>
                        <div class="legend-item">
                            <span class="dot maintenance"></span> Rusak
                        </div>
                    </div>
                </div>
                <div class="parking-grid" style="--rows: <?= $max_row ?>; --cols: 5;">
                    
                    <?php foreach($slots as $slot): 
                        // Logika penentuan status (Sama seperti sebelumnya)
                        $statusClass = 'available';
                        if ($slot['status'] == 'rusak') $statusClass = 'maintenance';
                        elseif ($slot['status'] == 'terisi') $statusClass = 'occupied';
                        
                        $icon = 'fa-check';
                        if ($statusClass == 'occupied') {
                            $icon = ($slot['jenis_kendaraan'] == 'motor') ? 'fa-motorcycle' : 'fa-car';
                        } elseif ($statusClass == 'maintenance') {
                            $icon = 'fa-triangle-exclamation';
                        }

                        $clean_plat  = htmlspecialchars($slot['plat_nomor'] ?? '', ENT_QUOTES);
                        $clean_owner = htmlspecialchars($slot['pemilik'] ?? '', ENT_QUOTES);
                        $clean_time  = htmlspecialchars($slot['waktu_masuk'] ?? '', ENT_QUOTES);

                        // 2. Masukkan data yang sudah bersih ke atribut HTML
                        $dataAttr = "data-slot='{$slot['kode_slot']}' data-status='{$statusClass}'";
                        
                        if ($statusClass == 'occupied') {
                            $dataAttr .= " data-plat='{$clean_plat}' data-owner='{$clean_owner}' data-time='{$clean_time}'";
                        }
                    ?>
                    <div class="parking-slot <?= $statusClass ?>" 
                        style="grid-row: <?= $slot['grid_row'] ?>; grid-column: <?= $slot['grid_col'] ?>;"
                        <?= $dataAttr ?>
                        onclick="showSlotDetail(this)">
                        
                        <div class="slot-header">
                            <span class="slot-code"><?= $slot['kode_slot'] ?></span>
                        </div>
                        
                        <div class="slot-body">
                            <i class="fa-solid <?= $icon ?>"></i>
                            <?php if($statusClass == 'occupied'): ?>
                                <span class="visible-plat"><?= htmlspecialchars($slot['plat_nomor']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="road-marking" style="grid-row: 2; grid-column: 1 / -1;"></div>
                </div>

            </div> </div>
    </main>

    <?php
    // Helper: Cari nama area yang sedang aktif untuk judul modal
    $current_area_name = "Area Parkir";
    foreach ($areas as $a) {
        if ($a['id'] == $active_area_id) {
            $current_area_name = $a['nama_area'];
            break;
        }
    }
    ?>

    <div class="modal-overlay" id="detailModal">
        <div class="detail-card">
            
            <button class="close-modal" onclick="closeModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
            
            <div class="detail-header">
                <div class="detail-icon-circle" id="modalIconBg">
                    <i class="fa-solid fa-parking" id="modalIcon"></i>
                </div>
                <div>
                    <h2 id="modalTitle" style="margin: 0; font-size: 1.25rem; color: var(--secondary);">Slot -</h2>
                    <p id="modalSubtitle" style="margin: 4px 0 0; color: var(--gray-500); font-size: 0.9rem;">
                        <?= htmlspecialchars($current_area_name) ?>
                    </p>
                </div>
            </div>
            
            <div class="detail-body">
                <div class="info-row">
                    <label>Status Saat Ini</label>
                    <span class="status-badge" id="modalStatus">Memuat...</span>
                </div>

                <div id="occupiedInfo" style="display: none;">
                    <div class="detail-divider" style="margin: 16px 0; border-top: 1px dashed var(--gray-200);"></div>
                    
                    <div class="info-row">
                        <label>Pemilik Kendaraan</label>
                        <span id="modalOwner" style="text-align: right;">-</span>
                    </div>
                    
                    <div class="info-row">
                        <label>Plat Nomor</label>
                        <span class="plat-badge" id="modalPlat" style="font-family: 'SF Mono', monospace;">-</span>
                    </div>
                    
                    <div class="info-row">
                        <label>Waktu Masuk</label>
                        <span id="modalTime">-</span>
                    </div>
                    
                    <div class="info-row">
                        <label>Durasi Parkir</label>
                        <span id="modalDuration" style="color: var(--primary); font-weight: 700;">-</span>
                    </div>
                </div>
            </div>
            
            </div>
    </div>

    <nav class="mobile-nav">
        <a href="dashboard_petugas.php" class="mobile-nav-item">
            <i class="fa-solid fa-table-columns"></i>
            <span>Dash</span>
        </a>
        <a href="scan_qrcode.php" class="mobile-nav-item">
            <i class="fa-solid fa-qrcode"></i>
            <span>Scan</span>
        </a>
        <a href="visual_map.php" class="mobile-nav-item active">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>Map</span>
        </a>
        <a href="data_parkir.php" class="mobile-nav-item">
                <i class="fa-solid fa-car-side"></i><span>Data</span>
        </a>
        <a href="laporan.php" class="mobile-nav-item">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <script src="dashboard_petugas.js"></script>
    <script src="visual_map.js"></script>
</body>
</html>