<?php
// FILE: login_admin.php (Form Login Khusus Admin)
session_start();
include '../config.php';

// --- AMBIL DAN HAPUS PESAN EROR DARI SESI ---
$error_message = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
// ------------------------------------------

// --- Proteksi Sesi Ganda ---
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] === 'admin')) {
        header("Location: dashboard_admin.php");
        exit();
    } elseif (($_SESSION['role'] === 'petugas')) {
        // Asumsi ada dashboard petugas
        header("Location: dashboard_petugas_parkir.php");
        exit();
    }
}
// ----------------------------

$process_file = 'process_login_admin.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../Css/Fonts.css">
    <link rel="stylesheet" href="../Css/form_layout.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <div class="background-blob blob-1"></div>
    <div class="background-blob blob-2"></div>
    <div class="background-blob blob-3"></div>
    
    <form class="register-card" action="<?php echo $process_file; ?>" method="POST" autocomplete="off">

        <img src="../Lambang UMK.png" alt="Logo UMK" class="logo">
        
        <h2>Login Admin</h2>

        <?php if ($error_message): ?>
            <div id="form-status-message" class="error" style="display: block; padding: 10px; margin-bottom: 15px; border: 1px solid red; background-color: #ffe5e5; color: #cc0000; border-radius: 4px;">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        <input type="hidden" name="role" value="admin">
        
        <div class="form-group">
            <label for="username" class="sr-only">Email Admin</label>
            <input type="text" id="username" name="username" placeholder="Email Admin" required> 
        </div>
        
        <div class="form-group">
            <div class="password-wrapper"> 
                <label for="password" class="sr-only">Kata sandi</label>
                <input type="password" id="password" name="password" placeholder="Kata sandi" required>
                <span class="toggle-password" data-target="password">
                    <i class="fa-solid fa-eye-slash"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn-daftar">Login</button>

        <p class="login-link">
            Kembali ke <a href="login.php">Login User</a>
        </p>
    </form>

    <script>
        // Logika untuk menampilkan/menyembunyikan kata sandi
        document.querySelector('.toggle-password')?.addEventListener('click', function() {
            const passwordInput = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            
            passwordInput.setAttribute('type', type);
            if (type === 'password') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>