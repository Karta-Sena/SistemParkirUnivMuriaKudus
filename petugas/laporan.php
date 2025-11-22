<?php
// FILE: petugas/laporan.php
// DESKRIPSI: Halaman Laporan Harian dengan Filter Tanggal & Export CSV

session_start();
include '../config.php';

// Set Timezone
date_default_timezone_set('Asia/Jakarta');

// 1. Proteksi Akses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header('Location: login_petugas.php');
    exit;
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
$role_label   = ucfirst($_SESSION['role']);

// 2. Logika Filter Tanggal
// Default: Hari ini
$tanggal_pilih = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 3. Query Statistik Ringkasan
$stats = [
    'masuk' => 0,
    'keluar' => 0,
    'motor' => 0,
    'mobil' => 0
];

$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'masuk' THEN 1 ELSE 0 END) as jml_masuk,
    SUM(CASE WHEN status = 'keluar' THEN 1 ELSE 0 END) as jml_keluar,
    SUM(CASE WHEN k.jenis = 'motor' THEN 1 ELSE 0 END) as jml_motor,
    SUM(CASE WHEN k.jenis = 'mobil' THEN 1 ELSE 0 END) as jml_mobil
FROM log_parkir lp
LEFT JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
WHERE DATE(lp.waktu_masuk) = '$tanggal_pilih'";

$res_stats = $conn->query($sql_stats);
if ($res_stats && $row = $res_stats->fetch_assoc()) {
    $stats['masuk']  = (int) $row['jml_masuk'];
    $stats['keluar'] = (int) $row['jml_keluar'];
    $stats['motor']  = (int) $row['jml_motor'];
    $stats['mobil']  = (int) $row['jml_mobil'];
}

// 4. Query Data Detail (Tabel)
$sql_data = "SELECT lp.*, k.jenis, u.nama as pemilik, ap.nama_area 
             FROM log_parkir lp
             LEFT JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
             LEFT JOIN users u ON lp.user_id = u.id
             LEFT JOIN area_parkir ap ON lp.kode_area = ap.kode_area
             WHERE DATE(lp.waktu_masuk) = '$tanggal_pilih'
             ORDER BY lp.waktu_masuk DESC";
$result_data = $conn->query($sql_data);

