<?php
require '../vendor/autoload.php';
require_once '../shorturl/UrlShortener.php';

/*
Menerima URL dari web, 
mengubah menjadi gambar QR,
dan menampilkan gambar kepada user
*/

//library untuk membuat QRCode dari URL
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;

try {
    //Menerima input pengguna 
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        $longUrl = trim($_POST['url-input']);

        // Buat shortlink dengan custom URL shortener
        try {
            $urlShortener = new UrlShortener();
            $result = $urlShortener->createShortUrl($longUrl);
            $shortUrl = $result['short_url'];
        } catch (Exception $e) {
            // Fallback ke URL asli jika gagal
            $shortUrl = $longUrl;
            error_log('URL shortener error: ' . $e->getMessage());
        }

        // Ambil warna dari input user
        // Ambil warna dari input user default nya hitam 
        $hexColor = isset($_POST['color']) ? $_POST['color'] : '#000000';

        // Hilangkan simbol # dari kode hex
        $hexColor = ltrim($hexColor, '#');

        // Ambil 2 digit pertama → nilai Red
        $r = hexdec(substr($hexColor, 0, 2));
        // Ambil 2 digit berikutnya → nilai Green
        $g = hexdec(substr($hexColor, 2, 2));
        // Ambil 2 digit terakhir → nilai Blu
        $b = hexdec(substr($hexColor, 4, 2));

        // Buat QR Code dari short link 
        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(400); //ukuran QRCode
        $qrCode->setMargin(10); //Margin warna putih sekitar QR
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                // Set warna QR sesuai input user
               ->setForegroundColor(new Color($r, $g, $b))
               // Set warna background jadi putih
               ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter(); //Mengubah jadi format PNG

        // Variabel hasil QR Code
        $result = null;

        // Jika user pilih logo bawaan
        if (!empty($_POST['preset-logo'])) {
            // Ambil nama file logo, amankan dari path traversal
            $presetLogo = basename($_POST['preset-logo']);
            // Path logo disimpan di folder `images/`
            $presetPath = __DIR__ . "/images/" . $presetLogo;

            if (file_exists($presetPath)) {
                $logo = Logo::create($presetPath)->setResizeToWidth(150);
                $result = $writer->write($qrCode, logo: $logo);
            } else {
                $result = $writer->write($qrCode); // fallback jika file tidak ada
            }
        }
        // Jika user upload logo custom
        elseif (isset($_FILES['logo-upload']) && $_FILES['logo-upload']['error'] === UPLOAD_ERR_OK) {
            $logoTmpPath = $_FILES['logo-upload']['tmp_name'];
            $logo = Logo::create($logoTmpPath)->setResizeToWidth(150);
            $result = $writer->write($qrCode, logo: $logo);
        }
        // Tanpa logo
        else {
            $result = $writer->write($qrCode);
        }

        // Convert hasil ke base64 untuk ditampilkan di browser
        $imageData = $result->getString();
        $base64Image = base64_encode($imageData);

        ob_end_clean();
        echo json_encode([
            'image'      => $base64Image,
            'short_link' => $shortUrl
        ]);
    } else {
        ob_end_clean();
        echo json_encode(['error' => 'No URL provided!']);
        exit;
    }
} catch (Exception $e) {
    //Jika terjadi error --> kirim pesan error
    ob_end_clean();
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
