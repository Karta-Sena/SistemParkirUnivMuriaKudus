<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Password – Parkir UMK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Css/form_layout.css">
</head>
<body>
  <form class="register-card" id="forgot-form" action="request_reset.php" method="POST" autocomplete="off">
    <img src="Lambang UMK.png" alt="Logo UMK" class="logo"
         onerror="this.src='https://placehold.co/130x40/ffffff/6a89cc?text=UMK&font=sans-serif'">

    <h2 style="text-align:center; color: var(--text-putih); font-weight:600; margin-bottom:1.5rem;">
    Lupa Password
    </h2>
    
    <p class="form-description" style="color:rgba(255,255,255,0.85);text-align:center;margin-bottom:1.5rem;">
      Masukkan email yang kamu gunakan saat registrasi akun.
    </p>

    <?php if (isset($_SESSION['message'])): ?>
        <div id="form-status-message" class="<?= $_SESSION['message_type'] ?? 'error' ?>" style="display: block;">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php 
            // Hapus pesan setelah ditampilkan
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="form-group">
      <label for="email" class="sr-only">Email</label>
      <input type="email" id="email" name="email" placeholder="Email terdaftar" required>
      <div class="error-message" id="email-error"></div>
    </div>

    <button type="submit" class="btn-daftar">Kirim Link Reset</button>

    <p class="login-link">
      ← <a href="login.php">Kembali ke Login</a>
    </p>
  </form>

</body>
</html>