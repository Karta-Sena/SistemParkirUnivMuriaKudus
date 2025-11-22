<?php
// FILE: petugas/scan_qrcode.php
session_start();
include '../config.php'; 

// Proteksi Sesi
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header("Location: login_petugas.php");
    exit();
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
$role_label   = ucfirst($_SESSION['role']);

// --- LOGIKA DATA MAP UNTUK MODAL ---
// 1. Ambil Area
$areas = [];
$sql_area = "SELECT * FROM area_parkir ORDER BY id ASC";
$res_area = $conn->query($sql_area);
if ($res_area) {
    while($row = $res_area->fetch_assoc()) $areas[] = $row;
}

// 2. Ambil Semua Slot
$sql_slots = "SELECT s.*, l.plat_nomor, k.jenis AS jenis_kendaraan
              FROM slot_parkir s
              LEFT JOIN log_parkir l ON s.id = l.slot_id AND l.status = 'masuk'
              LEFT JOIN kendaraan k ON l.plat_nomor = k.plat_nomor
              ORDER BY s.area_id ASC, s.grid_row ASC, s.grid_col ASC";
$res_slots = $conn->query($sql_slots);

$slots_by_area = [];
if ($res_slots) {
    while($row = $res_slots->fetch_assoc()) {
        $slots_by_area[$row['area_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner - Parkir UMK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="dashboard_petugas.css">
    <link rel="stylesheet" href="visual_map.css">
    <script src="zxing.min.js"></script> 
    
    <style>
        /* Style Khusus Bottom Sheet */
        .bottom-sheet-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 999;
            opacity: 0; visibility: hidden; transition: 0.3s;
            backdrop-filter: blur(4px);
        }
        .bottom-sheet-overlay.active { opacity: 1; visibility: visible; }

        .bottom-sheet {
            position: fixed; left: 0; right: 0; bottom: 0;
            background: #fff;
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.2);
            z-index: 1000;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            max-height: 85vh;
            display: flex; flex-direction: column;
        }
        .bottom-sheet.active { transform: translateY(0); }

        .sheet-header {
            padding: 20px; border-bottom: 1px solid var(--gray-200);
            display: flex; justify-content: space-between; align-items: center;
        }
        .sheet-title { font-weight: 700; font-size: 1.1rem; color: var(--secondary); }
        .sheet-close { background: none; border: none; font-size: 1.5rem; color: var(--gray-400); cursor: pointer; }

        .sheet-content {
            padding: 20px; overflow-y: auto; flex: 1;
            background: var(--gray-50);
        }

        /* Override Visual Map Grid untuk Sheet agar muat */
        .sheet-content .parking-grid {
            transform: scale(0.9); transform-origin: top center; margin-top: 0;
        }
        
        /* Input Trigger Button */
        .input-trigger {
            width: 100%; padding: 12px 16px; text-align: left;
            background: var(--white); border: 2px solid var(--gray-200);
            border-radius: var(--radius); color: var(--gray-500);
            cursor: pointer; display: flex; justify-content: space-between; align-items: center;
            transition: var(--transition);
        }
        .input-trigger.filled { border-color: var(--primary); color: var(--secondary); font-weight: 600; background: var(--primary-light); }
        .input-trigger:hover { border-color: var(--primary); }

        /* Hide Area Grids by Default */
        .area-grid-container { display: none; }
        .area-grid-container.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-group">
                <img src="../Lambang UMK.png" alt="Logo UMK" class="sidebar-logo">
            </div>
            <button class="sidebar-toggle-desktop" id="sidebarToggleBtn"><i class="fa-solid fa-angles-left"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard_petugas.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
            <a href="scan_qrcode.php" class="nav-item active"><i class="fa-solid fa-qrcode"></i><span>Scan Masuk/Keluar</span></a>
            <a href="visual_map.php" class="nav-item"><i class="fa-solid fa-map-location-dot"></i><span>Visual Map & Slot</span></a>
            <a href="data_parkir.php" class="nav-item"><i class="fa-solid fa-car-side"></i><span>Data Parkir Aktif</span></a>
            <a href="laporan.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i><span>Laporan Harian</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <div class="page-info">
                    <h1 class="page-title">Scan QR Code</h1>
                    <p class="page-subtitle">Pindai kode pengguna untuk mencatat akses</p>
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
            <div class="scanner-layout">
                
                <div class="scanner-card">
                    <div id="scanner-container" style="display:none;">
                        <video id="scanner-video" autoplay muted playsinline></video>
                        <div class="scanner-overlay"></div>
                    </div>
                    <div id="cameraPlaceholder" style="height: 350px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94A3B8; width: 100%; margin-bottom: 16px;">
                        <i class="fa-solid fa-camera" style="font-size: 3rem; margin-bottom: 10px;"></i>
                        <span>Kamera Nonaktif</span>
                    </div>
                    <button type="button" class="btn-camera" id="toggleScannerBtn">
                        <i class="fa-solid fa-camera"></i> Aktifkan Kamera
                    </button>
                </div>

                <div class="control-card">
                    <form id="scanForm">
                        <div class="form-group">
                            <label class="form-label">Kode Pengguna / ID</label>
                            <input type="text" id="user_code" name="user_code" class="form-input" placeholder="Scan QR atau Ketik ID..." autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kendaraan Terdaftar</label>
                            <select id="plat_nomor" name="plat_nomor" class="form-select" disabled>
                                <option value="">-- Menunggu ID --</option>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Jenis</label>
                                <input type="text" id="jenis_kendaraan" class="form-input" readonly placeholder="-">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warna</label>
                                <input type="text" id="warna_kendaraan" class="form-input" readonly placeholder="-">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Lokasi Parkir (Khusus Masuk)</label>
                            
                            <input type="hidden" name="kode_area" id="kode_area_input">
                            
                            <button type="button" class="input-trigger" id="btnTriggerSlot" onclick="openSlotSheet()">
                                <span id="slotLabelText">-- Pilih Lewat Peta --</span>
                                <i class="fa-solid fa-map-location-dot"></i>
                            </button>
                        </div>

                        <div class="btn-scan-action">
                            <button type="button" class="btn-scan btn-masuk" id="btnMasuk">
                                <i class="fa-solid fa-right-to-bracket"></i> Catat Masuk
                            </button>
                            <button type="button" class="btn-scan btn-keluar" id="btnKeluar">
                                <i class="fa-solid fa-right-from-bracket"></i> Catat Keluar
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <div class="bottom-sheet-overlay" id="sheetOverlay" onclick="closeSlotSheet()"></div>
    <div class="bottom-sheet" id="slotBottomSheet">
        <div class="sheet-header">
            <h3 class="sheet-title">Pilih Slot Parkir</h3>
            <button class="sheet-close" onclick="closeSlotSheet()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="padding: 10px 20px 0;">
            <div class="area-tabs">
                <?php foreach($areas as $index => $area): ?>
                <button type="button" class="area-tab <?= $index === 0 ? 'active' : '' ?>" 
                        onclick="switchSheetTab('<?= $area['id'] ?>', this)">
                    <?= htmlspecialchars($area['nama_area']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sheet-content">
            <?php foreach($areas as $index => $area): 
                $current_slots = $slots_by_area[$area['id']] ?? [];
            ?>
            <div class="area-grid-container <?= $index === 0 ? 'active' : '' ?>" id="grid-area-<?= $area['id'] ?>">
                <div class="parking-grid" style="--rows: 6; --cols: 5;">
                    <?php foreach($current_slots as $slot): 
                        $status = ($slot['status'] === 'terisi') ? 'occupied' : (($slot['status'] === 'rusak') ? 'maintenance' : 'available');
                        
                        // [UPDATE] Logika Ikon Kendaraan
                        $icon = 'fa-check';
                        if ($status == 'occupied') {
                            // Cek apakah motor atau mobil
                            $jenis = strtolower($slot['jenis_kendaraan'] ?? '');
                            $icon = ($jenis == 'motor') ? 'fa-motorcycle' : 'fa-car';
                        } elseif ($status == 'maintenance') {
                            $icon = 'fa-triangle-exclamation';
                        }
                        
                        // Hanya slot available yang bisa diklik untuk dipilih
                        $onClick = ($status === 'available') ? "selectSlot('{$slot['kode_slot']}')" : "";
                        $cursor = ($status === 'available') ? "cursor: pointer;" : "cursor: not-allowed; opacity: 0.6;";
                    ?>
                    <div class="parking-slot <?= $status ?>" 
                         style="grid-row: <?= $slot['grid_row'] ?>; grid-column: <?= $slot['grid_col'] ?>; <?= $cursor ?>"
                         onclick="<?= $onClick ?>">
                        <div class="slot-header">
                            <span class="slot-code"><?= $slot['kode_slot'] ?></span>
                        </div>
                        <div class="slot-body">
                            <i class="fa-solid <?= $icon ?>"></i>
                            <?php if($status === 'occupied'): ?>
                                <span class="visible-plat" style="font-size:0.7rem;"><?= $slot['plat_nomor'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="road-marking" style="grid-row: 2; grid-column: 1 / -1;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <nav class="mobile-nav">
        <a href="dashboard_petugas.php" class="mobile-nav-item">
            <i class="fa-solid fa-table-columns"></i>
            <span>Dash</span>
        </a>
        <a href="scan_qrcode.php" class="mobile-nav-item active">
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

    <script src="dashboard_petugas.js"></script>
    <script src="scanner_logic.js"></script>
    
    <script>
        // === LOGIKA BOTTOM SHEET MAP ===
        function openSlotSheet() {
            document.getElementById('sheetOverlay').classList.add('active');
            document.getElementById('slotBottomSheet').classList.add('active');
        }

        function closeSlotSheet() {
            document.getElementById('sheetOverlay').classList.remove('active');
            document.getElementById('slotBottomSheet').classList.remove('active');
        }

        function switchSheetTab(areaId, btnElement) {
            // 1. Hide semua grid
            document.querySelectorAll('.area-grid-container').forEach(el => el.classList.remove('active'));
            // 2. Show grid terpilih
            document.getElementById('grid-area-' + areaId).classList.add('active');
            
            // 3. Update Tab Style
            document.querySelectorAll('.sheet-header .area-tab, .bottom-sheet .area-tab').forEach(el => el.classList.remove('active'));
            btnElement.classList.add('active');
        }

        function selectSlot(kodeSlot) {
            // 1. Isi ke Hidden Input
            document.getElementById('kode_area_input').value = kodeSlot;
            
            // 2. Update Label Tombol (Feedback Visual)
            const btn = document.getElementById('btnTriggerSlot');
            document.getElementById('slotLabelText').innerText = "Terpilih: " + kodeSlot;
            btn.classList.add('filled');
            
            // 3. Tutup Sheet
            closeSlotSheet();
            
            // 4. (Opsional) SweetAlert kecil
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 1500
            });
            Toast.fire({ icon: 'success', title: 'Slot ' + kodeSlot + ' dipilih' });
        }
    </script>
</body>
</html>