<?php
session_start();
include 'config.php';

$error_message = '';
$old_input = [];

// Cek apakah ada error dari redirect sebelumnya
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['old_input'])) {
    $old_input = $_SESSION['old_input'];
    unset($_SESSION['old_input']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role             = $_POST['role'] ?? '';
    $nama             = $_POST['nama'] ?? '';
    $email            = $_POST['email'] ?? '';
    $password_plain   = $_POST['password'] ?? '';
    $konfirmasi_pass  = $_POST['konfirmasi-pass'] ?? '';
    $nim              = $_POST['nim'] ?? null;
    $nidn             = $_POST['nidn'] ?? null;
    $no_stnk          = $_POST['no-stnk'] ?? '';
    $plat_nomor       = $_POST['plat-nomor'] ?? '';

    // Simpan input untuk diisi kembali jika gagal
    $_SESSION['old_input'] = $_POST;

    if (empty($role) || empty($nama) || empty($email) || empty($password_plain) || empty($no_stnk) || empty($plat_nomor)) {
        $_SESSION['error_message'] = 'Semua data wajib diisi.';
        header('Location: registrasi.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Format email tidak valid.';
        header('Location: registrasi.php');
        exit;
    }
    if (strlen($password_plain) < 6) {
        $_SESSION['error_message'] = 'Password minimal 6 karakter.';
        header('Location: registrasi.php');
        exit;
    }
    if ($password_plain !== $konfirmasi_pass) {
        $_SESSION['error_message'] = 'Konfirmasi password tidak cocok.';
        header('Location: registrasi.php');
        exit;
    }
    if ($role === 'mahasiswa' && empty($nim)) {
        $_SESSION['error_message'] = 'NIM wajib diisi untuk mahasiswa.';
        header('Location: registrasi.php');
        exit;
    }
    if ($role === 'dosen' && empty($nidn)) {
        $_SESSION['error_message'] = 'NIDN wajib diisi untuk dosen.';
        header('Location: registrasi.php');
        exit;
    }

    // Cek Duplikat (Email)
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error_message'] = 'Email sudah terdaftar.';
        $stmt->close();
        header('Location: registrasi.php');
        exit;
    }
    $stmt->close();

    // Cek Duplikat (STNK)
    $cek_stnk = $conn->prepare("SELECT id FROM kendaraan WHERE no_stnk = ?");
    $cek_stnk->bind_param("s", $no_stnk);
    $cek_stnk->execute();
    $cek_stnk->store_result();
    if ($cek_stnk->num_rows > 0) {
        $_SESSION['error_message'] = 'Nomor STNK sudah terdaftar.';
        $cek_stnk->close();
        header('Location: registrasi.php');
        exit;
    }
    $cek_stnk->close();
    
    // Cek Duplikat (Plat)
    $cek_plat = $conn->prepare("SELECT id FROM kendaraan WHERE plat_nomor = ?");
    $cek_plat->bind_param("s", $plat_nomor);
    $cek_plat->execute();
    $cek_plat->store_result();
    if ($cek_plat->num_rows > 0) {
        $_SESSION['error_message'] = 'Plat nomor sudah terdaftar.';
        $cek_plat->close();
        header('Location: registrasi.php');
        exit;
    }
    $cek_plat->close();

    // === PROSES PENYIMPANAN DATA ===
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $conn->begin_transaction();

    try {
        $query_user = "INSERT INTO users (role, nama, email, password, nim, nidn) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_user = $conn->prepare($query_user);
        $stmt_user->bind_param("ssssss", $role, $nama, $email, $password_hash, $nim, $nidn);
        $stmt_user->execute();
        
        $user_id = $conn->insert_id;
        $stmt_user->close();

        $query_kendaraan = "INSERT INTO kendaraan (user_id, plat_nomor, no_stnk) VALUES (?, ?, ?)";
        $stmt_kendaraan = $conn->prepare($query_kendaraan);
        $stmt_kendaraan->bind_param("iss", $user_id, $plat_nomor, $no_stnk);
        $stmt_kendaraan->execute();
        $stmt_kendaraan->close();

        $conn->commit();
        
        unset($_SESSION['old_input']);
        $_SESSION['login_message'] = 'Registrasi berhasil! Silakan login.';
        header('Location: login.php?role=' . urlencode($role));
        exit;

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Terjadi kesalahan database: ' . $exception->getMessage();
        header('Location: registrasi.php');
        exit;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran UMK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Css/form_layout.css"> </head>
<body>
    <form class="register-card" id="register-form" action="registrasi.php" method="POST" autocomplete="off">

        <img src="Lambang UMK.png" alt="Logo UMK" class="logo"
             onerror="this.src='https://placehold.co/130x40/ffffff/6a89cc?text=Logo+UMK&font=sans-serif'">

        <?php if ($error_message): ?>
            <div id="form-status-message" class="error" style="display: block;">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="role" class="sr-only">Role</label>
            <select id="role" name="role" required>
                <option value="" disabled <?= empty($old_input['role']) ? 'selected' : '' ?>>Pilih peran kamu di kampus</option>
                <option value="mahasiswa" <?= ($old_input['role'] ?? '') === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                <option value="dosen" <?= ($old_input['role'] ?? '') === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                <option value="tamu" <?= ($old_input['role'] ?? '') === 'tamu' ? 'selected' : '' ?>>Tamu</option> 
            </select>
            <div class="error-message" id="role-error"></div>
        </div>

        <div class="form-row">
            <div class="form-group form-group-dynamic" id="nama-group">
                <label for="nama" class="sr-only">Nama</label>
                <input type="text" id="nama" name="nama" placeholder="Siapa namamu? Tulis lengkap ya" value="<?= htmlspecialchars($old_input['nama'] ?? '') ?>" required>
                <div class="error-message" id="nama-error"></div>
            </div>
            <div class="form-group form-group-dynamic field-hidden" id="nim-group">
                <label for="nim" class="sr-only">NIM</label>
                <input type="text" id="nim" name="nim" placeholder="NIM" value="<?= htmlspecialchars($old_input['nim'] ?? '') ?>">
                <div class="error-message" id="nim-error"></div>
            </div>
            <div class="form-group form-group-dynamic field-hidden" id="nidn-group">
                <label for="nidn" class="sr-only">NIDN</label>
                <input type="text" id="nidn" name="nidn" placeholder="NIDN" value="<?= htmlspecialchars($old_input['nidn'] ?? '') ?>">
                <div class="error-message" id="nidn-error"></div>
            </div>
        </div>

        <div class="form-group">
            <label for="email" class="sr-only">Email</label>
            <input type="email" id="email" name="email" placeholder="Masukkan email aktif kamu" value="<?= htmlspecialchars($old_input['email'] ?? '') ?>" required>
            <div class="error-message" id="email-error"></div>
        </div>
        
        <div class="form-group">
            <div class="password-wrapper"> 
                <label for="password" class="sr-only">Password</label>
                <input type="password" id="password" name="password" placeholder="Buat kata sandi yang mudah kamu ingat" required>
                <span class="toggle-password" data-target="password">🙈</span>
            </div>
            <div class="error-message" id="password-error"></div>
        </div>

        <div class="form-group">
            <div class="password-wrapper">
                <label for="konfirmasi-pass" class="sr-only">Konfirmasi Password</label>
                <input type="password" id="konfirmasi-pass" name="konfirmasi-pass" placeholder="Ulangi kata sandimu sekali lagi" required>
                <span class="toggle-password" data-target="konfirmasi-pass">🙈</span>
            </div>
            <div class="error-message" id="konfirmasi-pass-error"></div>
        </div>

        <div class="form-group">
            <label for="no-stnk" class="sr-only">Nomor STNK</label>
            <input type="text" id="no-stnk" name="no-stnk" placeholder="Kendaraan kamu punya STNK? Tulis nomornya di sini ya" value="<?= htmlspecialchars($old_input['no-stnk'] ?? '') ?>" required>
            <div class="error-message" id="no-stnk-error"></div>
        </div>
        
        <div class="form-group">
            <label for="plat-nomor" class="sr-only">Plat Nomor</label>
            <input type="text" id="plat-nomor" name="plat-nomor" placeholder="Tuliskan plat nomor kendaraanmu di sini" value="<?= htmlspecialchars($old_input['plat-nomor'] ?? '') ?>" required>
            <div class="error-message" id="plat-nomor-error"></div>
        </div>

        <button type="submit" class="btn-daftar">Daftar</button>

        <p class="login-link">
            Sudah punya akun? <a href="login.php">Login</a>
        </p>
    </form>
    <script src="Js/prototype7.js"></script>
</body>
</html>