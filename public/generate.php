<?php
require '../vendor/autoload.php';

/*
Menerima URL dari halam web, 
mengubah menjadi gambar QR, 
dan menampilkan gambar kepada user
*/

//library untuk membuat QR code dari URL
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;

try {
    //Menerima input pengguna
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        $longUrl = trim($_POST['url-input']); //mengambil input URL

        // buat shortlink via API v.gd
        $shortUrl = file_get_contents('https://v.gd/create.php?format=simple&url=' . urlencode($longUrl));
        if ($shortUrl === false || empty($shortUrl)) {
            // akan melakukan fallback jika gagal 
            $shortUrl = $longUrl;
        }

        // buat QR Code dari shortlink
        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
               ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));

        $writer = new PngWriter; //Mengubah jadi format PNG

        //Jika user mau menggunakan logo IF UNPAR
        if (isset($_POST['use-logo']) && $_POST['use-logo'] == 'yes') {
            $logo = Logo::create("images/Logo.jpg")
                        ->setResizeToWidth(70)
                        ->setPunchoutBackground(true);
            $result = $writer->write($qrCode, logo:$logo);
        } else {
            $result = $writer->write($qrCode);
        }

        //Gambar QR code dari $result --> diubah menjadi teks #base64Image --> dibungkus dalam paket JSON --> dikirim ke browser
        $imageData  = $result->getString();
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
    echo json_encode(['error' => 'An error occurred: '.$e->getMessage()]);
}
