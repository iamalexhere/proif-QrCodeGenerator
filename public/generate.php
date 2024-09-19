<?php

require '../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;


try {
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        $text = $_POST['url-input'];
        $qrCode = new QrCode($text);
        $qrCode->setSize(300); 
        $qrCode->setMargin(10); 
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255)); // Latar belakang putih
    
        $writer = new PngWriter;
    
        // Periksa apakah checkbox logo dicentang
        if (isset($_POST['use-logo']) && $_POST['use-logo'] == 'yes') {
            $logo = Logo::create("images/Logo.jpg")
                ->setResizeToWidth(70)
                ->setPunchoutBackground(true);
            $result = $writer->write($qrCode, logo:$logo);
        } else {
            // Jika tidak ingin menggunakan logo
            $result = $writer->write($qrCode);
        }
    
        header("Content-Type: " . $result->getMimeType()); 
        $imageData  = $result->getString(); 
        $base64Image = base64_encode($imageData); // Konversi ke base64 agar bisa ditampilkan di HTML
    
        // Simpan file PNG untuk di-download
        $filename = 'images/generated_qr_code.png';
        file_put_contents($filename, $imageData);
    
        // Hapus output buffer sebelum mengirim respons JSON
        echo json_encode([
            'image' => $base64Image,
            'downloadUrl' => $filename // URL untuk di-download
        ]);
    } else {
        // Hapus output buffer sebelum mengirim respons JSON
        ob_end_clean();
        echo json_encode(['error' => 'No URL provided!']);
        exit; // Stop jika tidak ada URL diberikan
    }
}
catch(Exception $e) {
    // Hapus output buffer sebelum mengirim respons JSON
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