// =========================================================
// LOGIKA EXPORT CSV (Tanpa file terpisah)
// =========================================================
if (isset($_GET['export']) && $_GET['export'] == 'true') {
    $filename = "Laporan_Parkir_" . $tanggal_pilih . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['No', 'Waktu Masuk', 'Waktu Keluar', 'Plat Nomor', 'Pemilik', 'Jenis', 'Area', 'Status']);
    
    // Isi Data
    $no = 1;
    // Reset pointer query agar bisa di-loop ulang
    $result_data->data_seek(0); 
    
    while ($row = $result_data->fetch_assoc()) {
        fputcsv($output, [
            $no++,
            $row['waktu_masuk'],
            $row['waktu_keluar'] ?? '-',
            $row['plat_nomor'],
            $row['pemilik'] ?? 'Tamu',
            $row['jenis'],
            $row['kode_area'],
            ucfirst($row['status'])
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - Petugas Parkir</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard_petugas.css">

    <style>
        /* Custom Style untuk Laporan */
        .filter-row {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .date-input {
            background: var(--white);
            border: 1px solid var(--glass-border);
            padding: 12px 30px;
            border-radius: var(--radius-full);
            font-family: inherit;
            color: var(--color-base-900);
            cursor: pointer;
            box-shadow: var(--shadow-xs);
        }

        /* Print Style */
        @media print {
            .sidebar, .mobile-nav, .top-header .header-right, .filter-row button, .view-all {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .activity-section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            body { background: white; }
        }

        .stats-grid {
            display: grid;
            gap: 16px;
            /* Default Desktop: 4 Kolom */
            grid-template-columns: repeat(4, 1fr); 
        }

        .stat-card {
            /* Pastikan isi kartu rapi & di tengah */
            display: flex;
            flex-direction: column; 
            justify-content: space-between; 
            align-items: center; /* Icon jadi sejajar tengah */
            text-align: center;
            padding: 20px;
            min-height: 140px; /* Tinggi seragam */
        }
        
        .stat-icon {
            /* Beri jarak icon dari angka */
            margin-top: 12px; 
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            
        .stats-grid {
            grid-template-columns: repeat(2, 1fr) !important; 
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap; 
            align-items: center;
            gap: 8px;
        }

        .date-input {
            flex: 1;
            width: auto; 
            height: 40px;
        }

        .filter-row > div {
            width: 100%; 
            margin-left: 0 !important; 
            margin-top: 8px; 
            display: flex; 
            gap: 10px; 
        }

        .filter-row button, 
        .filter-row a.btn-primary {
            display: inline-flex !important; 
            flex: 1; 
            justify-content: center;
            align-items: center;
            height: 42px; 
            white-space: nowrap; 
            font-size: 0.9rem; 
        }
            
        .filter-row button {
            background: var(--white);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-full);
            color: var(--gray-700);
        }
    }
    </style>
</head>
<body>

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
            <a href="dashboard_petugas.php" class="nav-item">
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
            <a href="laporan.php" class="nav-item active">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Laporan Harian</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        
        <header class="top-header">
            <div class="header-left">
                <div class="page-info">
                    <h1 class="page-title">Laporan Harian</h1>
                    <p class="page-subtitle">Ringkasan dan arsip aktivitas</p>
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
            
            <div class="search-section" style="margin-bottom: 24px; padding: 16px 24px;">
                <form method="GET" class="filter-row">
                    <label style="font-weight: 600; color: var(--gray-600);">Pilih Tanggal:</label>
                    <input type="date" name="date" class="date-input" 
                        value="<?= $tanggal_pilih ?>" 
                        lang="id" 
                        onchange="this.form.submit()">
                    <div style="margin-left: auto; display: flex; gap: 10px;">
                        <button type="button" class="btn-ghost" onclick="window.print()">
                            <i class="fa-solid fa-print"></i> Cetak
                        </button>
                        
                        <a href="?date=<?= $tanggal_pilih ?>&export=true" class="btn-primary" style="text-decoration:none;">
                            <i class="fa-solid fa-download"></i> Download CSV
                        </a>
                    </div>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Total Masuk</span>
                    <div class="stat-value"><?= $stats['masuk'] ?></div>
                    <div class="stat-icon green"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Total Keluar</span>
                    <div class="stat-value"><?= $stats['keluar'] ?></div>
                    <div class="stat-icon orange"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Motor</span>
                    <div class="stat-value"><?= $stats['motor'] ?></div>
                    <div class="stat-icon purple"><i class="fa-solid fa-motorcycle"></i></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Mobil</span>
                    <div class="stat-value"><?= $stats['mobil'] ?></div>
                    <div class="stat-icon blue"><i class="fa-solid fa-car"></i></div>
                </div>
            </div>

            <div class="activity-section">
                <div class="section-header">
                    <h2 class="section-title">Detail Transaksi (<?= date('d F Y', strtotime($tanggal_pilih)) ?>)</h2>
                </div>

                <div class="activity-table-wrapper">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Waktu Masuk</th>
                                <th>Waktu Keluar</th>
                                <th>Plat Nomor</th>
                                <th>Pemilik</th>
                                <th>Kendaraan</th>
                                <th>Area</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_data->num_rows > 0): ?>
                                <?php while($row = $result_data->fetch_assoc()): 
                                    $jam_masuk  = date('H:i', strtotime($row['waktu_masuk']));
                                    $jam_keluar = $row['waktu_keluar'] ? date('H:i', strtotime($row['waktu_keluar'])) : '-';
                                    $status = strtolower($row['status']);
                                    $jenis = ucfirst($row['jenis']);
                                    $icon = ($row['jenis'] == 'mobil') ? 'car' : 'motorcycle';
                                ?>
                                <tr>
                                    <td class="time-col"><?= $jam_masuk ?></td>
                                    <td class="time-col"><?= $jam_keluar ?></td>
                                    <td><span class="plat-nomor"><?= htmlspecialchars($row['plat_nomor']) ?></span></td>
                                    <td><?= htmlspecialchars($row['pemilik'] ?? 'Tamu') ?></td>
                                    <td>
                                        <span class="vehicle-type <?= $row['jenis'] ?>">
                                            <i class="fa-solid fa-<?= $icon ?>"></i> <?= $jenis ?>
                                        </span>
                                    </td>
                                    <td><span style="font-weight:600;"><?= htmlspecialchars($row['kode_area']) ?></span></td>
                                    <td><span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <span>Tidak ada data pada tanggal ini.</span>
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
        <a href="data_parkir.php" class="mobile-nav-item">
                <i class="fa-solid fa-car-side"></i><span>Data</span>
        </a>
        <a href="laporan.php" class="mobile-nav-item active">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <script src="dashboard_petugas.js"></script>
</body>
</html>