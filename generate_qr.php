<?php

// Matikan display error agar warning PHP tidak merusak struktur gambar PNG
ini_set('display_errors', 0);
error_reporting(0);

// Pastikan path ke library benar. 
// Cek apakah folder anda bernama 'phpqrcode' atau 'phpqrcode-master'?
if (!file_exists("phpqrcode/qrlib.php")) {
    // Jika library tidak ketemu, kita kirim gambar error sederhana buatan PHP
    header("Content-Type: image/png");
    $im = imagecreate(300, 50);
    $bg = imagecolorallocate($im, 255, 200, 200);
    $text_color = imagecolorallocate($im, 255, 0, 0);
    imagestring($im, 5, 5, 15, "Error: Library phpqrcode tidak ditemukan!", $text_color);
    imagepng($im);
    imagedestroy($im);
    exit;
}

require_once "phpqrcode/qrlib.php"; 

// Bersihkan output buffer sebelum kirim header gambar
// Ini menghapus spasi/enter tidak sengaja di file config atau include lain
if (ob_get_length()) ob_clean();

if(isset($_GET['text'])){
    $text = $_GET['text'];
    
    // Set Header agar browser tahu ini adalah gambar PNG
    header("Content-Type: image/png");
    
    // Render gambar PNG langsung ke output stream
    // Level: H (High), Ukuran Pixel: 10, Margin: 2
    QRcode::png($text, false, QR_ECLEVEL_H, 10, 2); 
    exit; // Penting: Hentikan script di sini agar tidak ada footer HTML yang ikut
}
?>