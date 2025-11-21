<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once 'config.php';

$user_id = $_SESSION['user_id'];
$error_message = '';
$placeholder = 'assets/img/avatar-placeholder.png'; // Definisikan placeholder di atas

$stmt = $conn->prepare("SELECT id, role, nama, email, nim, nidn, avatar FROM users WHERE id = ?");
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- Logika Update Profile ---
    if ($action === 'update_profile') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nim = trim($_POST['nim'] ?? '');
        $nidn = trim($_POST['nidn'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($nama) || empty($email)) {
            $error_message = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Format email tidak valid.';
        } else {
            $email_berubah = ($email !== $user['email']);
            
            if ($email_berubah && empty($confirm_password)) {
                $error_message = 'Konfirmasi password diperlukan untuk mengubah email.';
            } elseif ($email_berubah) {
                $stmt_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt_pass->bind_param("i", $user_id);
                $stmt_pass->execute();
                $result_pass = $stmt_pass->get_result();
                $data_pass = $result_pass->fetch_assoc();
                $stmt_pass->close();
                
                if (!password_verify($confirm_password, $data_pass['password'])) {
                    $error_message = 'Password tidak sesuai. Perubahan email dibatalkan.';
                }
            }
            
            if (empty($error_message)) {
                $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check->bind_param("si", $email, $user_id);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows > 0) {
                    $error_message = 'Email sudah digunakan oleh user lain.';
                    $check->close();
                } else {
                    $check->close();
                
                    $avatar_path = $user['avatar'];
                    
                    // --- Logika Avatar Terintegrasi ---
                    // 1. Cek jika ada flag HAPUS
                    if (isset($_POST['delete_avatar_flag']) && $_POST['delete_avatar_flag'] === '1') {
                        if (!empty($user['avatar']) && $user['avatar'] !== $placeholder) {
                            $old_file = __DIR__ . '/' . $user['avatar'];
                            if (file_exists($old_file)) {
                                @unlink($old_file);
                            }
                        }
                        $avatar_path = NULL; // Set ke NULL di database
                        $_SESSION['success_message'] = 'Avatar berhasil dihapus.'; 
                    } 
                    // 2. Cek jika ada file BARU (dan tidak ada flag hapus)
                    elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK && !empty($_FILES['avatar']['name'])) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['avatar']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                            $upload_dir = __DIR__ . '/uploads/avatars/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                            $full_upload_path = $upload_dir . $new_filename;
                            $db_path = 'uploads/avatars/' . $new_filename;
                            
                            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $full_upload_path)) {
                                if (!empty($user['avatar']) && $user['avatar'] !== $placeholder) {
                                    $old_file = __DIR__ . '/' . $user['avatar'];
                                    if (file_exists($old_file)) {
                                        @unlink($old_file);
                                    }
                                }
                                $avatar_path = $db_path;
                            } else {
                                $error_message = 'Gagal mengunggah gambar avatar.';
                            }
                        } else {
                            $error_message = 'Format gambar tidak didukung atau ukuran terlalu besar (maks 2MB).';
                        }
                    }
                    // 3. Jika tidak ada aksi (file tidak diubah) $avatar_path tetap $user['avatar']
                    
                    if (empty($error_message)) {
                        $update = $conn->prepare("UPDATE users SET nama = ?, email = ?, nim = ?, nidn = ?, avatar = ? WHERE id = ?");
                        // Perbaikan: Pastikan $nim dan $nidn di-bind dengan benar
                        $update->bind_param("sssssi", $nama, $email, $nim, $nidn, $avatar_path, $user_id);
                        
                        if ($update->execute()) {
                            $_SESSION['nama'] = $nama;
                            $_SESSION['email'] = $email;
                            $_SESSION['avatar'] = $avatar_path;
                            
                            if (!isset($_SESSION['success_message'])) {
                                $_SESSION['success_message'] = $email_berubah ? 'Profile berhasil diperbarui! Email Anda telah diubah.' : 'Profile berhasil diperbarui!';
                            }
                            
                            $update->close();
                            $conn->close();
                            header('Location: profile.php');
                            exit;
                        } else {
                            $error_message = 'Gagal memperbarui profile.';
                        }
                        $update->close();
                    }
                }
            }
        }
    }
    
    // --- Logika Change Password ---
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $error_message = 'Semua field password wajib diisi.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password baru minimal 6 karakter.';
        } elseif ($new_password !== $confirm_new_password) {
            $error_message = 'Konfirmasi password tidak cocok.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($current_password, $data['password'])) {
                $error_message = 'Password lama tidak sesuai.';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $new_hash, $user_id);
                
                if ($update->execute()) {
                    $_SESSION['success_message'] = 'Password berhasil diubah!';
                    $update->close();
                    $conn->close();
                    header('Location: profile.php');
                    exit;
                } else {
                    $error_message = 'Gagal mengubah password.';
                }
                $update->close();
            }
        }
    }
}

