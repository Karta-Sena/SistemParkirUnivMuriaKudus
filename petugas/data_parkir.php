<?php
// FILE: petugas/data_parkir.php
// DESKRIPSI: Menampilkan daftar kendaraan aktif dengan template Dashboard Petugas

session_start();
include '../config.php';

// 1. Proteksi Akses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header('Location: login_petugas.php');
    exit;
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
$role_label   = ucfirst($_SESSION['role']);

// 2. Logika Pencarian & Filter (DIPERBAIKI)
// Menggunakan trim() agar spasi tidak dianggap karakter
$search_keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_area    = isset($_GET['area']) ? trim($_GET['area']) : '';

// Query Dasar: Ambil Log Parkir yang statusnya 'masuk'
$sql = "SELECT lp.id, lp.plat_nomor, lp.waktu_masuk, lp.kode_area, 
               k.jenis, k.warna, 
               u.nama as pemilik 
        FROM log_parkir lp
        LEFT JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
        LEFT JOIN users u ON lp.user_id = u.id
        WHERE lp.status = 'masuk'";

// Terapkan Filter Pencarian (Plat / Nama)
if ($search_keyword !== '') {
    $safe_kw = $conn->real_escape_string($search_keyword);
    $sql .= " AND (lp.plat_nomor LIKE '%$safe_kw%' OR u.nama LIKE '%$safe_kw%')";
}

// Terapkan Filter Area (Hanya jika tidak kosong)
if ($filter_area !== '') {
    $safe_area = $conn->real_escape_string($filter_area);
    $sql .= " AND lp.kode_area = '$safe_area'";
}

$sql .= " ORDER BY lp.waktu_masuk DESC";
$result = $conn->query($sql);

// Ambil list area untuk filter dropdown
$areas = $conn->query("SELECT kode_area FROM area_parkir ORDER BY kode_area ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Parkir Aktif - Petugas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="dashboard_petugas.css">

    <style>
        /* Custom Style untuk Halaman Ini */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center; /* Agar tombol sejajar vertikal */
        }
        .filter-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .filter-select {
            width: 180px; /* Diperlebar sedikit */
            padding: 10px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
        }
        .btn-filter {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px; /* Padding disamakan vertikalnya */
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-filter:hover {
            background: #334155;
        }
        .btn-reset {
            background: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-reset:hover {
            background: var(--gray-100);
            color: var(--danger);
            border-color: var(--danger);
        }
        .btn-action-pindah {
            background: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex; align-items: center; gap: 4px;
            transition: 0.2s;
        }
        .btn-action-pindah:hover { background: var(--primary-dark); }
        
        .empty-data {
            text-align: center;
            padding: 40px;
            color: var(--gray-500);
        }
    </style>
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
                <i class="fa-solid fa-table-columns"></i> <span>Dashboard</span>
            </a>
            <a href="scan_qrcode.php" class="nav-item">
                <i class="fa-solid fa-qrcode"></i> <span>Scan Masuk/Keluar</span>
            </a>
            <a href="visual_map.php" class="nav-item">
                <i class="fa-solid fa-map-location-dot"></i> <span>Visual Map & Slot</span>
            </a>
            <a href="data_parkir.php" class="nav-item active">
                <i class="fa-solid fa-car-side"></i> <span>Data Parkir Aktif</span>
            </a>
            <a href="laporan.php" class="nav-item">
                <i class="fa-solid fa-chart-bar"></i> <span>Laporan Harian</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        
        <header class="top-header">
            <div class="header-left">
                <div class="page-info">
                    <h1 class="page-title">Data Parkir Aktif</h1>
                    <p class="page-subtitle">Kelola kendaraan yang sedang berada di area parkir</p>
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
            
            <div class="search-section" style="margin-bottom: 24px;">
                <form method="GET" action="data_parkir.php" class="filter-bar">
                    <input type="text" name="q" class="filter-input" 
                           placeholder="Cari Plat Nomor atau Nama Pemilik..." 
                           value="<?= htmlspecialchars($search_keyword) ?>">
                    
                    <select name="area" class="filter-select">
                        <option value="" <?= $filter_area === '' ? 'selected' : '' ?>>Semua Area</option>
                        <?php 
                        if ($areas) {
                            while($a = $areas->fetch_assoc()): 
                                // Logika Selected yang Akurat
                                $isSelected = ((string)$filter_area === (string)$a['kode_area']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($a['kode_area']) ?>" <?= $isSelected ?>>
                                Area <?= htmlspecialchars($a['kode_area']) ?>
                            </option>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </select>
                    
                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-filter"></i> Terapkan
                    </button>
                    
                    <?php if($search_keyword !== '' || $filter_area !== ''): ?>
                        <a href="data_parkir.php" class="btn-reset">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="activity-section">
                <div class="section-header">
                    <h2 class="section-title">Daftar Kendaraan (<?= $result ? $result->num_rows : 0 ?>)</h2>
                </div>

                <div class="activity-table-wrapper">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Plat Nomor</th>
                                <th>Pemilik</th>
                                <th>Kendaraan</th>
                                <th>Area</th>
                                <th>Waktu Masuk</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $waktu = date('d/m H:i', strtotime($row['waktu_masuk']));
                                    $jenis_raw = strtolower($row['jenis'] ?? '');
                                    $jenis_icon = ($jenis_raw == 'mobil') ? 'fa-car' : 'fa-motorcycle';
                                    $jenis_class = ($jenis_raw == 'mobil') ? 'mobil' : 'motor';
                                ?>
                                <tr>
                                    <td>
                                        <span class="plat-nomor"><?= htmlspecialchars($row['plat_nomor']) ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:var(--secondary);"><?= htmlspecialchars($row['pemilik'] ?? 'Tamu') ?></div>
                                    </td>
                                    <td>
                                        <span class="vehicle-type <?= $jenis_class ?>">
                                            <i class="fa-solid <?= $jenis_icon ?>"></i>
                                            <?= htmlspecialchars(ucfirst($row['jenis'] ?? '-')) ?>
                                        </span>
                                        <div style="font-size:0.75rem; color:var(--gray-500); margin-top:2px;">
                                            <?= htmlspecialchars($row['warna'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight:700; color:var(--primary);"><?= htmlspecialchars($row['kode_area']) ?></span>
                                    </td>
                                    <td class="time-col"><?= $waktu ?> WIB</td>
                                    <td>
                                        <a href="process_pindah_area.php?id=<?= $row['id'] ?>" class="btn-action-pindah">
                                            <i class="fa-solid fa-share-from-square"></i> Pindah
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-data">
                                        <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 10px; display:block;"></i>
                                        <?php if($search_keyword || $filter_area): ?>
                                            Tidak ada data yang sesuai filter "<?= htmlspecialchars($search_keyword) ?>" <?= $filter_area ? "di Area $filter_area" : "" ?>.
                                        <?php else: ?>
                                            Belum ada kendaraan yang parkir saat ini.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <nav class="mobile-nav">
        <a href="dashboard_petugas.php" class="mobile-nav-item">
            <i class="fa-solid fa-table-columns"></i>
            <span>Dash</span>
        </a>
        <a href="scan_qrcode.php" class="mobile-nav-item">
            <i class="fa-solid fa-qrcode"></i>
            <span>Scan</span>
        </a>
        <a href="visual_map.php" class="mobile-nav-item">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>Map</span>
        </a>
        <a href="data_parkir.php" class="mobile-nav-item active">
                <i class="fa-solid fa-car-side"></i><span>Data</span>
        </a>
        <a href="laporan.php" class="mobile-nav-item">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <script src="dashboard_petugas.js"></script>

</body>
</html>