<?php
session_start();
include 'config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token_raw = $_POST['token'];
    $password = $_POST['password'];
    $password_confirm = $_POST['confirm'];

    // Validasi input dasar
    if (empty($token_raw) || empty($password) || empty($password_confirm)) {
        $_SESSION['message'] = 'Semua field wajib diisi.';
        $_SESSION['message_type'] = 'error';
        header('Location: reset_password.php?token=' . urlencode($token_raw));
        exit;
    }

    if ($password !== $password_confirm) {
        $_SESSION['message'] = 'Password dan konfirmasi password tidak cocok.';
        $_SESSION['message_type'] = 'error';
        header('Location: reset_password.php?token=' . urlencode($token_raw));
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['message'] = 'Password minimal 6 karakter.';
        $_SESSION['message_type'] = 'error';
        header('Location: reset_password.php?token=' . urlencode($token_raw));
        exit;
    }

    // Validasi token
    $token_hash = hash('sha256', $token_raw);
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ?");
    $stmt->bind_param("ss", $token_hash, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $_SESSION['message'] = 'Token tidak valid atau telah kadaluwarsa.';
        $_SESSION['message_type'] = 'error';
        header('Location: forgot_password.php'); // mengarahkan ke forgot_password.php agar bisa minta token baru
        exit;
    }

    // Token valid, - ambil email
    $reset_request = $result->fetch_assoc();
    $user_email = $reset_request['email'];
    $stmt->close();

    // Update password user
    $new_password_hash = password_hash($password, PASSWORD_DEFAULT); 

    $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt_update->bind_param("ss", $new_password_hash, $user_email);
    $stmt_update->execute();
    $stmt_update->close();

    // Menghapus token yang sudah dipakai
    $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $user_email);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Mengirim pesan sukses ke halaman login
    $_SESSION['login_message'] = 'Password berhasil diubah. Silakan login.';
    header('Location: login.php');
    exit;

} else {
    header('Location: login.php');
    exit;
}
?>