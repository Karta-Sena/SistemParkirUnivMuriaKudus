<?php
/**
 * --------------------------------------------------------------------------
 * PARKOS ENTERPRISE - ATTENDANT CONSOLE (UNIFIED UMK EDITION)
 * --------------------------------------------------------------------------
 * Version      : 4.0.0-UMK
 * Architecture : Monolithic (PHP + CSS + JS in single file)
 * Design Sys   : UMK Glassmorphism + Manrope Typography
 * Logic Core   : Physics Map Engine + Session Mock DB
 * * Note: This system merges the backend robustness of the prototype
 * with the specific visual identity of the User Dashboard.
 */

session_start();

// ==========================================================================
// 1. BACKEND CORE: CONFIGURATION & MOCK DATABASE
// ==========================================================================

define('APP_NAME', 'Parkir UMK Console');
define('ZONE_ID', 'GATE-TEKNIK-01');
define('MAX_SLOTS', 100);

class MockDatabase {
    public function __construct() { $this->init(); }

    private function init() {
        // Simulasi Data Kendaraan (Persisten di Session)
        if (!isset($_SESSION['db_vehicles'])) {
            $_SESSION['db_vehicles'] = [
                'K4421XYZ' => [
                    'plate' => 'K 4421 XYZ',
                    'owner' => 'Mahasiswa Teknik',
                    'model' => 'Honda Scoopy',
                    'color' => 'Cream',
                    'status' => 'PARKED',
                    'location' => 'B-05',
                    'in_time' => date('H:i')
                ],
                'H8821ZA' => [
                    'plate' => 'H 8821 ZA',
                    'owner' => 'Dosen FEB',
                    'model' => 'Toyota Avanza',
                    'color' => 'Hitam',
                    'status' => 'PARKED',
                    'location' => 'B-12',
                    'in_time' => date('H:i', strtotime('-1 hour'))
                ]
            ];
        }

        // Simulasi Slot Parkir
        if (!isset($_SESSION['db_slots'])) {
            $slots = [];
            for ($i = 1; $i <= MAX_SLOTS; $i++) {
                $id = 'B-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $isOccupied = (rand(0, 10) > 7); 
                $slots[$id] = [
                    'id' => $id,
                    'is_occupied' => $isOccupied,
                    'vehicle_plate' => $isOccupied ? 'K4421XYZ' : null
                ];
            }
            $_SESSION['db_slots'] = $slots;
        }
    }

    public function findVehicle($query) {
        $query = strtoupper(str_replace(' ', '', $query));
        foreach ($_SESSION['db_vehicles'] as $key => $data) {
            if (strpos(str_replace(' ', '', $key), $query) !== false) return $data;
        }
        return null;
    }

    public function getSlots() { return $_SESSION['db_slots']; }

    public function updateLocation($plate, $newSlot) {
        // Simple logic to swap slot
        // In production this handles DB transcations
        return true; 
    }

    public function getStats() {
        $occupied = 0;
        foreach($_SESSION['db_slots'] as $s) if($s['is_occupied']) $occupied++;
        $total = count($_SESSION['db_slots']);
        
        return [
            'total_in' => 124 + $occupied,
            'occupancy' => round(($occupied / $total) * 100),
            'vacant' => $total - $occupied
        ];
    }
}

