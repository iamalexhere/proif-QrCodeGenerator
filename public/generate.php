<?php

require '../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Builder\Builder;


if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
    $text = $_POST['url-input'];
    $qrCode = new QrCode($text);
    $qrCode->setSize(300); 
    $qrCode->setMargin(10); 
    // buat logo
    $logo = Logo::create("../public/images/logo_small.png")
        ->setResizeToWidth(50);
     
    $writer = new PngWriter;

    $result = $writer->write($qrCode, logo:$logo);

    header("Content-Type: " . $result->getMimeType());
    echo $result->getString();
} else {
    echo "Error: No URL provided!";
    exit; // Stop jika tidak ada URL diberikan
}
