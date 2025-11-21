<?php
// FILE: dashboard_petugas_parkir.php (FINAL - ZXING-JS INTEGRATION)
session_start();

// PERBAIKAN: Include config dengan path absolut dan error handling
$config_path = __DIR__ . '/config.php';

if (!file_exists($config_path)) {
    // Coba cek di parent directory
    $config_path = __DIR__ . '/../config.php';
    
    if (!file_exists($config_path)) {
        die("❌ Error: File config.php tidak ditemukan!<br><br>" .
            "Lokasi yang dicoba:<br>" .
            "1. " . __DIR__ . '/config.php<br>' .
            "2. " . __DIR__ . '/../config.php<br><br>' .
            "Silakan copy file config.php ke salah satu lokasi di atas.");
    }
}

include_once $config_path;

// Cek koneksi database
if (!isset($conn) || $conn->connect_error) {
    die("❌ Error: Koneksi database gagal!<br>" .
        ($conn->connect_error ?? 'Variable $conn tidak ditemukan di config.php'));
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas Parkir</title>
    <link rel="stylesheet" href="Css/dashboard_layout.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Reset & Variables */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root { 
            --primary-blue: #114A9B; 
            --primary-yellow: #FBCE00; 
            --border-color: #111827; 
            --bg-content: #FFFFFF; 
            --success: #2ECC71;
            --danger: #E74C3C;
            --gray: #f7f7f7;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Container */
        .container { 
            max-width: 500px; 
            margin: 50px auto; 
            padding: 30px; 
            background: var(--bg-content); 
            border: 2px solid var(--border-color); 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        /* Header */
        h1 { 
            color: var(--primary-blue); 
            border-bottom: 3px solid var(--primary-yellow); 
            padding-bottom: 15px; 
            margin-bottom: 10px;
            font-size: 1.6rem; 
            text-align: center; 
        }
        
        .welcome-text {
            text-align: center; 
            font-weight: 600;
            color: #555;
            margin-bottom: 25px;
            font-size: 1rem;
        }
        
        /* Form Groups */
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 8px; 
            font-size: 0.95rem; 
            color: #333; 
        }
        
        .form-group input[type="text"],
        .form-group input[readonly], 
        .form-group select { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #ddd; 
            border-radius: 8px; 
            font-size: 1rem; 
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-blue);
        }
        
        .form-group input[readonly], 
        .form-group select:disabled { 
            background-color: var(--gray); 
            color: #777;
            cursor: not-allowed;
        }
        
        .form-group select:enabled {
            background-color: white;
            cursor: pointer;
        }
        
        /* Scan Input Group */
        .scan-input-group { 
            display: flex; 
            gap: 10px; 
            align-items: stretch;
        }
        
        .scan-input-group input {
            flex: 1;
        }
        
        /* Buttons */
        .btn-scan, .btn-camera { 
            padding: 12px 20px; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            font-size: 1rem; 
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-camera { 
            background: var(--primary-yellow); 
            color: var(--primary-blue); 
            min-width: 150px;
            box-shadow: 0 4px 15px rgba(251, 206, 0, 0.3);
        }
        
        .btn-camera:hover {
            background: #e0b700;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(251, 206, 0, 0.4);
        }
        
        .btn-camera:active {
            transform: translateY(0);
        }
        
        .btn-scan-action { 
            display: flex; 
            justify-content: space-between; 
            gap: 15px; 
            margin-top: 25px; 
        }
        
        .btn-scan {
            flex: 1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-masuk { 
            background: var(--success); 
            color: white; 
        }
        
        .btn-masuk:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
        }
        
        .btn-keluar { 
            background: var(--danger); 
            color: white; 
        }
        
        .btn-keluar:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        
        .btn-scan:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Scanner Container */
        #scanner-container { 
            width: 100%; 
            height: 280px; 
            background: #000; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            overflow: hidden; 
            position: relative; 
            border: 3px solid var(--primary-yellow);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        #scanner-video { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        
        /* Scanner Overlay */
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 3px solid var(--primary-yellow);
            border-radius: 10px;
            pointer-events: none;
            z-index: 100;
        }
        
        .scanner-overlay::before,
        .scanner-overlay::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid var(--primary-yellow);
        }
        
        .scanner-overlay::before {
            top: -3px;
            left: -3px;
            border-right: none;
            border-bottom: none;
        }
        
        .scanner-overlay::after {
            bottom: -3px;
            right: -3px;
            border-left: none;
            border-top: none;
        }
        
        /* Loading Animation */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--primary-yellow);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
                padding: 20px;
            }
            
            h1 {
                font-size: 1.3rem;
            }
            
            .scan-input-group {
                flex-direction: column;
            }
            
            .btn-camera {
                width: 100%;
            }
            
            .btn-scan-action {
                flex-direction: column;
            }
            
            #scanner-container {
                height: 240px;
            }
        }
    </style>
    <script src="zxing.min.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
</head>
<body>
    <div class="container">
        <h1>🅿️ Pencatatan Parkir UMK</h1>
        <p class="welcome-text">Halo, <?php echo htmlspecialchars($nama_petugas); ?>! 👋</p>
        
        <div id="scanner-container" style="display: none;">
            <video id="scanner-video" autoplay="true" muted="true" playsinline></video>
            <div class="scanner-overlay"></div>
        </div>
        
        <form id="scanForm">
            <div class="form-group">
                <label for="user_code">🔍 Kode Pengguna (QR/Barcode)</label>
                <div class="scan-input-group">
                    <input 
                        type="text" 
                        id="user_code" 
                        name="user_code" 
                        placeholder="Scan atau Input Kode Pengguna" 
                        autofocus 
                        required
                    >
                    <button type="button" class="btn-camera" id="toggleScannerBtn">
                        📷 Scan
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="plat_nomor">🚗 Plat Nomor Kendaraan</label>
                <select id="plat_nomor" name="plat_nomor" required disabled> 
                    <option value="">-- Scan/Input Kode Pengguna Dahulu --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="jenis_kendaraan">📋 Jenis Kendaraan</label>
                <input 
                    type="text" 
                    id="jenis_kendaraan" 
                    name="jenis_kendaraan" 
                    placeholder="Otomatis terisi setelah pilih plat" 
                    readonly 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="warna_kendaraan">🎨 Warna Kendaraan</label>
                <input 
                    type="text" 
                    id="warna_kendaraan" 
                    name="warna_kendaraan" 
                    placeholder="Otomatis terisi setelah pilih plat" 
                    readonly 
                    required
                >
            </div>
            
            <div class="btn-scan-action">
                <button type="button" class="btn-scan btn-masuk" id="btnMasuk">
                    ✅ Catat Masuk
                </button>
                <button type="button" class="btn-scan btn-keluar" id="btnKeluar">
                    🚪 Catat Keluar
                </button>
            </div>
        </form>
    </div>
    
    <script src="scanner_logic.js"></script> 
</body>
</html>