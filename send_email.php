<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function send_reset_email($to_email, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Pengaturan Server
        // $mail->SMTPDebug = SMTP -> DEBUG_SERVER; // Aktifkan bagian ini untuk debugging cuy
        $mail->SMTPDebug = 0; // Set ke 0 untuk non-aktif
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // KREDENSIAL AKUN PENGIRIM
        $mail->Username   = 'a.parkir.bydev@gmail.com'; // Email pengirim
        $mail->Password   = 'nxqe iyrx bcht skoz'; // App Password dari google

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Pengirim
        $mail->setFrom('a.parkir.bydev@gmail.com', 'Admin Parkir UMK');

        // Penerima
        $mail->addAddress($to_email); // Email user yang lupa password

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Konten plain-text

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>