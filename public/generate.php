<?php

require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

use Endroid\QrCode\Builder\Builder;


if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
    $text = $_POST['url-input'];
    $qrCode = new QrCode($text);
    $qrCode->setSize(300); 
    $qrCode->setMargin(10); 
    // buat logo
    $qrCode    
    ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
    ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255)); // Latar belakang putih



    $logo = Logo::create("images/Logo.jpg")
        ->setResizeToWidth(70)
        ->setPunchoutBackground(true);


     
    $writer = new PngWriter;

    $result = $writer->write($qrCode, logo:$logo);

    header("Content-Type: " . $result->getMimeType());
    $imageData  = $result->getString();
    $base64Image = base64_encode($imageData); //ubah ke base64 agar bisa ditampilkan di HTML

    echo $base64Image;
} else {
    echo "Error: No URL provided!";
    //kirim error jika tidak ada URL yang diberikan
    
    exit; // Stop jika tidak ada URL diberikan
}
