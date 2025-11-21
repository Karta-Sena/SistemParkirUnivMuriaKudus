<?php
// FILE: generate_qr.php

// 1. Matikan semua output text/error agar tidak merusak header gambar PNG
ini_set('display_errors', 0);
error_reporting(0);

// 2. Cek dan Panggil Library
if (file_exists(__DIR__ . '/phpqrcode.php')) {
    require_once __DIR__ . '/phpqrcode.php';
} else {
    // Fallback: Buat gambar error merah jika library hilang
    header("Content-Type: image/png");
    $im = imagecreate(300, 50);
    $bg = imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, 255, 0, 0);
    imagestring($im, 5, 5, 5, "Error: phpqrcode.php missing", $text_color);
    imagepng($im);
    exit;
}

// 3. Bersihkan buffer output (PENTING: Hapus spasi/enter tak sengaja)
// Ini mencegah error "The image cannot be displayed because it contains errors"
while (ob_get_level()) ob_end_clean();

// 4. Generate QR Code
if (isset($_GET['text'])) {
    $text = $_GET['text'];
    
    // Set Header agar browser tahu ini gambar PNG
    header("Content-Type: image/png");
    
    // Render PNG langsung ke output
    // Parameter: text, outfile(false=stream), level, size, margin
    QRcode::png($text, false, QR_ECLEVEL_H, 10, 2);
    exit;
} else {
    // Jika diakses tanpa parameter, kirim gambar kosong transparan 1x1 pixel
    // atau header 404 agar browser tidak bingung
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>