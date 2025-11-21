<?php
// FILE: Scan_qrcode.php (Menggunakan struktur yang Anda sukai, dengan eksternal JS)
session_start();
include 'config.php'; 

// === 1. PROTEKSI SESI ===
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    session_destroy();
    header('Location: login_petugas.php'); 
    exit;
}

// === 2. AMBIL DATA PETUGAS & KPI ===
$nama_petugas = $_SESSION['nama'] ?? 'Petugas Parkir';
$petugas_id = $_SESSION['user_id'];
$total_on_site = 0; 

// Ambil Status Kendaraan Saat Ini
if (isset($conn) && !$conn->connect_error) {
    $sql_on_site = "SELECT COUNT(id) AS total_on_site FROM log_parkir WHERE status = 'masuk'"; 
    $result_on_site = $conn->query($sql_on_site);
    if ($result_on_site) {
        $data = $result_on_site->fetch_assoc();
        $total_on_site = $data['total_on_site'] ?? 0;
    }
}
// Tutup koneksi (Opsional, tapi Good Practice)
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Parkir - <?php echo htmlspecialchars($nama_petugas); ?></title> 
    <link rel="stylesheet" href="Css/dashboard_layout.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root { 
            --primary-blue: #114A9B; 
            --primary-yellow: #FBCE00; 
            --border-color: #111827; 
            --bg-content: #FFFFFF; 
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; } 
        
        /* === 1. TOMBOL KEMBALI (Lebih Rapi) === */
        .back-link-container {
            max-width: 500px; 
            margin: 30px auto 15px; 
            padding: 0 15px;
        }
        .btn-back-dashboard {
            background: #6c757d; 
            color: white;
            padding: 10px 15px;
            border-radius: 8px; /* Lebih membulat */
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15); /* Bayangan halus */
        }
        .btn-back-dashboard:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .container { 
            max-width: 500px; 
            margin: 0 auto 50px; 
            padding: 30px; 
            background: var(--bg-content); 
            border: none; /* Hilangkan border hitam */
            border-radius: 12px; /* Lebih membulat */
            box-shadow: var(--shadow-light); /* Gunakan bayangan halus */
        }
        h1 { 
            color: var(--primary-blue); 
            border-bottom: 2px solid #eee; 
            padding-bottom: 15px; 
            margin-top: 0; 
            font-size: 1.8rem; 
            text-align: center; 
            font-weight: 700;
        }
        p { text-align: center; font-weight: 500; font-size: 1.1rem; margin-bottom: 25px;}
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; color: #444; }
        
        /* Input & Select Styles (Lebih Rapi) */
        .form-group input[readonly], 
        .form-group select,
        .scan-input-group input[type="text"] { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; /* Lebih membulat */
            box-sizing: border-box; 
            font-size: 1rem; 
            background-color: white; /* Input field background putih */
            color: #333;
            transition: border-color 0.2s;
        }
        .form-group input[readonly], .form-group select {
            background-color: #f7f7f7;
        }
        .scan-input-group { display: flex; gap: 10px; }

        /* === 2. TOMBOL SCAN KAMERA (Lebih Rapi) === */
        .btn-camera { 
            background: var(--primary-yellow); 
            color: var(--primary-blue); 
            font-size: 1rem; 
            width: 150px; 
            border: none;
            border-radius: 8px; /* Lebih membulat */
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            padding: 12px 10px;
            box-shadow: 0 2px 5px rgba(251, 206, 0, 0.4); /* Bayangan Kuning */
        }
        .btn-camera:hover {
            background: #FFD740;
            transform: translateY(-1px);
        }

        /* === 3. TOMBOL CATAT MASUK/KELUAR (Sangat Rapi & Menarik) === */
        .btn-scan-action { display: flex; justify-content: space-between; gap: 15px; margin-top: 25px; }
        
        .btn-scan { 
            padding: 15px 20px; /* Padding lebih besar */
            border: none; 
            border-radius: 10px; /* Lebih membulat */
            font-weight: 700; 
            cursor: pointer; 
            width: 50%; 
            font-size: 1.1rem; 
            transition: all 0.3s ease; 
        }

        .btn-masuk { 
            background: #2ECC71; /* Hijau */
            color: white; 
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.4); 
        }
        .btn-masuk:hover { 
            background: #27ae60; 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.6); 
        }

        .btn-keluar { 
            background: #E74C3C; /* Merah */
            color: white; 
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.4); 
        }
        .btn-keluar:hover { 
            background: #c0392b; 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.6); 
        }

        #scanner-container { width: 100%; height: 250px; background: #333; border-radius: 10px; margin-bottom: 20px; overflow: hidden; position: relative; z-index: 5; }
        #scanner-video { width: 100%; height: 100%; object-fit: cover; z-index: 10; position: relative; }

        /* Mobile Adjustments */
        @media (max-width: 600px) {
            .container { padding: 20px 15px; margin: 0 auto 30px; border-radius: 0; }
            .scan-input-group { flex-direction: column; gap: 8px; }
            .btn-camera { width: 100%; font-size: 1.1rem; padding: 15px; }
            .btn-scan-action { flex-direction: column; gap: 10px; }
            .btn-scan { width: 100%; padding: 18px; font-size: 1.2rem; }
            .back-link-container { margin: 20px auto 10px; padding: 0 15px; }
        }
    </style>
    <script src="Js/zxing.min.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
</head>
<body>
    
    <div class="back-link-container">
        <a href="dashboard_petugas_parkir.php" class="btn-back-dashboard">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
    
    <div class="container">
        <h1>Pencatatan Parkir UMK</h1>
        
        <div id="scanner-container" style="display: none;">
            <video id="scanner-video" autoplay="true" muted="true" playsinline></video>
        </div>
        <form id="scanForm">
            <div class="form-group">
                <label for="user_code">Kode Pengguna (QR/Barcode)</label>
                <div class="scan-input-group">
                    <input type="text" id="user_code" name="user_code" placeholder="Scan atau Input Kode Pengguna" autofocus required>
                    <button type="button" class="btn-camera" id="toggleScannerBtn">
                        <i class="fa-solid fa-camera"></i> Scan Kamera
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="plat_nomor">Plat Nomor Kendaraan</label>
                <select id="plat_nomor" name="plat_nomor" required disabled> 
                    <option value="">-- Scan/Input Kode Pengguna Dahulu --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="jenis_kendaraan">Jenis Kendaraan</label>
                <input 
                    type="text" 
                    id="jenis_kendaraan" 
                    name="jenis_kendaraan" 
                    placeholder="Jenis Kendaraan" 
                    readonly 
                    required
                >
            </div>
            <div class="form-group">
                <label for="warna_kendaraan">Warna Kendaraan</label>
                <input 
                    type="text" 
                    id="warna_kendaraan" 
                    name="warna_kendaraan" 
                    placeholder="Warna Kendaraan" 
                    readonly 
                    required
                >
            </div>
            
            <input type="hidden" name="petugas_id" value="<?php echo htmlspecialchars($petugas_id); ?>">
            <input type="hidden" name="action" id="action_type">

            <div class="btn-scan-action">
                <button type="button" class="btn-scan btn-masuk" id="btnMasuk">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> CATAT MASUK
                </button>
                <button type="button" class="btn-scan btn-keluar" id="btnKeluar">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> CATAT KELUAR
                </button>
            </div>
        </form>
    </div>
    
    <script src="Js/scanner_logic.js"></script> 
</body>
</html>