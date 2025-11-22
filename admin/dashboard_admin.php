<?php
// ===== dashboard-admin.php (FINAL GABUNGAN - Realtime KPI & Fitur CRUD Simulasi) =====
session_start();
// Pastikan file config.php ada dan berisi koneksi MySQLi
include('../config.php'); 

// --- PROTEKSI SESI KRUSIAL ---
// Jika sesi tidak ada ATAU role BUKAN 'admin', tendang kembali ke login admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin')) {
    session_destroy();
    header('Location: login_admin.php'); 
    exit;
}
// =============================

// Data admin dari sesi
$_SESSION['username'] = $_SESSION['username'] ?? 'Admin Dashboard';
$nama_admin = $_SESSION['nama'] ?? $_SESSION['username'];
$is_admin_utama = ($_SESSION['username'] === 'Admin UMK');

// ===================================
// --- QUERY KPI (REAL DATABASE DATA) ---
// ===================================

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// 1. Total Kendaraan Saat Ini (Status 'parkir')
$sql_on_site = "SELECT COUNT(id) AS total_on_site FROM log_parkir WHERE status = 'parkir'";
$result_on_site = $conn->query($sql_on_site);
$total_on_site = $result_on_site ? ($result_on_site->fetch_assoc()['total_on_site'] ?? 0) : 0;

// 2. Total Pengguna Terdaftar (role non-admin & non-petugas)
$sql_users = "SELECT COUNT(id) AS total_users FROM users WHERE role != 'admin' AND role != 'petugas'";
$result_users = $conn->query($sql_users);
$total_users = $result_users ? ($result_users->fetch_assoc()['total_users'] ?? 0) : 0;

// 3. Total Petugas Parkir Terdaftar
$sql_petugas = "SELECT COUNT(id) AS total_petugas FROM users WHERE role = 'petugas'";
$result_petugas = $conn->query($sql_petugas);
$total_petugas = $result_petugas ? ($result_petugas->fetch_assoc()['total_petugas'] ?? 0) : 0;

// 4. Log Parkir Terbaru (10 Log Terakhir)
$sql_log_db = "SELECT * FROM log_parkir ORDER BY waktu_masuk DESC LIMIT 10";
$result_log_db = $conn->query($sql_log_db);

// ====================================
// --- DATA SIMULASI (Dipertahankan untuk Fitur Kelola Akun/Chart) ---
// ====================================
$akun_pengguna = [
  ["id" => 1, "nama" => "Budi Santoso", "role" => "Mahasiswa", "status" => "Aktif"],
  ["id" => 2, "nama" => "Dosen Rahmat", "role" => "Dosen", "status" => "Aktif"],
  ["id" => 3, "nama" => "Andi Pratama", "role" => "Tamu", "status" => "Nonaktif"],
  ["id" => 4, "nama" => "Slamet", "role" => "Tukang Parkir", "status" => "Aktif"],
];
$kendaraan_per_hari = [
  "Senin" => 120, "Selasa" => 135, "Rabu" => 150, "Kamis" => 145, 
  "Jumat" => 160, "Sabtu" => 180, "Minggu" => 175
];
$kapasitas_terpakai = 85; // %
$area_list = ["Utama", "Selatan", "Barat", "Timur"]; 

// Fungsi catat log (ke file log_aktivitas.txt)
function catat_log($aksi, $nama_pengguna) {
  $log_file = "log_aktivitas.txt";
  $waktu = date("Y-m-d H:i:s");
  $admin_nama_log = $_SESSION['nama'] ?? 'Admin Tidak Diketahui'; 
  $data = "[$waktu] $aksi - Oleh: $admin_nama_log - Target: $nama_pengguna\n";
  file_put_contents($log_file, $data, FILE_APPEND);
}

// Proses simulasi CRUD
if (isset($_POST['aksi'])) {
  $aksi = $_POST['aksi'];
  $nama = $_POST['nama'];
  
  if ($aksi == "Tambah") { catat_log("Menambah akun baru", $nama); }
  elseif ($aksi == "Edit") { catat_log("Mengedit detail akun", $nama); }
  elseif ($aksi == "Nonaktifkan") { catat_log("Menonaktifkan status akun", $nama); }
  elseif ($aksi == "Hapus" && $is_admin_utama) { catat_log("Menghapus akun", $nama); }
}