// --- API ROUTER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    header('Content-Type: application/json');
    $db = new MockDatabase();
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        switch ($_GET['api']) {
            case 'search':
                echo json_encode(['status' => 'success', 'data' => $db->findVehicle($input['query'])]);
                break;
            case 'map_data':
                echo json_encode(['status' => 'success', 'data' => array_values($db->getSlots())]);
                break;
            case 'notify': // Simulasi kirim notif ke dashboard user
                echo json_encode(['status' => 'success', 'message' => 'Notifikasi terkirim ke User App']);
                break;
            default: throw new Exception("Invalid Endpoint");
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

$db = new MockDatabase();
$stats = $db->getStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?></title>
  
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    /* ========================================
    1. CORE VARIABLES (MATCHING USER DASHBOARD)
    ========================================
    */
    :root {
        /* Brand Colors */
        --umk-blue: #114A9B;
        --umk-blue-dark: #0a3574;
        --umk-yellow: #FBCE00;
        --text-white: #ffffff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        
        /* Layout Dimensions */
        --sidebar-width: 260px;
        --sidebar-collapsed: 80px;
        --header-height: 80px;
        
        /* Glass Morphism */
        --glass-bg: rgba(255, 255, 255, 0.12);
        --glass-border: rgba(255, 255, 255, 0.2);
        --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        
        /* Spacing & Radius */
        --radius-xl: 24px;
        --radius-lg: 16px;
        --radius-md: 12px;
        
        /* Transitions */
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* RESET */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
        font-family: 'Manrope', sans-serif;
        color: var(--text-white);
        min-height: 100vh;
        overflow-x: hidden;
        background: radial-gradient(circle at 50% 100%, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%),
                    linear-gradient(180deg, #0039a3 0%, #248ff9 40%, #114a9b 100%);
        background-attachment: fixed;
        background-size: cover;
    }

    /* ========================================
    2. LAYOUT STRUCTURE (SIDEBAR & HEADER)
    ========================================
    */
    .layout-container { display: flex; min-height: 100vh; }

    /* SIDEBAR */
    .sidebar {
        width: var(--sidebar-width);
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        border-right: 1px solid var(--glass-border);
        padding: 24px;
        display: flex; flex-direction: column;
        position: fixed; height: 100vh; z-index: 50;
        transition: var(--transition);
    }

    .brand {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 40px; padding: 0 12px;
    }
    .brand img { height: 40px; filter: brightness(0) invert(1); }
    .brand-text { font-weight: 800; font-size: 1.2rem; letter-spacing: -0.5px; }

    .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .nav-item {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 16px;
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        font-weight: 600; border-radius: var(--radius-md);
        transition: var(--transition);
    }
    .nav-item:hover, .nav-item.active {
        background: rgba(255,255,255,0.15);
        color: var(--umk-yellow);
        transform: translateX(4px);
    }
    .nav-item i { width: 24px; text-align: center; font-size: 1.1rem; }

    .user-profile {
        margin-top: auto;
        padding: 16px;
        background: rgba(0,0,0,0.2);
        border-radius: var(--radius-md);
        display: flex; align-items: center; gap: 12px;
    }
    .avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--umk-yellow); }

    /* MAIN CONTENT */
    .main-content {
        margin-left: var(--sidebar-width);
        flex: 1; width: calc(100% - var(--sidebar-width));
        padding: 24px;
    }

    /* HEADER */
    .header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 32px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(12px);
        padding: 16px 24px;
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
    }

    .search-bar {
        position: relative; width: 400px;
    }
    .search-input {
        width: 100%; padding: 12px 20px 12px 45px;
        border-radius: 50px; border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.15);
        color: white; font-family: 'Manrope'; outline: none;
        transition: var(--transition);
    }
    .search-input::placeholder { color: rgba(255,255,255,0.6); }
    .search-input:focus { background: rgba(255,255,255,0.25); border-color: var(--umk-yellow); }
    .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); opacity: 0.7; }

    .header-actions { display: flex; gap: 16px; }
    .action-btn {
        width: 44px; height: 44px; border-radius: 50%;
        background: rgba(255,255,255,0.15); border: 1px solid var(--glass-border);
        color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .action-btn:hover { background: white; color: var(--umk-blue); transform: translateY(-2px); }

    /* ========================================
    3. DASHBOARD WIDGETS
    ========================================
    */
    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 24px; margin-bottom: 32px;
    }

    .card {
        background: rgba(255, 255, 255, 0.92);
        border-radius: var(--radius-xl);
        padding: 24px;
        color: var(--text-dark);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.5);
        transition: var(--transition);
    }
    .card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.15); }

    .stat-label { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 2.5rem; font-weight: 800; color: var(--umk-blue); margin: 8px 0; }
    .stat-icon { 
        float: right; font-size: 1.5rem; padding: 12px; 
        background: rgba(17, 74, 155, 0.1); border-radius: 12px; color: var(--umk-blue); 
    }

    /* ========================================
    4. INTERACTIVE PHYSICS MAP
    ========================================
    */
    .map-section {
        height: 600px; position: relative; overflow: hidden;
        border-radius: var(--radius-xl);
        background: #F1F5F9; /* Light grey base */
        border: 4px solid white;
        box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
    }

    .map-canvas {
        position: absolute; top: 0; left: 0;
        width: 1500px; height: 1000px;
        background-image: 
            radial-gradient(var(--umk-blue) 1px, transparent 1px);
        background-size: 40px 40px;
        background-color: #F8FAFC;
        cursor: grab;
        display: grid;
        grid-template-columns: repeat(15, 1fr);
        gap: 20px; padding: 60px;
    }
    .map-canvas:active { cursor: grabbing; }

    /* Slot Styling - Mini Cards */
    .slot {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        height: 100px; border: 2px solid transparent;
        transition: var(--transition);
        position: relative;
    }
    .slot.occupied {
        background: var(--umk-blue);
        color: white;
        border-color: var(--umk-blue-dark);
    }
    .slot.occupied .slot-id { color: var(--umk-yellow); opacity: 1; }
    .slot.vacant:hover {
        transform: scale(1.05);
        border-color: var(--umk-blue);
        box-shadow: 0 8px 20px rgba(17, 74, 155, 0.15);
    }

    .slot-id { font-weight: 800; font-size: 1.1rem; margin-bottom: 4px; }
    .slot-meta { font-size: 0.75rem; font-weight: 600; }
    .slot-plate { 
        background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px;
        font-family: monospace;
    }

    /* ========================================
    5. OVERLAYS (SCANNER & MODALS)
    ========================================
    */
    .fab-scan {
        position: fixed; bottom: 40px; right: 40px;
        width: 70px; height: 70px;
        background: var(--umk-yellow);
        color: var(--umk-blue);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem;
        box-shadow: 0 10px 30px rgba(251, 206, 0, 0.4);
        border: 4px solid white;
        cursor: pointer; z-index: 100;
        transition: var(--transition);
    }
    .fab-scan:hover { transform: scale(1.1) rotate(90deg); }

    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
        z-index: 200; opacity: 0; pointer-events: none;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }

    .modal-card {
        background: white; width: 90%; max-width: 500px;
        border-radius: 24px; padding: 32px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        transform: translateY(20px); transition: var(--transition);
        text-align: center;
        color: var(--text-dark);
    }
    .modal-overlay.active .modal-card { transform: translateY(0); }

    .modal-title { font-size: 1.5rem; font-weight: 800; color: var(--umk-blue); margin-bottom: 8px; }
    .vehicle-found-card {
        background: #F8FAFC; border: 1px solid #E2E8F0;
        border-radius: 16px; padding: 20px; margin: 24px 0;
        display: flex; gap: 16px; text-align: left; align-items: center;
    }
    .vehicle-icon { 
        width: 60px; height: 60px; background: #DBEAFE; color: var(--umk-blue);
        border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }

    .btn {
        padding: 12px 24px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; width: 100%; transition: var(--transition); margin-bottom: 8px;
    }
    .btn-primary { background: linear-gradient(135deg, #3B82F6, #2563EB); color: white; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); }
    .btn-danger { background: #EF4444; color: white; }
    .btn-outline { background: transparent; border: 2px solid #E2E8F0; color: var(--text-muted); }
    .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

    /* Toast */
    .toast {
        position: fixed; top: 20px; right: 20px;
        background: white; padding: 16px 24px; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: flex; align-items: center; gap: 12px;
        transform: translateX(150%); transition: var(--transition); z-index: 300;
        border-left: 5px solid var(--umk-yellow); color: var(--text-dark);
    }
    .toast.active { transform: translateX(0); }

    /* Responsive */
    @media (max-width: 992px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); }
        .main-content { margin-left: 0; width: 100%; }
        .header { flex-direction: column; gap: 16px; align-items: flex-start; }
        .search-bar { width: 100%; }
        .search-icon { display: none; } /* Clean up mobile */
    }
  </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9c/Logo_Universitas_Muria_Kudus.png/1200px-Logo_Universitas_Muria_Kudus.png" alt="Logo">
            <div class="brand-text">PARKIR UMK<br><span style="font-size:0.8rem; opacity:0.8; font-weight:500;">CONSOLE</span></div>
        </div>
        <nav class="nav-menu">
            <a href="#" class="nav-item active"><i class="fa-solid fa-grid-2"></i> Dashboard</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-map-location-dot"></i> Denah Live</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Riwayat</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-gear"></i> Pengaturan</a>
        </nav>
        <div class="user-profile">
            <div class="avatar"></div>
            <div style="line-height:1.2">
                <div style="font-weight:700; font-size:0.9rem;">Petugas Jaga</div>
                <div style="font-size:0.75rem; opacity:0.7;">Shift Pagi</div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <button class="action-btn" style="background:transparent; border:none; font-size:1.5rem; display:none;" onclick="toggleSidebar()" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" class="search-input" id="globalSearch" placeholder="Cari Plat Nomor (Enter)..." onkeyup="handleSearch(event)">
            </div>

            <div class="header-actions">
                <button class="action-btn"><i class="fa-regular fa-bell"></i></button>
                <div style="padding: 8px 16px; background: rgba(255,255,255,0.15); border-radius: 20px; font-weight:700; border:1px solid rgba(255,255,255,0.2);">
                    <?= ZONE_ID ?>
                </div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="card">
                <div class="stat-icon"><i class="fa-solid fa-car-side"></i></div>
                <div class="stat-label">Total Masuk</div>
                <div class="stat-value"><?= $stats['total_in'] ?></div>
                <div style="font-size:0.85rem; color:var(--umk-blue);">+12% dari kemarin</div>
            </div>
            <div class="card">
                <div class="stat-icon"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="stat-label">Okupansi</div>
                <div class="stat-value"><?= $stats['occupancy'] ?>%</div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700" style="background:#e2e8f0; height:6px; border-radius:10px; margin-top:5px;">
                    <div style="background:var(--umk-yellow); height:6px; border-radius:10px; width:<?= $stats['occupancy'] ?>%"></div>
                </div>
            </div>
            <div class="card">
                <div class="stat-icon"><i class="fa-solid fa-square-parking"></i></div>
                <div class="stat-label">Slot Kosong</div>
                <div class="stat-value"><?= $stats['vacant'] ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);">Kapasitas Max: <?= MAX_SLOTS ?></div>
            </div>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <div style="padding: 20px; border-bottom: 1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color:var(--umk-blue);">Live Map Monitor</h3>
                <span style="font-size:0.8rem; background:var(--umk-yellow); color:var(--umk-blue); padding:4px 8px; border-radius:6px; font-weight:700;">PHYSICS ENABLED</span>
            </div>
            <div class="map-section" id="mapFrame">
                <div class="map-canvas" id="mapCanvas">
                    </div>
                <div style="position:absolute; bottom:20px; left:20px; background:white; padding:8px 12px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); font-weight:700; color:var(--umk-blue); font-size:0.8rem; pointer-events:none;">
                    <i class="fa-solid fa-hand"></i> Drag to Navigate
                </div>
            </div>
        </div>
    </main>

    <div class="fab-scan" onclick="openScanner()">
        <i class="fa-solid fa-qrcode"></i>
    </div>

    <div class="modal-overlay" id="scannerModal">
        <div class="modal-card">
            <h2 class="modal-title">Scan Tiket</h2>
            <p style="color:var(--text-muted); margin-bottom:20px;">Arahkan kamera ke QR Code User</p>
            <div style="background:#000; width:100%; height:300px; border-radius:16px; overflow:hidden; margin-bottom:20px; position:relative;">
                <div id="reader" style="width:100%; height:100%;"></div>
            </div>
            <button class="btn btn-outline" onclick="closeModal('scannerModal')">Batal</button>
        </div>
    </div>

    <div class="modal-overlay" id="resultModal">
        <div class="modal-card" style="text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2 class="modal-title" style="margin:0;">Kendaraan Ditemukan</h2>
                <button onclick="closeModal('resultModal')" style="background:none; border:none; font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="vehicle-found-card">
                <div class="vehicle-icon"><i class="fa-solid fa-motorcycle"></i></div>
                <div>
                    <h3 style="margin:0; font-size:1.5rem; font-weight:800; color:var(--text-dark);" id="resPlate">...</h3>
                    <div style="color:var(--text-muted); font-size:0.9rem; font-weight:600;" id="resModel">...</div>
                    <div style="font-size:0.85rem; margin-top:4px;">Pemilik: <strong id="resOwner">...</strong></div>
                </div>
            </div>

            <div style="font-weight:700; margin-bottom:10px; color:var(--text-muted); font-size:0.8rem; text-transform:uppercase;">Aksi Cepat</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <button class="btn btn-primary" onclick="sendNotification('MOVE')">Pindahkan</button>
                <button class="btn btn-danger" onclick="sendNotification('EMERGENCY')">Darurat</button>
            </div>
            <button class="btn btn-outline" style="margin-top:8px;" onclick="closeModal('resultModal')">Tutup</button>
        </div>
    </div>

    <div id="toastContainer"></div>

    <script>
        const API_URL = '?api=';
        let scanner = null;
        let currentVehicle = null;

        // --- 1. PHYSICS ENGINE (From Prototype) ---
        class InertiaMap {
            constructor(containerId, canvasId) {
                this.container = document.getElementById(containerId);
                this.canvas = document.getElementById(canvasId);
                this.pos = { x: -50, y: -50 };
                this.vel = { x: 0, y: 0 };
                this.isDragging = false;
                this.friction = 0.92;
                this.lastMouse = { x: 0, y: 0 };
                
                this.initEvents();
                this.animate();
            }

            initEvents() {
                const start = (e) => {
                    this.isDragging = true;
                    this.vel = { x: 0, y: 0 };
                    this.lastMouse = this.getPoint(e);
                    this.canvas.style.cursor = 'grabbing';
                };
                const move = (e) => {
                    if(!this.isDragging) return;
                    e.preventDefault();
                    const curr = this.getPoint(e);
                    this.pos.x += curr.x - this.lastMouse.x;
                    this.pos.y += curr.y - this.lastMouse.y;
                    this.vel = { x: curr.x - this.lastMouse.x, y: curr.y - this.lastMouse.y };
                    this.lastMouse = curr;
                    this.constrain();
                };
                const end = () => { this.isDragging = false; this.canvas.style.cursor = 'grab'; };

                this.container.addEventListener('mousedown', start);
                this.container.addEventListener('touchstart', start);
                window.addEventListener('mousemove', move);
                window.addEventListener('touchmove', move, {passive: false});
                window.addEventListener('mouseup', end);
                window.addEventListener('touchend', end);
            }

            getPoint(e) { return { x: e.clientX || e.touches[0].clientX, y: e.clientY || e.touches[0].clientY }; }

            constrain() {
                const minX = this.container.clientWidth - this.canvas.clientWidth;
                const minY = this.container.clientHeight - this.canvas.clientHeight;
                if(this.pos.x > 0) this.pos.x = 0;
                if(this.pos.y > 0) this.pos.y = 0;
                if(this.pos.x < minX) this.pos.x = minX;
                if(this.pos.y < minY) this.pos.y = minY;
            }

            animate() {
                if(!this.isDragging) {
                    if(Math.abs(this.vel.x) > 0.1 || Math.abs(this.vel.y) > 0.1) {
                        this.pos.x += this.vel.x;
                        this.pos.y += this.vel.y;
                        this.vel.x *= this.friction;
                        this.vel.y *= this.friction;
                        this.constrain();
                    }
                }
                this.canvas.style.transform = `translate3d(${this.pos.x}px, ${this.pos.y}px, 0)`;
                requestAnimationFrame(() => this.animate());
            }
        }

        // --- 2. APP LOGIC ---
        document.addEventListener('DOMContentLoaded', () => {
            loadMap();
            // GSAP Intro
            gsap.from(".sidebar", { x: -100, opacity: 0, duration: 0.8, ease: "power3.out" });
            gsap.from(".card", { y: 30, opacity: 0, duration: 0.6, stagger: 0.1, delay: 0.2 });
            gsap.from(".fab-scan", { scale: 0, rotate: 180, duration: 0.8, delay: 1, ease: "elastic.out(1, 0.5)" });
            
            // Mobile Menu Check
            if(window.innerWidth <= 992) {
                document.getElementById('mobileMenuBtn').style.display = 'block';
            }
        });

        function loadMap() {
            fetch(API_URL + 'map_data', {method: 'POST'})
                .then(r => r.json())
                .then(res => {
                    if(res.status === 'success') renderSlots(res.data);
                    new InertiaMap('mapFrame', 'mapCanvas');
                });
        }

        function renderSlots(slots) {
            const canvas = document.getElementById('mapCanvas');
            canvas.innerHTML = '';
            slots.forEach(slot => {
                const div = document.createElement('div');
                div.className = `slot ${slot.is_occupied ? 'occupied' : 'vacant'}`;
                div.innerHTML = `
                    <div class="slot-id">${slot.id}</div>
                    <div class="slot-meta">${slot.is_occupied ? 'TERISI' : 'KOSONG'}</div>
                    ${slot.is_occupied ? `<div class="slot-plate">${slot.vehicle_plate}</div>` : ''}
                `;
                if(slot.is_occupied) div.onclick = () => searchVehicle(slot.vehicle_plate);
                canvas.appendChild(div);
            });
        }

        function handleSearch(e) {
            if(e.key === 'Enter') searchVehicle(e.target.value);
        }

        function searchVehicle(query) {
            if(!query) return;
            fetch(API_URL + 'search', {
                method: 'POST',
                body: JSON.stringify({query: query})
            })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success' && res.data) {
                    openResultModal(res.data);
                } else {
                    showToast('Kendaraan tidak ditemukan', 'error');
                }
            });
        }

        function openResultModal(data) {
            currentVehicle = data;
            document.getElementById('resPlate').innerText = data.plate;
            document.getElementById('resModel').innerText = `${data.model} - ${data.color}`;
            document.getElementById('resOwner').innerText = data.owner;
            
            closeModal('scannerModal');
            const modal = document.getElementById('resultModal');
            modal.classList.add('active');
        }

        function sendNotification(type) {
            if(!currentVehicle) return;
            fetch(API_URL + 'notify', { method: 'POST', body: JSON.stringify({type}) })
            .then(r => r.json())
            .then(res => {
                showToast(res.message);
                closeModal('resultModal');
            });
        }

        // --- 3. UI UTILS ---
        function openScanner() {
            document.getElementById('scannerModal').classList.add('active');
            scanner = new Html5Qrcode("reader");
            scanner.start({ facingMode: "environment" }, { fps: 10 }, 
                (decodedText) => { searchVehicle(decodedText); }
            );
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            if(id === 'scannerModal' && scanner) {
                scanner.stop().then(() => scanner.clear());
            }
        }

        function showToast(msg, type='success') {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.borderLeftColor = type === 'error' ? '#EF4444' : 'var(--umk-yellow)';
            toast.innerHTML = `
                <i class="fa-solid ${type==='error'?'fa-circle-exclamation':'fa-check-circle'}" 
                   style="color:${type==='error'?'#EF4444':'var(--umk-blue)'}"></i>
                <span>${msg}</span>
            `;
            document.getElementById('toastContainer').appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('active'));
            setTimeout(() => {
                toast.classList.remove('active');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>