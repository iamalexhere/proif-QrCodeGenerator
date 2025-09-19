<?php
require '../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;

try {
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        $longUrl = trim($_POST['url-input']);

        // Buat shortlink via API v.gd
        $shortUrl = file_get_contents('https://v.gd/create.php?format=simple&url=' . urlencode($longUrl));
        if ($shortUrl === false || empty($shortUrl)) {
            $shortUrl = $longUrl; // fallback jika gagal
        }

        // Ambil warna dari input user
        $hexColor = isset($_POST['color']) ? $_POST['color'] : '#000000';
        $hexColor = ltrim($hexColor, '#');

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Buat QR Code
        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(400);
        $qrCode->setMargin(10);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
               ->setForegroundColor(new Color($r, $g, $b))
               ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();

        $result = null;

        // 🔹 Jika user pilih logo bawaan
        if (!empty($_POST['preset-logo'])) {
            $presetLogo = basename($_POST['preset-logo']); // amankan input
            $presetPath = __DIR__ . "/images/" . $presetLogo;

            if (file_exists($presetPath)) {
                $logo = Logo::create($presetPath)->setResizeToWidth(150);
                $result = $writer->write($qrCode, logo: $logo);
            } else {
                $result = $writer->write($qrCode); // fallback jika file tidak ada
            }
        }
        // 🔹 Jika user upload logo custom
        elseif (isset($_FILES['logo-upload']) && $_FILES['logo-upload']['error'] === UPLOAD_ERR_OK) {
            $logoTmpPath = $_FILES['logo-upload']['tmp_name'];
            $logo = Logo::create($logoTmpPath)->setResizeToWidth(150);
            $result = $writer->write($qrCode, logo: $logo);
        }
        // 🔹 Tanpa logo
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
    ob_end_clean();
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