// Tutup koneksi setelah selesai
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin UMK</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  /* --- CSS UTAMA (DARI FILE SEBELUMNYA) --- */
  :root {
      --umk-blue: #0d47a1; 
      --umk-yellow: #ffc107; 
      --bg-light: #f4f6f9; 
      --text-dark: #333;
  }

  body {
      background-color: var(--bg-light);
      font-family: 'Segoe UI', sans-serif;
      color: var(--text-dark);
      overflow-x: hidden;
  }

  /* SIDEBAR */
  .dashboard-container { display: flex; min-height: 100vh; }
  .sidebar { 
      background-color: var(--umk-blue); color: white; width: 250px;
      padding: 20px 0; position: sticky; top: 0;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
  }
  .sidebar h4 { text-align: center; margin-bottom: 30px; color: var(--umk-yellow); }
  .sidebar a { 
      display: block; color: rgba(255,255,255,0.8); text-decoration: none; 
      padding: 15px 20px; margin-bottom: 5px; transition: 0.3s;
      border-left: 4px solid transparent;
  }
  .sidebar a:hover, .sidebar a.active { 
      background: rgba(255,255,255,0.1); color: white;
      border-left-color: var(--umk-yellow); 
  }

  /* MAIN CONTENT */
  .main { flex-grow: 1; padding: 30px; }
  .content-box { 
      background: white; border-radius: 12px; padding: 25px; 
      box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px;
  }

  /* STATS CARDS */
  .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-bottom:30px;}
  .card-stat { 
      border: none; border-radius: 12px; padding: 20px; color: white;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s;
  }
  .card-stat:hover { transform: translateY(-3px); }

  .card-blue { background: linear-gradient(45deg, #0d47a1, #1565c0); }
  .card-yellow { background: linear-gradient(45deg, #ffc107, #ffcd38); color: var(--text-dark); }
  .card-green { background: linear-gradient(45deg, #2e7d32, #43a047); }
  .card-red { background: linear-gradient(45deg, #c62828, #e53935); }

  /* TABEL & TOMBOL */
  th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; font-size: 0.95rem; }
  th { background-color: #f7f7f7; color: var(--text-dark); }
  
  .btn-custom { border-radius:6px; padding:6px 12px; font-size: 0.85rem;}
  .btn-add { background-color: var(--umk-blue); color:white; } 
  .btn-add:hover { background-color: #08306b; color:white; }
  .btn-disable{ background-color:var(--umk-yellow); color:var(--text-dark); } 
</style>
</head>
<body>
<div class="dashboard-container">
  <div class="sidebar">
    <h4 class="d-flex align-items-center justify-content-center gap-2">
        <i class="fas fa-university"></i> Admin UMK
    </h4>
    <a href="#dashboard" class="active"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a>
    <a href="#akun"><i class="fas fa-user-cog fa-fw me-2"></i> Kelola Akun</a>
    <a href="#statistik"><i class="fas fa-chart-line fa-fw me-2"></i> Statistik</a>
    <a href="#log"><i class="fas fa-clipboard-list fa-fw me-2"></i> Log Aktivitas</a>
    <a href="#pengaturan"><i class="fas fa-cogs fa-fw me-2"></i> Pengaturan</a>
    <a href="logout.php?redirect=login_admin.php" class="text-warning mt-5"><i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout</a>
  </div>

  <div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Selamat Datang, <?= htmlspecialchars($nama_admin) ?>!</h2>
            <p class="text-muted">Pantau aktivitas parkir & akun di Universitas Muria Kudus.</p>
        </div>
        <button class="btn btn-primary btn-custom"><i class="fas fa-download me-1"></i> Unduh Laporan</button>
    </div>

    <div class="stats" id="dashboard">
      <div class="card-stat card-blue">
          <h3><?= $total_on_site ?></h3>
          <h5><i class="fas fa-car me-1"></i> Kendaraan Saat Ini</h5>
      </div>
      <div class="card-stat card-green">
          <h3><?= $total_users ?></h3>
          <h5><i class="fas fa-users me-1"></i> Total Pengguna</h5>
      </div>
      <div class="card-stat card-yellow">
          <h3><?= $total_petugas ?></h3>
          <h5><i class="fas fa-user-tie me-1"></i> Total Petugas</h5>
      </div>
      <div class="card-stat card-red">
          <h3><?= 100 - $kapasitas_terpakai ?>%</h3>
          <h5><i class="fas fa-map-marker-alt me-1"></i> Slot Tersedia (Simulasi)</h5>
      </div>
    </div>
    
    <div class="content-box">
        <h5><i class="fas fa-history me-1"></i> 10 Log Parkir Transaksi Terbaru</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Waktu Masuk</th>
                        <th>Plat Nomor</th>
                        <th>User ID</th>
                        <th>Status</th>
                        <th>Area ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_log_db && $result_log_db->num_rows > 0): ?>
                        <?php while($log = $result_log_db->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y, H:i', strtotime($log['waktu_masuk'])); ?></td>
                                <td><?php echo htmlspecialchars($log['plat_nomor']); ?></td>
                                <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                                <td>
                                    <span class="badge <?= $log['status'] == 'parkir' ? 'bg-primary' : 'bg-success' ?>">
                                        <?php echo htmlspecialchars(ucfirst($log['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['area_parkir_id'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Belum ada riwayat parkir yang tercatat di database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="content-box">
      <h5><i class="fas fa-filter me-1"></i> Filter Data Parkir</h5>
      <div class="filter-container d-flex gap-3 mt-3">
        <input type="date" id="filterTanggal" value="<?= date('Y-m-d') ?>" class="form-control" style="max-width: 180px;">
        <select id="filterArea" class="form-select" style="max-width: 180px;">
          <option value="">Semua Area</option>
          <?php foreach($area_list as $area) echo "<option>$area</option>"; ?>
        </select>
        <button class="btn btn-add btn-custom" onclick="filterData()">Terapkan Filter</button>
      </div>
    </div>


    <div id="statistik" class="content-box">
      <h5><i class="fas fa-chart-area me-1"></i> Grafik Kendaraan per Hari (Simulasi Data)</h5>
      <canvas id="chartKendaraan"></canvas>
    </div>

    <div id="akun" class="content-box">
      <h5><i class="fas fa-user-cog me-1"></i> Kelola Akun Pengguna (Simulasi)</h5>
      <form method="POST" class="row g-3 mb-4 p-3 bg-light rounded">
        <div class="col-md-3"><input type="text" name="nama" class="form-control" placeholder="Nama pengguna" required></div>
        <div class="col-md-3">
            <select name="role" class="form-select" required>
              <option value="">Pilih Role</option>
              <option>Mahasiswa</option>
              <option>Dosen</option>
              <option>Tamu</option>
              <option>Tukang Parkir</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
              <option>Aktif</option>
              <option>Nonaktif</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-add w-100" name="aksi" value="Tambah">+ Tambah Akun</button>
        </div>
      </form>

      <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="bg-light">
              <tr><th>ID</th><th>Nama</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($akun_pengguna as $akun): ?>
              <tr>
                <td><?= $akun['id'] ?></td>
                <td class="fw-bold"><?= $akun['nama'] ?></td>
                <td><span class="badge bg-info text-dark"><?= $akun['role'] ?></span></td>
                <td>
                  <span class="badge <?= $akun['status'] == 'Aktif' ? 'bg-success' : 'bg-secondary' ?>">
                      <?= $akun['status'] ?>
                  </span>
                </td>
                <td>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin melanjutkan aksi ini?')">
                    <input type="hidden" name="nama" value="<?= $akun['nama'] ?>">
                    <button class="btn btn-custom btn-info text-white" name="aksi" value="Edit"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-custom btn-disable" name="aksi" value="Nonaktifkan"><i class="fas fa-ban"></i> Nonaktifkan</button>
                    <?php if ($is_admin_utama): ?>
                      <button class="btn btn-custom btn-danger text-white" name="aksi" value="Hapus"><i class="fas fa-trash"></i> Hapus</button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
      </div>
    </div>

    <div id="log" class="content-box">
      <h5>📜 Log Aktivitas Admin (File Teks)</h5>
      <div style="max-height: 400px; overflow-y: auto;">
        <table class="table table-hover">
          <thead class="bg-light">
            <tr>
              <th>Waktu</th>
              <th>Aksi</th>
              <th>Oleh</th>
              <th>Target</th>
            </tr>
          </thead>
          <tbody>
          <?php
          // Fungsi catat_log mencatat dalam format: [Waktu] Aksi - Oleh: Admin - Target: Nama
          if (file_exists("log_aktivitas.txt")) {
            $logs = file("log_aktivitas.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $recent_logs = array_slice(array_reverse($logs), 0, 10);
            
            foreach($recent_logs as $line) {
              preg_match('/\[(.*?)\] (.*?) - Oleh: (.*?) - Target: (.*)/', $line, $matches);
              
              $waktu = $matches[1] ?? 'N/A';
              $aksi_full  = $matches[2] ?? 'N/A';
              $admin_pelaku = $matches[3] ?? 'N/A';
              $target_nama  = $matches[4] ?? 'N/A';
              
              $aksi = trim(explode(' - Oleh:', $aksi_full)[0]);
              
              $badge_class = 'bg-secondary';
              if(stripos($aksi, 'Menambah') !== false) $badge_class = 'bg-success';
              elseif(stripos($aksi, 'Mengedit') !== false) $badge_class = 'bg-primary';

              echo "<tr>
                      <td>$waktu</td>
                      <td><span class='badge $badge_class'>$aksi</span></td>
                      <td>$admin_pelaku</td>
                      <td>$target_nama</td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='4' class='text-center'>Belum ada aktivitas.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <div id="pengaturan" class="content-box">
        <h5><i class="fas fa-cogs me-1"></i> Pengaturan Sistem</h5>
        <p class="text-muted">Bagian ini dapat digunakan untuk mengatur tarif parkir, batas kapasitas area, atau mengelola petugas parkir.</p>
        <button class="btn btn-warning btn-custom"><i class="fas fa-users"></i> Kelola Petugas</button>
        <button class="btn btn-primary btn-custom"><i class="fas fa-money-bill"></i> Atur Tarif</button>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Js/dashboard_main.js"></script>
</body>
</html>