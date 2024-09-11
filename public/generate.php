<?php

require '../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$text = $_POST['text'];
$qrCode = new QrCode($text);
$writer = new PngWriter();

$result = $writer->write($qrCode);

echo $result->getString();