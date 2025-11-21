<?php
// FILE: dashboard_petugas_parkir.php (FINAL V2.0 - KAPASITAS BERBASIS)
session_start();
include 'config.php'; 

// === PROTEKSI SESI KETAT ===
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    session_unset();
    session_destroy();
    header('Location: login_petugas.php'); 
    exit;
}

// === PENGAMBILAN DATA & KPI ===
$nama_petugas = $_SESSION['nama'] ?? 'Petugas Parkir';
$petugas_id = $_SESSION['user_id'];
$total_on_site = 0; 
$total_transaction_today = 0;
$motor_on_site = 0;
$mobil_on_site = 0;
// Variabel baru untuk menampung detail area dan slot yang tersedia
$area_kosong_details = []; 
$active_parkings = [];

if (isset($conn) && !$conn->connect_error) {
    
    // 1. Total Kendaraan On-Site (LOGIKA TIDAK BERUBAH)
    $sql_on_site = "SELECT COUNT(lp.id) AS total, 
                    SUM(CASE WHEN k.jenis = 'motor' THEN 1 ELSE 0 END) AS motor,
                    SUM(CASE WHEN k.jenis = 'mobil' THEN 1 ELSE 0 END) AS mobil
                    FROM log_parkir lp 
                    JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor 
                    WHERE lp.status = 'masuk'";
    
    $result_on_site = $conn->query($sql_on_site);
    if ($result_on_site) {
        $data = $result_on_site->fetch_assoc();
        $total_on_site = $data['total'] ?? 0;
        $motor_on_site = $data['motor'] ?? 0;
        $mobil_on_site = $data['mobil'] ?? 0;
    }

    // 2. Total Transaksi Hari Ini (LOGIKA TIDAK BERUBAH)
    $today = date('Y-m-d');
    $sql_today = "SELECT COUNT(id) AS total FROM log_parkir WHERE DATE(waktu_masuk) = ? AND status = 'masuk'";
    
    if ($stmt_today = $conn->prepare($sql_today)) {
        $stmt_today->bind_param('s', $today);
        $stmt_today->execute();
        $result_today = $stmt_today->get_result()->fetch_assoc();
        $total_transaction_today = $result_today['total'] ?? 0;
        $stmt_today->close();
    }
    
    // 3. V2.0 BARU: AMBIL DATA AREA PARKIR YANG MASIH PUNYA SLOT KOSONG
    // Menggantikan SQL lama yang bergantung pada kolom `status`
    $sql_kosong_v2 = "
        SELECT 
            ap.kode_area, 
            (ap.kapasitas_maks - COUNT(lp.id)) AS available_slots
        FROM 
            area_parkir ap
        LEFT JOIN 
            log_parkir lp ON ap.kode_area = lp.kode_area AND lp.status = 'masuk'
        GROUP BY 
            ap.kode_area, ap.kapasitas_maks
        HAVING 
            available_slots > 0  -- Hanya area yang punya slot > 0
        ORDER BY 
            available_slots DESC
    ";
    
    $total_area_kosong_count = 0;
    if ($stmt_kosong = $conn->prepare($sql_kosong_v2)) {
        $stmt_kosong->execute();
        $result_kosong = $stmt_kosong->get_result();
        
        while ($row = $result_kosong->fetch_assoc()) {
            // Simpan detail untuk ditampilkan di KPI dan digunakan di Modal
            $area_kosong_details[] = [
                'kode' => $row['kode_area'],
                'slots' => $row['available_slots'],
                // Format display: A (5)
                'display' => "{$row['kode_area']} ({$row['available_slots']})" 
            ];
            $total_area_kosong_count += $row['available_slots']; // Hitung total slot
        }
        $stmt_kosong->close();
    }
    
    $total_area_kosong = $total_area_kosong_count;
    // Format list area untuk display
    $list_area_kosong = implode(', ', array_column($area_kosong_details, 'display'));

    // 4. AMBIL DAFTAR KENDARAAN AKTIF (LOGIKA TIDAK BERUBAH)
    $sql_active = "SELECT lp.id AS log_id, lp.plat_nomor, lp.kode_area, lp.waktu_masuk, k.jenis, k.user_id, u.nama
                   FROM log_parkir lp 
                   JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
                   LEFT JOIN users u ON k.user_id = u.id
                   WHERE lp.status = 'masuk'
                   ORDER BY lp.waktu_masuk DESC";
    
    $result_active = $conn->query($sql_active);
    if ($result_active) {
        while ($row = $result_active->fetch_assoc()) {
            $active_parkings[] = $row;
        }
    }
    
    $conn->close();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard Petugas - <?php echo htmlspecialchars($nama_petugas); ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* === DESAIN TINGKAT TINGGI UNTUK DASHBOARD PETUGAS === */
        :root {
            --primary-color: #114A9B; /* Biru UMK */
            --secondary-color: #FBCE00; /* Kuning UMK */
            --bg-body: #e8f1ff; /* Latar belakang lebih lembut */
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08); /* Bayangan yang lebih dalam dan halus */
            --success-color: #28a745;
            --info-color: #007bff; /* Biru terang untuk info/motor */
            --danger-color: #dc3545; /* Merah untuk logout */
            --warning-color: #ffc107; /* Kuning/Oranye untuk area kosong */
        }
        body { 
            background-color: var(--bg-body); 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            line-height: 1.6;
        }
        .container { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 25px;
        }
        
        .header-dashboard { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            padding: 15px 0;
            border-bottom: 3px solid var(--secondary-color); 
        }
        .header-dashboard h1 { 
            color: var(--primary-color); 
            font-size: 2.2rem; 
            font-weight: 800; 
        }
        .welcome-info { 
            text-align: right; 
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* TOMBOL LOGOUT */
        .btn-logout {
            background: var(--danger-color); 
            color: white; 
            text-decoration: none; 
            padding: 10px 20px;
            border-radius: 50px; 
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.4);
            display: flex;
            align-items: center;
        }
        .btn-logout i { margin-right: 8px; }
        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        /* KPI GRID ENHANCEMENT */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 25px; 
            margin-bottom: 40px;
        }
        .kpi-card {
            background: white;
            border-radius: 15px; 
            padding: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-left: 5px solid transparent;
            flex-direction: column; 
            align-items: flex-start;
        }
        .kpi-card .kpi-main-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .kpi-icon { 
            font-size: 2.8rem; 
            padding: 18px; 
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kpi-info {
            text-align: right;
        }
        .kpi-value { 
            font-size: 2.5rem; 
            font-weight: 800; 
            color: var(--primary-color); 
        }
        .kpi-label { 
            font-size: 0.95rem; 
            color: #777; 
            margin-top: 5px; 
        }
        
        /* KPI Colors */
        .kpi-card.total { border-left-color: var(--primary-color); }
        .kpi-card.motor { border-left-color: var(--info-color); }
        .kpi-card.mobil { border-left-color: var(--success-color); }
        .kpi-card.today { border-left-color: #6f42c1; }
        .kpi-card.kosong { border-left-color: var(--secondary-color); } 

        .kpi-card.total .kpi-icon { color: var(--primary-color); background: rgba(17, 74, 155, 0.1); }
        .kpi-card.kosong .kpi-icon { color: var(--secondary-color); background: rgba(251, 206, 0, 0.1); } 

        .area-list { 
            font-size: 0.9rem; 
            color: #555; 
            margin-top: 10px; 
            padding-top: 10px;
            border-top: 1px dashed #eee;
            width: 100%;
        }
        
        /* MAIN ACTION CARD ENHANCEMENT */
        .main-action-card {
            background: linear-gradient(135deg, var(--primary-color), #0d3878);
            border-radius: 20px; 
            padding: 50px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(17, 74, 155, 0.5);
        }
        .btn-scan-main {
            background: var(--secondary-color);
            color: var(--primary-color);
            padding: 18px 50px;
            font-size: 1.6rem; 
            font-weight: 900;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        /* === ACTIVE PARKING TABLE (NEW) === */
        .active-parking-section {
            background: white;
            border-radius: 15px; 
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-top: 30px;
        }
        .active-parking-section h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-weight: 700;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .active-parking-table {
            width: 100%;
            border-collapse: collapse;
        }
        .active-parking-table th, .active-parking-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .active-parking-table th {
            background-color: #f8f8f8;
            color: #555;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .btn-pindah {
            /* Warna yang lebih soft untuk aksi manajemen */
            background: #ffe082; /* Light yellow */
            color: #424242;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 0.9rem;
        }
        .btn-pindah:hover {
            background: var(--warning-color);
        }
        
        /* Modal Style */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe; 
            margin: 15% auto; 
            padding: 25px; 
            border: 1px solid #ddd; 
            width: 90%; 
            max-width: 450px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            animation-name: animatetop;
            animation-duration: 0.4s;
        }
        @keyframes animatetop {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }
        .close {
            color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;
        }
        .close:hover, .close:focus {
            color: #000; text-decoration: none; cursor: pointer;
        }

        /* MEDIA QUERY */
        @media (max-width: 768px) {
            .active-parking-table {
                font-size: 0.8rem;
                display: block;
                overflow-x: auto; 
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header-dashboard">
            <h1>Dashboard Petugas Parkir</h1>
            <div class="welcome-info">
                <span>
                    Selamat Bertugas, **<?php echo htmlspecialchars($nama_petugas); ?>** (ID: <?php echo $petugas_id; ?>)
                </span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <?php 
        // Tambahkan blok untuk menampilkan pesan notifikasi petugas (dari process_pindah_area.php)
        if (isset($_SESSION['notif_petugas'])): 
            $notif = $_SESSION['notif_petugas'];
            $color = ($notif['type'] === 'success') ? '#28a745' : '#dc3545';
            $icon = ($notif['type'] === 'success') ? 'fa-check-circle' : 'fa-times-circle';
        ?>
            <div style="
                padding: 15px; 
                margin-bottom: 25px; 
                border-radius: 8px; 
                background-color: <?php echo ($notif['type'] === 'success') ? '#d4edda' : '#f8d7da'; ?>;
                color: <?php echo $color; ?>;
                border: 1px solid <?php echo ($notif['type'] === 'success') ? '#c3e6cb' : '#f5c6cb'; ?>;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            ">
                <i class="fas <?php echo $icon; ?>" style="font-size: 1.2em;"></i>
                <?php echo htmlspecialchars($notif['text']); ?>
            </div>
        <?php 
            unset($_SESSION['notif_petugas']);
        endif; 
        ?>

        <div class="kpi-grid">
            
            <div class="kpi-card kosong">
                <div class="kpi-main-info">
                    <i class="fas fa-sign-in-alt kpi-icon"></i>
                    <div class="kpi-info">
                        <div class="kpi-value"><?php echo number_format($total_area_kosong, 0, ',', '.'); ?></div>
                        <div class="kpi-label">Total Slot Kosong Tersedia</div>
                    </div>
                </div>
                <p class="area-list">
                    Detail: <strong><?php echo empty($list_area_kosong) ? 'SEMUA AREA PENUH' : htmlspecialchars($list_area_kosong); ?></strong>
                </p>
            </div>

            <div class="kpi-card total"><div class="kpi-main-info"><i class="fas fa-parking kpi-icon"></i><div class="kpi-info"><div class="kpi-value"><?php echo number_format($total_on_site, 0, ',', '.'); ?></div><div class="kpi-label">Total Kendaraan On-Site</div></div></div></div>
            <div class="kpi-card motor"><div class="kpi-main-info"><i class="fas fa-motorcycle kpi-icon"></i><div class="kpi-info"><div class="kpi-value"><?php echo number_format($motor_on_site, 0, ',', '.'); ?></div><div class="kpi-label">Sepeda Motor Terparkir</div></div></div></div>
            <div class="kpi-card mobil"><div class="kpi-main-info"><i class="fas fa-car kpi-icon"></i><div class="kpi-info"><div class="kpi-value"><?php echo number_format($mobil_on_site, 0, ',', '.'); ?></div><div class="kpi-label">Mobil Terparkir</div></div></div></div>
            <div class="kpi-card today"><div class="kpi-main-info"><i class="fas fa-clipboard-list kpi-icon"></i><div class="kpi-info"><div class="kpi-value"><?php echo number_format($total_transaction_today, 0, ',', '.'); ?></div><div class="kpi-label">Pencatatan Hari Ini</div></div></div></div>

        </div>

        <div class="main-action-card">
            <h2>Siap Melayani Pencatatan Parkir?</h2>
            <a href="Scan_qrcode.php" class="btn-scan-main">
                <i class="fas fa-qrcode"></i> MULAI SCAN (MASUK/KELUAR)
            </a>
        </div>
        
        <div class="active-parking-section">
            <h2><i class="fas fa-list-check"></i> Manajemen Kendaraan Aktif (<?php echo count($active_parkings); ?>)</h2>
            
            <?php if (empty($active_parkings)): ?>
                <p style="text-align: center; color: #777; padding: 20px;">Tidak ada kendaraan yang sedang terparkir saat ini.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="active-parking-table">
                    <thead>
                        <tr>
                            <th>Plat Nomor</th>
                            <th>Pemilik</th>
                            <th>Area Saat Ini</th>
                            <th>Jenis</th>
                            <th>Waktu Masuk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_parkings as $parkir): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($parkir['plat_nomor']); ?></strong></td>
                            <td><?php echo htmlspecialchars($parkir['nama'] ?? 'Tamu'); ?></td>
                            <td><span style="font-weight: bold; color: var(--primary-color);"><?php echo htmlspecialchars($parkir['kode_area']); ?></span></td>
                            <td><?php echo ucfirst(htmlspecialchars($parkir['jenis'])); ?></td>
                            <td><?php echo date('d/m H:i', strtotime($parkir['waktu_masuk'])); ?></td>
                            <td>
                                <button 
                                    class="btn-pindah" 
                                    onclick="showMoveModal(<?php echo $parkir['log_id']; ?>, '<?php echo htmlspecialchars($parkir['plat_nomor']); ?>', '<?php echo htmlspecialchars($parkir['kode_area']); ?>', <?php echo $parkir['user_id']; ?>)">
                                    Pindahkan <i class="fas fa-arrow-right-arrow-left"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <div id="move-modal" class="modal">
        <div class="modal-content">
            <span onclick="document.getElementById('move-modal').style.display='none'" class="close">&times;</span>
            <h2 style="color: var(--primary-color);">Pindahkan Kendaraan</h2>
            <form id="move-form" action="process_pindah_area.php" method="POST">
                <input type="hidden" name="log_id" id="modal_log_id">
                <input type="hidden" name="plat_nomor" id="modal_plat_nomor">
                <input type="hidden" name="area_lama" id="modal_area_lama">
                <input type="hidden" name="user_id" id="modal_user_id">
                
                <p>Kendaraan: <strong id="display_plat_nomor"></strong></p>
                <p>Dari Area: <strong id="display_area_lama"></strong></p>

                <label for="modal_area_baru" style="display: block; margin-top: 15px;">Pilih Area Tujuan Baru (Area Tersedia):</label>
                <select name="area_baru" id="modal_area_baru" required style="width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #ccc;">
                    <option value="" disabled selected>Pilih Area</option>
                    <?php 
                    // Opsi diisi dengan data area yang masih punya slot (available_slots > 0)
                    // Menggunakan $area_kosong_details yang baru
                    foreach ($area_kosong_details as $area) {
                        // Pastikan hanya kode_area yang dikirim
                        echo "<option value='".htmlspecialchars($area['kode'])."'>Area ".htmlspecialchars($area['display'])."</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="btn-pindah" style="width: 100%; padding: 12px; background-color: var(--success-color); color: white;">Konfirmasi Pindah Area</button>
            </form>
        </div>
    </div>

    <script>
        function showMoveModal(logId, platNomor, areaLama, userId) {
            // Setel nilai ke hidden fields
            document.getElementById('modal_log_id').value = logId;
            document.getElementById('modal_plat_nomor').value = platNomor;
            document.getElementById('modal_area_lama').value = areaLama;
            document.getElementById('modal_user_id').value = userId;

            // Tampilkan informasi di modal
            document.getElementById('display_plat_nomor').textContent = platNomor;
            document.getElementById('display_area_lama').textContent = areaLama;
            
            // Atur ulang pilihan select
            document.getElementById('modal_area_baru').selectedIndex = 0;

            // Tampilkan modal
            document.getElementById('move-modal').style.display = 'block';
        }
        
        // Tutup modal jika user mengklik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('move-modal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>