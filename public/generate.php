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
use Endroid\QrCode\Color\Color; //untuk mengatur warna foreground (warna QRCode)

try {
    //Menerima input pengguna
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        $longUrl = trim($_POST['url-input']);

        // Buat shortlink via API v.gd
        $shortUrl = file_get_contents('https://v.gd/create.php?format=simple&url=' . urlencode($longUrl));
        if ($shortUrl === false || empty($shortUrl)) {
            //akan melakukan fallback jika gagal
            $shortUrl = $longUrl;
        }

        // Ambil warna foreground dari input user (untuk default warna hitam)
        $hexColor = isset($_POST['color']) ? $_POST['color'] : '#000000';

        //Menghapus tanda #, untuk bisa diproses menjadi RGB 
        $hexColor = ltrim($hexColor, '#');

        // konversi hex (#RRGGBB) ke RGB integer
        $r = hexdec(substr($hexColor, 0, 2)); //Red
        $g = hexdec(substr($hexColor, 2, 2)); //Green
        $b = hexdec(substr($hexColor, 4, 2)); //Blue

        // Buat QR Code
        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(400);
        $qrCode->setMargin(10);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
               ->setForegroundColor(new Color($r, $g, $b)) //warna untuk QRCode 
               ->setBackgroundColor(new Color(255, 255, 255)); //background color warna putih

        $writer = new PngWriter; //Mengubah jadi format PNG

        // Jika user mengupload file logo 
        if (isset($_FILES['logo-upload']) && $_FILES['logo-upload']['error'] === UPLOAD_ERR_OK) {
            //Mengambil input logo dari user 
            $logoTmpPath = $_FILES['logo-upload']['tmp_name'];

            //Membuat objek logo 
            $logo = Logo::create($logoTmpPath)
                        ->setResizeToWidth(150)
                        ->setPunchoutBackground(true);

            //Untuk menggabungkan QRCode dengan logo 
            $result = $writer->write($qrCode, logo: $logo);
        } else {
            // Tanpa logo
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
