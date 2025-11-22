<?php
// FILE: petugas/dashboard_petugas.php
// Dashboard Petugas Parkir UMK - Versi 2088 (dengan Smart Search di bagian atas)

session_start();
include '../config.php';

// Proteksi Sesi
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header("Location: login_petugas.php");
    exit();
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
$role_label = ucfirst($_SESSION['role']);
$petugas_id = $_SESSION['user_id'];

// Inisialisasi Stats
$stats = [
    'total_hari_ini' => 0,
    'sedang_parkir'  => 0,
    'motor'          => 0,
    'mobil'          => 0,
    'slot_tersedia'  => 0,
    'okupansi'       => 0
];

$recent_activity = [];
$notifikasi = $_SESSION['notif_petugas'] ?? null;
unset($_SESSION['notif_petugas']);

if (isset($conn) && !$conn->connect_error) {
    // A. Kendaraan Sedang Parkir
    $sql_active = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN k.jenis = 'motor' THEN 1 ELSE 0 END) as motor,
                    SUM(CASE WHEN k.jenis = 'mobil' THEN 1 ELSE 0 END) as mobil
                  FROM log_parkir lp
                  JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
                  WHERE lp.status = 'masuk'";
    $res = $conn->query($sql_active);
    if ($res) {
        $row = $res->fetch_assoc();
        $stats['sedang_parkir'] = (int)($row['total'] ?? 0);
        $stats['motor']         = (int)($row['motor'] ?? 0);
        $stats['mobil']         = (int)($row['mobil'] ?? 0);
    }

    // B. Total Transaksi Hari Ini
    $today = date('Y-m-d');
    $sql_today = "SELECT COUNT(*) as total FROM log_parkir WHERE DATE(waktu_masuk) = '$today'";
    $res_today = $conn->query($sql_today);
    if ($res_today) {
        $stats['total_hari_ini'] = (int)$res_today->fetch_assoc()['total'];
    }

    // C. Kapasitas & Okupansi
    $total_kapasitas = 100; // bisa diambil dari tabel konfigurasi nanti
    $stats['slot_tersedia'] = max(0, $total_kapasitas - $stats['sedang_parkir']);
    $stats['okupansi'] = $total_kapasitas > 0 
        ? round(($stats['sedang_parkir'] / $total_kapasitas) * 100) 
        : 0;

    // D. Aktivitas Terbaru
    $sql_recent = "SELECT lp.*, k.jenis, k.warna, u.nama as pemilik
                   FROM log_parkir lp
                   LEFT JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
                   LEFT JOIN users u ON lp.user_id = u.id
                   ORDER BY lp.id DESC LIMIT 10";
    $res_recent = $conn->query($sql_recent);
    if ($res_recent) {
        while ($row = $res_recent->fetch_assoc()) {
            $recent_activity[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Parkir UMK</title>

    <!-- Font & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="dashboard_petugas.css">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-group">
                <img src="../Lambang UMK.png" alt="Logo UMK" class="sidebar-logo">
            </div>
            <button class="sidebar-toggle-desktop" id="sidebarToggleBtn" title="Perkecil sidebar">
                <i class="fa-solid fa-angles-left"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard_petugas.php" class="nav-item active">
                <i class="fa-solid fa-table-columns"></i>
                <span>Dashboard</span>
            </a>
            <a href="scan_qrcode.php" class="nav-item">
                <i class="fa-solid fa-qrcode"></i>
                <span>Scan Masuk/Keluar</span>
            </a>
            <a href="visual_map.php" class="nav-item">
                <i class="fa-solid fa-map-location-dot"></i>
                <span>Visual Map & Slot</span>
            </a>
            <a href="data_parkir.php" class="nav-item">
                <i class="fa-solid fa-car-side"></i>
                <span>Data Parkir Aktif</span>
            </a>
            <a href="laporan.php" class="nav-item">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Laporan Harian</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <!-- menu-toggle DIHILANGKAN sesuai permintaan -->
                <div class="page-info">
                    <h1 class="page-title">Dashboard Petugas</h1>
                    <p class="page-subtitle">Monitor parkir, slot, dan aktivitas secara real-time</p>
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
            <!-- ===============================
                 SMART SEARCH (DI PALING ATAS)
            ================================ -->
            <section class="search-section" id="searchSection">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Pencarian Kendaraan</h2>
                        <p class="section-desc">
                            Cari plat, pemilik, jenis, atau lokasi parkir dengan filter cerdas. 
                            Dirancang untuk tugas cepat di lapangan tahun 2088.
                        </p>
                    </div>
                    <div class="search-meta">
                        <span id="searchResultCount">0 hasil</span>
                        <span id="searchStatusLabel" class="search-status idle">Idle</span>
                    </div>
                </div>

                <div class="search-main-row">
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-magnifying-glass search-input-icon"></i>
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input"
                            placeholder="Contoh: K 1234 AB, nama pemilik, warna kendaraan, atau ID slot...">
                        <button id="searchClearBtn" class="search-clear-btn" title="Bersihkan pencarian">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="search-main-actions">
                        <button id="searchBtn" class="btn-primary">
                            <i class="fa-solid fa-play"></i> Cari
                        </button>
                        <button id="searchAdvancedToggle" class="btn-ghost">
                            <i class="fa-solid fa-sliders"></i> Filter lanjut
                        </button>
                    </div>
                </div>

                <div class="search-quick-filters">
                    <button class="chip-filter" data-chip="hari-ini">Hari ini</button>
                    <button class="chip-filter" data-chip="sedang-parkir">Sedang parkir</button>
                    <button class="chip-filter" data-chip="motor">Motor</button>
                    <button class="chip-filter" data-chip="mobil">Mobil</button>
                    <button class="chip-filter" data-chip="overtime">Overtime</button>
                </div>

                <div class="search-advanced" id="searchAdvancedPanel">
                    <div class="search-advanced-grid">
                        <div class="field">
                            <label for="filterJenis">Jenis kendaraan</label>
                            <select id="filterJenis">
                                <option value="">Semua</option>
                                <option value="motor">Motor</option>
                                <option value="mobil">Mobil</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="filterStatus">Status parkir</label>
                            <select id="filterStatus">
                                <option value="">Semua</option>
                                <option value="masuk">Sedang parkir (masuk)</option>
                                <option value="keluar">Sudah keluar</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="filterSlot">ID Slot</label>
                            <input type="text" id="filterSlot" placeholder="Misal: A12, B07, 15">
                        </div>
                        <div class="field">
                            <label>Rentang waktu</label>
                            <div class="date-range">
                                <input type="datetime-local" id="filterStart">
                                <span>sampai</span>
                                <input type="datetime-local" id="filterEnd">
                            </div>
                        </div>
                        <div class="field">
                            <label for="filterSort">Urutkan berdasarkan</label>
                            <select id="filterSort">
                                <option value="waktu_desc">Waktu terbaru</option>
                                <option value="waktu_asc">Waktu terlama</option>
                                <option value="jenis">Jenis kendaraan</option>
                                <option value="slot">Slot parkir</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-advanced-footer">
                        <button id="searchResetFilters" class="btn-ghost-sm">
                            Reset filter
                        </button>
                    </div>
                </div>

                <div class="search-results-wrapper">
                    <div class="search-results-header">
                        <h3>Hasil Pencarian</h3>
                        <div class="search-results-actions">
                            <button id="searchExportBtn" class="btn-outline-sm">
                                <i class="fa-solid fa-file-export"></i> Ekspor
                            </button>
                            <button id="searchRefreshBtn" class="btn-outline-sm">
                                <i class="fa-solid fa-rotate-right"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="activity-table-wrapper search-table-wrapper">
                        <table class="activity-table" id="searchResultTable">
                            <thead>
                                <tr>
                                    <th>Plat Nomor</th>
                                    <th>Pemilik</th>
                                    <th>Jenis</th>
                                    <th>Slot</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody id="searchResultBody">
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span>Belum ada pencarian. Masukkan kata kunci di atas untuk memulai.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- QUICK ACTIONS -->
            <section class="quick-actions">
                <a href="Scan_qrcode.php" class="action-card primary">
                    <div class="action-icon">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div class="action-text">
                        <span class="action-title">Scan Kendaraan</span>
                        <span class="action-desc">Masuk / Keluar & pilih lokasi parkir</span>
                    </div>
                    <i class="fa-solid fa-arrow-right action-arrow"></i>
                </a>
                <a href="data_arkir.php" class="action-card">
                    <div class="action-icon secondary">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="action-text">
                        <span class="action-title">Data Parkir</span>
                        <span class="action-desc">Pantau & pindah kendaraan aktif</span>
                    </div>
                    <i class="fa-solid fa-arrow-right action-arrow"></i>
                </a>
            </section>

            <!-- STATS GRID -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Hari Ini</span>
                        <div class="stat-icon blue">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_hari_ini']) ?></div>
                    <div class="stat-footer">
                        <span class="stat-info">Semua transaksi log_parkir</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Sedang Parkir</span>
                        <div class="stat-icon orange">
                            <i class="fa-solid fa-square-parking"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['sedang_parkir']) ?></div>
                    <div class="stat-footer">
                        <div class="occupancy-bar">
                            <div class="occupancy-fill" style="width: <?= $stats['okupansi'] ?>%"></div>
                        </div>
                        <span class="occupancy-text"><?= $stats['okupansi'] ?>% kapasitas • <?= $stats['slot_tersedia'] ?> slot tersisa</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Motor</span>
                        <div class="stat-icon green">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['motor']) ?></div>
                    <div class="stat-footer">
                        <span class="stat-info">Unit aktif di area</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Mobil</span>
                        <div class="stat-icon purple">
                            <i class="fa-solid fa-car"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['mobil']) ?></div>
                    <div class="stat-footer">
                        <span class="stat-info">Unit aktif di area</span>
                    </div>
                </div>
            </section>

            <!-- AKTIVITAS TERBARU -->
            <section class="activity-section">
                <div class="section-header">
                    <h2 class="section-title">Aktivitas Terbaru</h2>
                    <a href="riwayat.php" class="view-all">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="activity-table-wrapper">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Plat Nomor</th>
                                <th>Pemilik</th>
                                <th>Jenis</th>
                                <th>Waktu</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_activity)): ?>
                                <?php foreach($recent_activity as $row): 
                                    $status = strtolower($row['status']);
                                    $waktu_sumber = $status === 'masuk' ? $row['waktu_masuk'] : $row['waktu_keluar'];
                                    $waktu = $waktu_sumber ? date('H:i', strtotime($waktu_sumber)) : '-';
                                    $jenis = ucfirst($row['jenis'] ?? '-');
                                ?>
                                <tr>
                                    <td><span class="plat-nomor"><?= htmlspecialchars($row['plat_nomor']) ?></span></td>
                                    <td><?= htmlspecialchars($row['pemilik'] ?? '-') ?></td>
                                    <td>
                                        <span class="vehicle-type <?= strtolower($row['jenis'] ?? '') ?>">
                                            <i class="fa-solid fa-<?= ($row['jenis'] === 'motor') ? 'motorcycle' : 'car' ?>"></i>
                                            <?= $jenis ?>
                                        </span>
                                    </td>
                                    <td class="time-col"><?= $waktu ?> WIB</td>
                                    <td>
                                        <span class="status-badge <?= $status ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fa-solid fa-inbox"></i>
                                        <span>Belum ada aktivitas hari ini</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <!-- MOBILE NAV (tetap) -->
    <nav class="mobile-nav">
        <a href="dashboard_petugas.php" class="mobile-nav-item active">
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
        <a href="data_parkir.php" class="mobile-nav-item">
                <i class="fa-solid fa-car-side"></i><span>Data</span>
        </a>
        <a href="laporan.php" class="mobile-nav-item">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php if ($notifikasi): ?>
    <div class="toast <?= $notifikasi['type'] ?>" id="toast">
        <i class="fa-solid fa-<?= $notifikasi['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= htmlspecialchars($notifikasi['text']) ?></span>
    </div>
    <?php endif; ?>

    <script src="dashboard_petugas.js"></script>
</body>
</html>
