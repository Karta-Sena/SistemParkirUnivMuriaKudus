<?php
session_start();
include 'config.php';

// MODIFIKASI 1: Ambil pesan sukses dari reset password 
$login_message = '';
if (isset($_SESSION['login_message'])) {
    $login_message = $_SESSION['login_message'];
    unset($_SESSION['login_message']); // Hapus pesan setelah diambil
}

// LOGIKA UNTUK ALUR DARI REGISTRASI BERDASARKAN RANCANGAN ALUR SISTEM YA GUYS 
$selectedRole = isset($_GET['role']) ? $_GET['role'] : '';
$error_message = '';

// LOGIKA UNTUK PROSES LOGIN BERDASARKAN RANCANGAN ALUR SISTEM YA GAYS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $userInput = $_POST['user'];
    $password_input = $_POST['password'];

    $selectedRole = $role; 

    if (empty($role) || empty($userInput) || empty($password_input)) {
        $error_message = 'Role, Email/NIM/NIDN, dan Password harus diisi.';
    } else {
        
        $sql = "SELECT * FROM users WHERE (email = ? OR nim = ? OR nidn = ?) AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $userInput, $userInput, $userInput, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password_input, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama'];

                echo "<script>
                    alert('Login berhasil, selamat datang {$user['nama']}!');
                    window.location.href = 'dashboard_user.php';
                </script>";
                $stmt->close();
                $conn->close();
                exit;
            } else {
                $error_message = 'Password salah!';
            }
        } else {
            $error_message = 'Akun tidak ditemukan untuk role tersebut!';
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Akun Parkir UMK</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Css/form_layout.css">
    </head>
<body>
    
    <div class="background-blob blob-1"></div>
    <div class="background-blob blob-2"></div>
    <div class="background-blob blob-3"></div>

    <form class="register-card" id="login-form" action="login.php" method="POST" autocomplete="off">
        <img src="Lambang UMK.png" alt="Logo UMK" class="logo"
             onerror="this.src='https://placehold.co/130x40/ffffff/6a89cc?text=Logo+UMK&font=sans-serif'">

        <h2 style="text-align:center; margin-bottom:1.5rem; color: var(--text-putih); font-weight:600; letter-spacing:0.5px; font-size: 1.5rem;">
            Login Akun Parkir
        </h2>

        <?php if ($error_message): ?>
            <div id="form-status-message" class="error" style="display: block;">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($login_message): ?>
            <div id="form-status-message" class="success" style="display: block;">
                <?= htmlspecialchars($login_message) ?>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="role" class="sr-only">Role</label>
            <select id="role" name="role" required>
                <option value="" disabled <?= $selectedRole ? '' : 'selected' ?>>Pilih Role</option>
                <option value="mahasiswa" <?= $selectedRole === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                <option value="dosen" <?= $selectedRole === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                <option value="tamu" <?= $selectedRole === 'tamu' ? 'selected' : '' ?>>Tamu</option>
            </select>
            <div class="error-message" id="role-error"></div>
        </div>

        <div class="form-group">
            <label for="user" class="sr-only">Email, NIM, atau NIDN</label>
            <input type="text" id="user" name="user" placeholder="Email, NIM, atau NIDN" required>
            <div class="error-message" id="user-error"></div>
        </div>

        <div class="form-group">
            <div class="password-wrapper">
                <label for="password" class="sr-only">Password</label>
                <input type="password" id="password" name="password" placeholder="Kata sandi" required>
                <span class="toggle-password" data-target="password">🙈</span>
            </div>
            <div class="error-message" id="password-error"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-bottom:1rem; margin-top: 0.5rem;">
            <a href="forgot_password.php" style="color:var(--text-putih); font-size:0.98em; text-decoration:none; font-weight:500;">
                Lupa password?
            </a>
            </div>

        <button type="submit" class="btn-daftar">Login</button>

        <p class="login-link" style="margin-bottom:0;">
            Belum punya akun? <a href="registrasi.php">Daftar</a>
        </p>
    </form>

    <script src="Js/login.js"></script>
</body>
</html>