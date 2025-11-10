<?php
session_start();
include 'config.php';
require 'send_email.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Format email tidak valid.';
        $_SESSION['message_type'] = 'error';
        header('Location: forgot_password.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt_delete->bind_param("s", $email);
        $stmt_delete->execute();
        $stmt_delete->close();

        $token_raw = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token_raw);
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 jam

        $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $email, $token_hash, $expires_at);
        $stmt_insert->execute();

        if ($stmt_insert->affected_rows > 0) {
            $reset_link = "http://localhost/Sistem Parkir UMK/reset_password.php?token=" . $token_raw;
            $subject = "Link Reset Password Parkir UMK";
            $body = "Anda menerima email ini karena ada permintaan reset password untuk akun Anda.<br><br>";
            $body .= "Klik link ini untuk reset password Anda: <br>";
            $body .= "<a href='" . $reset_link . "'>" . $reset_link . "</a><br><br>";
            $body .= "Link ini hanya valid selama 1 jam. <br>";
            $body .= "Jika Anda tidak meminta ini, abaikan email ini.";

            send_reset_email($email, $subject, $body);
        }
        $stmt_insert->close();
    }
    
    $_SESSION['message'] = 'Jika email Anda terdaftar, instruksi reset telah dikirim.';
    $_SESSION['message_type'] = 'success';
    header('Location: forgot_password.php');
    exit;

} else {
    header('Location: forgot_password.php');
    exit;
}
?>