$conn->close();

$avatarPath = !empty($user['avatar']) ? $user['avatar'] : $placeholder;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo htmlspecialchars($user['nama']); ?></title>
    
    <link rel="stylesheet" href="Css/dashboard_layout.css">
    <link rel="stylesheet" href="Css/form_layout.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- CSS Umum --- */
        :root {
            --umk-blue: #00478a;
            --space-xs: 4px;
            --space-sm: 8px;
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;
            --transition-base: all 0.2s ease-in-out;
            --transition-fast: all 0.1s ease-out;
        }

        body {
            padding: 0 !important;
            margin: 0 !important;
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(180deg, #0039a3 0%, #248ff9 28%, #00478a 62%, #114a9b 100%) fixed;
        }
        .profile-page-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: var(--space-xl);
            width: 100%;
        }
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
        .btn-back-internal {
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            padding: 6px 10px;
            background: #f1f5f9; 
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #475569; 
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition-base);
            margin-bottom: var(--space-md);
        }
        .btn-back-internal:hover { background: #e2e8f0; }
        .profile-name { 
            font-size: 1.8rem; 
            font-weight: 700; 
            color: #111827; 
            margin: 0; 
            line-height: 1.2; 
        }
        .card-divider { 
            height: 0px; 
            border-bottom: 2px dashed rgba(0, 0, 0, 0.2); 
            margin: var(--space-xl) 0; 
        }
        .form-section { margin-bottom: var(--space-xl); }
        .section-title { 
            font-size: 1.3rem; 
            font-weight: 700; 
            color: #0f172a; 
            margin-bottom: var(--space-lg); 
            display: flex; 
            align-items: center; 
            gap: var(--space-sm); 
        }
        .form-group { margin-bottom: var(--space-md); }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            font-size: 0.9rem; 
            color: #475569; 
            margin-bottom: var(--space-xs); 
        }
        .form-group input { 
            width: 100%; 
            padding: 10px 14px; 
            border-radius: 10px; 
            border: 1.5px solid rgba(0, 0, 0, 0.15); 
            background: white; 
            font-size: 0.95rem; 
            font-weight: 500; 
            color: #1e293b; 
            transition: var(--transition-fast); 
        }
        .form-group input:focus { 
            outline: none; 
            border-color: var(--umk-blue); 
            box-shadow: 0 0 0 3px rgba(17, 74, 155, 0.1); 
        }
        .form-group input:disabled { 
            background: #f1f5f9; 
            cursor: not-allowed; 
            opacity: 0.7; 
        }
        .alert { 
            padding: var(--space-sm); 
            border-radius: 10px; 
            margin-bottom: var(--space-md); 
            font-weight: 600; 
            font-size: 0.9rem; 
            display: flex; 
            align-items: center; 
            gap: var(--space-xs); 
        }
        .alert-error { 
            background: rgba(239, 68, 68, 0.1); 
            color: #DC2626; 
            border: 1.5px solid rgba(239, 68, 68, 0.3); 
        }
        .info-text { 
            font-size: 0.8rem; 
            color: #64748b; 
            margin-top: 4px; 
        }
        .password-info { 
            background: rgba(59, 130, 246, 0.05); 
            border: 1.5px solid rgba(59, 130, 246, 0.2); 
            border-radius: 10px; 
            padding: var(--space-sm); 
            margin-bottom: var(--space-md); 
            font-size: 0.85rem; 
            color: #1e40af; 
        }
        .email-confirm-group { 
            background: rgba(239, 68, 68, 0.05); 
            border: 1.5px dashed rgba(239, 68, 68, 0.3); 
            border-radius: 10px; 
            padding: var(--space-md); 
            margin-top: var(--space-md); 
        }
        .profile-card-unified .btn-daftar { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            width: 100%; 
            text-decoration: none; 
            margin-top: var(--space-md); 
            padding: 14px; 
            font-size: 1rem; 
            gap: var(--space-xs); 
        }
        @media (max-width: 767px) { 
            .profile-page-wrapper { padding: var(--space-md); } 
            .profile-card-unified { padding: var(--space-lg); } 
            .profile-name { font-size: 1.5rem; } 
        }
        .input-wrapper { position: relative; width: 100%; }
        .input-wrapper input { padding-right: 45px !important; }
        .toggle-password { 
            position: absolute; 
            right: 1.5rem; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            user-select: none; 
            color: #aaa; 
            font-size: 1.2rem; 
            transition: color .18s ease; 
            background: none; 
            border: none; 
            padding: 0; 
        }
        .toggle-password:hover { color: #333; }

        /* --- PERUBAHAN: Style Avatar di Tengah --- */
        .profile-photo-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: var(--space-lg);
            text-align: center;
        }
        .profile-photo-area .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--umk-blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: var(--space-md);
        }
        .profile-photo-controls {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
            align-items: center;
        }
        .btn-change-photo {
            background: none;
            border: none;
            color: var(--umk-blue);
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            text-align: center;
            font-size: 0.95rem;
            transition: color 0.15s ease-in-out;
        }
        .btn-change-photo:hover {
            color: #0d3a7a; 
            text-decoration: underline;
        }
        #avatarInput {
            display: none;
        }

        /* --- Modal Styling (dari referensi) --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0s 0.3s linear;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
            transition-delay: 0s;
        }
        .modal-content {
            background-color: #fff;
            padding: var(--space-md);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 320px;
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        .modal-option-btn {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px var(--space-md);
            margin-bottom: var(--space-xs);
            background: none;
            border: none;
            border-radius: 8px;
            text-align: left;
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
            gap: var(--space-sm);
        }
        .modal-option-btn:hover {
            background-color: #f1f5f9;
        }
        .modal-option-btn.red {
            color: #DC2626;
        }
        .modal-option-btn.red:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }
        .modal-option-btn span {
            font-size: 1.2em; 
            line-height: 1;
        }
        .modal-cancel-btn {
            width: 100%;
            padding: 12px var(--space-md);
            background: #e2e8f0;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
            margin-top: var(--space-sm);
        }
        .modal-cancel-btn:hover {
            background-color: #cbd5e1;
        }
    </style>
</head>
<body>

    <div class="profile-page-wrapper">
        
        <div class="profile-card-unified">
            
            <a href="profile.php" class="btn-back-internal" title="Kembali ke Profil">
                Kembali ke Profil
            </a>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="delete_avatar_flag" id="deleteAvatarFlag" value="0">
                <input type="file" id="avatarInput" name="avatar" accept="image/*" class="avatar-input">

                <div class="profile-photo-area">
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-preview" id="avatarPreview">
                    <div class="profile-photo-controls">
                        <button type="button" class="btn-change-photo" id="openAvatarModalBtn">
                            Ubah foto profil
                        </button>
                    </div>
                </div>
                
                <h1 class="profile-name" style="text-align: center; margin-top: 0; margin-bottom: var(--space-lg);">
                    <?php echo htmlspecialchars($user['nama']); ?>
                </h1>
                
                <div class="card-divider"></div>
                
                <div class="form-section">
                    <div class="section-title">
                        Informasi Profile
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap <span style="color: #DC2626;">*</span></label>
                        <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" disabled>
                    </div>
                    
                    <?php if ($user['role'] === 'mahasiswa'): ?>
                    <div class="form-group">
                        <label>NIM</label>
                        <input type="text" name="nim" value="<?php echo htmlspecialchars($user['nim'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'dosen'): ?>
                    <div class="form-group">
                        <label>NIDN</label>
                        <input type="text" name="nidn" value="<?php echo htmlspecialchars($user['nidn'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        Email & Keamanan
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span style="color: #DC2626;">*</span></label>
                        <input type="email" id="emailInput" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <div class="info-text">
                            Mengubah email memerlukan konfirmasi password
                        </div>
                    </div>
                    
                    <div id="emailConfirmGroup" class="email-confirm-group" style="display: none;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>
                                Konfirmasi Password <span style="color: #DC2626;">*</span></label>
                            
                            <div class="input-wrapper password-wrapper">
                                <input type="password" id="confirmPasswordInput" name="confirm_password" placeholder="Masukkan password untuk konfirmasi">
                                <span class="toggle-password" data-target="confirmPasswordInput">🙈</span>
                            </div>
                            
                            <div class="info-text" style="color: #DC2626;">
                                Password diperlukan karena Anda mengubah email
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-daftar">
                    Simpan Perubahan
                </button>
            </form>
            
            <div class="card-divider"></div>
            
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-section">
                    <div class="section-title">
                        Ubah Password
                    </div>
                    
                    <div class="password-info">
                        Password minimal 6 karakter. Gunakan kombinasi huruf dan angka untuk keamanan lebih baik.
                    </div>
                    
                    <div class="form-group">
                        <label>Password Lama <span style="color: #DC2626;">*</span></label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" id="currentPassword" name="current_password" placeholder="Masukkan password lama" required>
                            <span class="toggle-password" data-target="currentPassword">🙈</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password Baru <span style="color: #DC2626;">*</span></label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" id="newPassword" name="new_password" placeholder="Masukkan password baru (min. 6 karakter)" required>
                            <span class="toggle-password" data-target="newPassword">🙈</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Password Baru <span style="color: #DC2626;">*</span></label>
                        <div class="input-wrapper password-wrapper">
                            <input type="password" id="confirmNewPassword" name="confirm_new_password" placeholder="Ulangi password baru" required>
                            <span class="toggle-password" data-target="confirmNewPassword">🙈</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-daftar">
                    Ubah Password
                </button>
            </form>
        </div>
    </div>

    <div id="avatarModal" class="modal-overlay">
        <div class="modal-content">
            <button type="button" class="modal-option-btn" id="uploadPhotoOption">
                <span>&#128247;</span> Unggah Foto
            </button>
            
            <?php if (!empty($user['avatar']) && $user['avatar'] !== $placeholder): ?>
                <button type="button" class="modal-option-btn red" id="removePhotoOption">
                    <span>&#128465;</span> Hapus Foto
                </button>
            <?php endif; ?>
            
            <button type="button" class="modal-cancel-btn" id="cancelModalBtn">
                Batal
            </button>
        </div>
    </div>
    
<script>
        const originalEmail = "<?php echo htmlspecialchars($user['email']); ?>";
        const placeholderImage = "<?php echo htmlspecialchars($placeholder); ?>";
        
        // --- JavaScript untuk Modal Avatar ---
        const avatarModal = document.getElementById('avatarModal');
        const openAvatarModalBtn = document.getElementById('openAvatarModalBtn');
        const uploadPhotoOption = document.getElementById('uploadPhotoOption');
        const removePhotoOption = document.getElementById('removePhotoOption');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreview = document.getElementById('avatarPreview');
        const deleteAvatarFlag = document.getElementById('deleteAvatarFlag');
        const profileForm = document.getElementById('profileForm');

        function openModal() {
            avatarModal.classList.add('active');
        }

        function closeModal() {
            avatarModal.classList.remove('active');
        }

        openAvatarModalBtn.addEventListener('click', openModal);
        cancelModalBtn.addEventListener('click', closeModal);

        avatarModal.addEventListener('click', function(e) {
            if (e.target === avatarModal) {
                closeModal();
            }
        });

        // Opsi: Unggah Foto
        uploadPhotoOption.addEventListener('click', function() {
            avatarInput.click(); // Memicu input file tersembunyi
            closeModal();
        });

        // Opsi: Hapus Foto (jika tombolnya ada)
        if (removePhotoOption) {
            removePhotoOption.addEventListener('click', function() {
                if (confirm('Apakah Anda yakin ingin menghapus foto profil Anda?')) {
                    deleteAvatarFlag.value = '1'; // Set flag untuk PHP
                    avatarInput.value = ''; // Hapus file jika ada yang dipilih
                    avatarPreview.src = placeholderImage; // Tampilkan placeholder
                    closeModal();
                    // Submit form untuk memproses penghapusan
                    profileForm.submit(); 
                }
            });
        }
        
        // Avatar preview saat file dipilih
        avatarInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
                // Reset flag delete jika user memilih gambar baru
                deleteAvatarFlag.value = '0'; 
            }
        });

        // Email change detection
        document.getElementById('emailInput').addEventListener('input', function() {
            const confirmGroup = document.getElementById('emailConfirmGroup');
            const confirmInput = document.getElementById('confirmPasswordInput');
            
            if (this.value !== originalEmail) {
                confirmGroup.style.display = 'block';
                confirmInput.setAttribute('required', 'required');
            } else {
                confirmGroup.style.display = 'none';
                confirmInput.removeAttribute('required');
                confirmInput.value = '';
            }
        });
        
        // Script toggle emoji (dari registrasi.php)
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    this.textContent = '🐵';
                } else {
                    targetInput.type = 'password';
                    this.textContent = '🙈';
                }
            });
        });
    </script>
</body>
</html>