<?php
session_start();
include 'config.php';

// Keamanan: Cek apakah user_id ada DAN rolenya adalah mahasiswa, dosen, atau tamu
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['mahasiswa', 'dosen', 'tamu'])) {
    header('Location: login.php');
    exit;
}

// Ambil data dari session
$nama = $_SESSION['nama'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$avatar = $_SESSION['avatar'] ?? 'default.png';

// 1. Generate QR Code
$data_qr = "USER_ID_PARKIR:" . $user_id;
$url_qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($data_qr) . "&choe=UTF-8";

// 2. Ambil data kendaraan (hanya jika bukan tamu)
$result_kendaraan = null;
if ($role !== 'tamu') {
    $sql_kendaraan = "SELECT * FROM kendaraan WHERE user_id = ?";
    $stmt_kendaraan = $conn->prepare($sql_kendaraan);
    $stmt_kendaraan->bind_param("i", $user_id);
    $stmt_kendaraan->execute();
    $result_kendaraan = $stmt_kendaraan->get_result();
}

// 3. Ambil data riwayat parkir (5 terbaru)
$sql_log = "SELECT * FROM log_parkir WHERE user_id = ? ORDER BY waktu_masuk DESC LIMIT 5";
$stmt_log = $conn->prepare($sql_log);
$stmt_log->bind_param("i", $user_id);
$stmt_log->execute();
$result_log = $stmt_log->get_result();

// Tutup statement awal
$stmt_log->close();
if ($result_kendaraan !== null) {
    $stmt_kendaraan->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna</title>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="Css/fonts.css">
    <link rel="stylesheet" href="Css/dashboard_layout.css">
</head>
<body>

    <div class="page-wrapper">

        <div class="background-blob blob-1"></div>
        <div class="background-blob blob-2"></div>
        <div class="background-blob blob-3"></div>

        <header class="header-desktop">
            <img src="Lambang UMK.png" alt="Logo UMK" class="logo"
                 onerror="this.src='https://placehold.co/130x40/ffffff/6a89cc?text=UMK&font=sans-serif'">
            
            <nav class="nav-links">
                <a href="dashboard_user.php" class="active">Dashboard</a>
                <a href="profil.php">Profil Saya</a>
                <a href="kendaraan.php">Kendaraan</a>
            </nav>
            
            <a href="#" id="notif-toggle-desktop" class="notification-trigger-desktop">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">8</span>
            </a>

            <div class="profile-container">
                <a id="profile-toggle" class="profile-trigger">
                    <span>Halo, <?php echo htmlspecialchars($nama); ?>!</span>
                    <img src="uploads/<?php echo htmlspecialchars($avatar); ?>" alt="Profil" class="profile-pfp-header"
                         onerror="this.onerror=null; this.src='uploads/default.png';">
                </a>
                
                <div class="profile-dropdown">
                    <a href="profil.php">Edit Profil</a>
                    <a href="kendaraan.php">Edit Data Kendaraan</a>
                    <a href="logout.php">Log out</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            
            <div class="content-grid">
                
                <div>
                    <div class="card card-yellow">
                        <h2>Kartu Parkir Anda</h2>
                        <div class="qr-card-content">
                            <div class="qr-code">
                                <img src="<?php echo $url_qr; ?>" alt="QR Code Parkir">
                            </div>
                            <div class="user-details">
                                <h3><?php echo htmlspecialchars($nama); ?></h3>
                                <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?></p>
                                <p>Tunjukkan kode ini saat masuk & keluar</p>
                            </div>
                        </div>
                    </div>

                    <div class="card log-list">
                        <h2>Riwayat Parkir Terakhir</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Plat Nomor</th>
                                    <th>Waktu Masuk</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_log->num_rows > 0): ?>
                                    <?php while($log = $result_log->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['plat_nomor']); ?></td>
                                            <td><?php echo date('d M Y, H:i', strtotime($log['waktu_masuk'])); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($log['status'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align: center;">Tidak ada riwayat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <?php if ($role !== 'tamu'): ?>
                        <div class="card vehicle-list">
                            <h2>Informasi Kendaraan</h2>
                            
                            <button class="btn-tambah" id="tambahBtn">
                                [+] Tambah Kendaraan Baru
                            </button>

                            <div id="daftar-kendaraan">
                                <?php if ($result_kendaraan && $result_kendaraan->num_rows > 0): ?>
                                    <?php while($kendaraan = $result_kendaraan->fetch_assoc()): ?>
                                        <div class="list-item" id="kendaraan-<?php echo $kendaraan['id']; ?>">
                                            <div>
                                                <span><?php echo htmlspecialchars($kendaraan['plat_nomor']); ?></span>
                                                <br>
                                                <small><?php echo htmlspecialchars($kendaraan['no_stnk']); ?></small>
                                            </div>
                                            <div class="vehicle-actions">
                                                <button class="btn-edit" 
                                                        data-id="<?php echo $kendaraan['id']; ?>"
                                                        data-plat="<?php echo htmlspecialchars($kendaraan['plat_nomor']); ?>"
                                                        data-stnk="<?php echo htmlspecialchars($kendaraan['no_stnk']); ?>">
                                                    Edit
                                                </button>
                                                <button class="btn-delete" 
                                                        onclick="hapusKendaraan(<?php echo $kendaraan['id']; ?>)">
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p id="kendaraan-kosong" style="text-align: center;">Anda belum mendaftarkan kendaraan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </main>
        
    </div>

    <nav class="nav-mobile">
        <a href="dashboard_user.php" class="nav-link active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" id="notif-toggle-mobile" class="nav-link">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">8</span>
            <span>Notifikasi</span>
        </a>
        <a href="kendaraan.php" class="nav-link">
            <i class="fas fa-car"></i>
            <span>Kendaraan</span>
        </a>
        <a href="#" id="profile-toggle-mobile" class="nav-link">
            <i class="fas fa-bars"></i>
            <span>Lainnya</span>
        </a>
    </nav>

    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Kendaraan</h2>
                <span class="close-btn">&times;</span>
            </div>
            
            <form id="vehicleForm">
                <input type="hidden" id="form-kendaraan-id" name="id">
                
                <div class="form-group">
                    <label for="form-plat-nomor">Plat Nomor</label>
                    <input type="text" id="form-plat-nomor" name="plat_nomor" required>
                </div>
                <div class="form-group">
                    <label for="form-no-stnk">Nomor STNK</label>
                    <input type="text" id="form-no-stnk" name="no_stnk" required>
                </div>
                <button type="submit" class="btn-simpan">Simpan</button>
            </form>
        </div>
    </div>

    <div class="overlay"></div>

    <div class="profile-drawup">
        <div class="profile-drawup-header">
            <h5>Opsi Akun</h5>
            <button id="close-drawup">&times;</button>
        </div>
        <div class="profile-drawup-content">
            <a href="profil.php" class="btn-daftar">Edit Profil</a>
            <a href="kendaraan.php" class="btn-daftar">Edit Data Kendaraan</a>
            <a href="logout.php" class="btn-daftar" style="background: var(--error-red); border: none;">Log out</a>
        </div>
    </div>

    <div id="notification-panel" class="notification-panel">
        <div class="notification-panel-header">
            <h5>Notifikasi</h5>
            <button id="close-notif">&times;</button>
        </div>
        <div class="notification-panel-content">
            
            <div class="notif-item new">
                <div class="notif-icon" style="background-color: rgba(0, 122, 255, 0.2); color: #007AFF;">
                    <i class="fas fa-comment"></i>
                </div>
                <div class="notif-text">
                    <strong>New comment</strong>
                    <p>Ava: “Love this shot!”</p>
                </div>
            </div>

            <div class="notif-item">
                <div class="notif-icon" style="background-color: rgba(255, 149, 0, 0.2); color: #FF9500;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="notif-text">
                    <strong>Reminder</strong>
                    <p>Standup in 15 min</p>
                </div>
            </div>
            
            <div class="notif-item">
                <div class="notif-icon" style="background-color: rgba(88, 86, 214, 0.2); color: #5856D6;">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="notif-text">
                    <strong>Build Complete</strong>
                    <p>v1.4.3 deployed</p>
                </div>
            </div>

            <div class="notif-item">
                <div class="notif-icon" style="background-color: rgba(255, 204, 0, 0.2); color: #FFCC00;">
                    <i class="fas fa-at"></i>
                </div>
                <div class="notif-text">
                    <strong>Mention</strong>
                    <p>@you in #design</p>
                </div>
            </div>

        </div>
    </div>

    <script src="Js/dashboard_app.js"></script>
</body>
</html>