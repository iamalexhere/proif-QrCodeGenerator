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
                ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));

        $writer = new PngWriter;


        if (isset($_POST['use-logo']) && $_POST['use-logo'] == 'yes') {
            $logo = Logo::create("images/Logo.jpg")
                        ->setResizeToWidth(70)
                        ->setPunchoutBackground(true);
            $result = $writer->write($qrCode, logo:$logo);
        } else {
            $result = $writer->write($qrCode);
        }

        $imageData  = $result->getString();
        $base64Image = base64_encode($imageData);

        ob_end_clean();
        echo json_encode([
            'image' => $base64Image,
        ]);
    } else {
        ob_end_clean();
        echo json_encode(['error' => 'No URL provided!']);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'An error occurred!']);
}
