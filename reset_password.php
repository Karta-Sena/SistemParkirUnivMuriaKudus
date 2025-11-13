<?php
session_start();
// Ambil token dari URL
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';

if (empty($token)) {
    // Jika tidak ada token, lempar ke halaman login 
    $_SESSION['login_message'] = 'Token reset tidak valid atau tidak ada.';
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password – Parkir UMK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Css/form_layout.css">
</head>
<body>
  <form class="register-card" id="reset-form" action="do_reset.php" method="POST" autocomplete="off">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    
    <img src="Lambang UMK.png" alt="Logo UMK" class="logo"
         onerror="this.src='https://placehold.co/130x40/ffffff/6a89cc?text=UMK&font=sans-serif'">

    <h2 style="text-align:center;color:white;margin-bottom:1rem;">Reset Password</h2>
    <p style="color:rgba(255,255,255,0.85);text-align:center;margin-bottom:1.5rem;">
      Masukkan kata sandi baru kamu di bawah ini.
    </p>

    <?php if (isset($_SESSION['message'])): ?>
        <div id="form-status-message" class="<?= $_SESSION['message_type'] ?? 'error' ?>" style="display: block;">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php 
            // Untuk menghapus pesan setelah ditampilkan
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="form-group">
        <div class="password-wrapper">
          <label for="password" class="sr-only">Password Baru</label>
          <input type="password" id="password" name="password" placeholder="Password Baru" required>
          <span class="toggle-password" data-target="password">🙈</span>
        </div>
    </div>

    <div class="form-group">
        <div class="password-wrapper">
          <label for="confirm" class="sr-only">Konfirmasi Password</label>
          <input type="password" id="confirm" name="confirm" placeholder="Konfirmasi Password" required>
          <span class="toggle-password" data-target="confirm">🙈</span>
        </div>
    </div>

    <button type="submit" class="btn-daftar">Simpan Password</button>

    <p class="login-link">
      ← <a href="login.php">Kembali ke Login</a>
    </p>
  </form>

  <script src="Js/login.js"></script>
</body>
</html>