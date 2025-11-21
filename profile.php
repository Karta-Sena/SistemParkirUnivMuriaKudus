<?php
// Pastikan session dimulai sebelum output HTML
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once 'config.php';

$user_id = $_SESSION['user_id'];

// Ambil data user dari database
$stmt = $conn->prepare("SELECT id, role, nama, email, nim, nidn, avatar, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$placeholder = 'assets/img/avatar-placeholder.png';
$avatarPath = !empty($user['avatar']) ? $user['avatar'] : $placeholder;

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['nama']); ?></title>
    
    <link rel="stylesheet" href="Css/dashboard_layout.css">
    <link rel="stylesheet" href="Css/form_layout.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Hapus semua margin/padding dari body */
        body {
            padding: 0 !important;
            margin: 0 !important;
            background: linear-gradient(180deg, #0039a3 0%, #248ff9 28%, #00478a 62%, #114a9b 100%) fixed;
        }

        /* Wrapper untuk menengahkan kartu */
        .profile-page-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: var(--space-xl);
            width: 100%;
        }

        /* Container Putih Solid Tunggal */
        .profile-card-unified {
            background: #ffffff;
            color: #111827;
            border-radius: 18px; 
            padding: var(--space-xl);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 900px; 
            position: relative;
        }

        /* PERUBAHAN 1: Tombol Kembali diperkecil */
        .btn-back-internal {
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            padding: 6px 10px; /* Padding lebih kecil */
            background: #f1f5f9; 
            border: 1px solid #e2e8f0;
            border-radius: 8px; /* Radius sedikit lebih kecil */
            color: #475569; 
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem; /* Ukuran font lebih kecil */
            transition: var(--transition-base);
            /* Tambahkan margin-bottom agar tidak terlalu dekat dengan avatar */
            margin-bottom: var(--space-md); 
        }
        .btn-back-internal:hover {
            background: #e2e8f0;
        }

        /* Header Profil (Avatar, Nama, Role) */
        .profile-header-internal {
            text-align: center;
            margin-top: var(--space-sm);
        }
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--umk-blue); 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: var(--space-md);
        }
        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }
        .profile-role-badge {
            display: inline-block;
            padding: 6px 16px;
            background: var(--umk-blue); 
            color: white; 
            border-radius: 100px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: capitalize;
            margin-top: var(--space-xs);
        }
        
        /* PERUBAHAN 3: Pembatas menjadi dashed line */
        .card-divider {
            height: 0px; /* Hapus tinggi solid */
            border-bottom: 3px dashed #00000030; /* Garis putus-putus */
            margin: var(--space-xl) 0;
        }

        /* PERUBAHAN 2: Judul "Informasi Akun" dibuat segaris */
        .profile-card-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            display: flex; /* Menggunakan flexbox */
            align-items: center; /* Vertikal tengah */
            gap: var(--space-sm);
            margin: 0 0 var(--space-lg) 0;
            /* Tambahan: Pastikan ikon tidak terlalu jauh dari teks jika ada ruang kosong */
            width: fit-content; /* Mempersempit lebar agar sesuai konten */
        }
        
        /* Grid Info (Sesuai screenshot) */
        .profile-info-grid {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
        }
        .profile-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md);
            background: #f8f9fa; 
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .profile-info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.9rem;
        }
        .profile-info-value {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }
        
        /* Style kustom untuk .btn-daftar di dalam card */
        .profile-card-unified .btn-daftar {
            display: flex; 
            align-items: center;
            justify-content: center;
            width: 100%;
            text-decoration: none; 
            margin-top: var(--space-xl);
            padding: 14px; 
            font-size: 1rem; 
            gap: var(--space-xs);
        }
        
        @media (max-width: 767px) {
            .profile-page-wrapper {
                padding: var(--space-md);
            }
            .profile-card-unified {
                padding: var(--space-lg);
            }
            .profile-avatar-large {
                width: 100px;
                height: 100px;
            }
            .profile-name {
                font-size: 1.5rem;
            }
            .profile-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
    </style>
</head>
<body>

    <div class="profile-page-wrapper">
        
        <div class="profile-card-unified">
            
            <a href="overview.php" class="btn-back-internal">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Dashboard
            </a>
            
            <div class="profile-header-internal">
                <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="profile-avatar-large">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['nama']); ?></h1>
                <span class="profile-role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>

            <div class="card-divider"></div>

            <div class="profile-card-header">
                <h2>
                    <i class="fas fa-info-circle"></i>
                    Informasi Akun
                </h2>
            </div>
            <div class="profile-info-grid">
                <div class="profile-info-item">
                    <span class="profile-info-label">User ID</span>
                    <span class="profile-info-value">#<?php echo htmlspecialchars($user['id']); ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Nama Lengkap</span>
                    <span class="profile-info-value"><?php echo htmlspecialchars($user['nama']); ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Email</span>
                    <span class="profile-info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Role</span>
                    <span class="profile-info-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                </div>
                
                <?php if ($user['role'] === 'mahasiswa' && !empty($user['nim'])): ?>
                <div class="profile-info-item">
                    <span class="profile-info-label">NIM</span>
                    <span class="profile-info-value"><?php echo htmlspecialchars($user['nim']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($user['role'] === 'dosen' && !empty($user['nidn'])): ?>
                <div class="profile-info-item">
                    <span class="profile-info-label">NIDN</span>
                    <span class="profile-info-value"><?php echo htmlspecialchars($user['nidn']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="profile-info-item">
                    <span class="profile-info-label">Terdaftar Sejak</span>
                    <span class="profile-info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <a href="edit_profile.php" class="btn-daftar">
                <i class="fas fa-edit"></i>
                Edit Profile
            </a>
            
        </div>
        
    </div>
    
</body>
</html>