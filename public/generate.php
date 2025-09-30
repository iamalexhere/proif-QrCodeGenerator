<?php

// Memuat semua library dari Composer
require '../vendor/autoload.php';
require_once '../classes/UrlShortener.php';
require_once '../config/Config.php';

// Mengimpor class yang dibutuhkan
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;

try {
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        
        // --- MENGAMBIL DATA DARI FORM ---
        $longUrl = trim($_POST['url-input']);
        $qrColor = $_POST['qr_color'] ?? '#000000';
        $logoPathForDb = null;
        $logoToUse = null;

        // --- LOGIKA PEMILIHAN LOGO (DENGAN PRIORITAS) ---
        if (isset($_FILES['custom-logo']) && $_FILES['custom-logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = uniqid() . '-' . basename($_FILES['custom-logo']['name']);
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['custom-logo']['tmp_name'], $uploadPath)) {
                $logoPathForDb = $uploadPath;
            }
        } 
        else if (isset($_POST['default-logo']) && !empty($_POST['default-logo'])) {
            $defaultLogoName = basename($_POST['default-logo']);
            $logoPathForDb = 'images/' . $defaultLogoName; 
        }

        if ($logoPathForDb !== null && file_exists($logoPathForDb)) {
            $logoToUse = Logo::create($logoPathForDb)
                             ->setResizeToWidth(100);
        }

        // --- LOGIKA SHORT LINK DENGAN CUSTOM URL SHORTENER ---
        $customUrlInput = '';
        $status = 'active'; // **PERBAIKAN 1: Menyiapkan status default**

        try {
            $urlShortener = new UrlShortener();
            // **PERBAIKAN 2: Mengirim semua parameter yang dibutuhkan, termasuk $status**
            $result = $urlShortener->createShortUrl($longUrl, $customUrlInput, $logoPathForDb, $qrColor, $status);
            $shortUrl = $result['short_url'];
        } catch (Exception $e) {
            $shortUrl = $longUrl;
            error_log('URL shortener error: ' . $e->getMessage());
        }

        // --- PEMBUATAN QR CODE ---
        function hexToColor(string $hex): Color {
            $hex = ltrim($hex, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return new Color($r, $g, $b);
        }

        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High);
        $qrCode->setForegroundColor(hexToColor($qrColor));
        $qrCode->setBackgroundColor(new Color(255, 255, 255));
        
        $writer = new PngWriter;
        $result = $writer->write($qrCode, logo: $logoToUse);
        
        // --- MENGIRIM RESPONSE KE FRONTEND ---
        $imageData   = $result->getString();
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
    ob_end_clean();
    echo json_encode(['error' => 'An error occurred: '.$e->getMessage()]);
}