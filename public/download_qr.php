<?php
/**
 * QR Code Download Handler
 * 
 * This script handles downloading QR code images from the database
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;

// Require authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

// Get short code from request
$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    http_response_code(400);
    die('Missing QR code identifier');
}

// Sanitize the short code
$shortCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $shortCode);

// Get database connection
$db = Database::getInstance()->getConnection();

// Fetch QR code data - ensure it belongs to the current user
$stmt = $db->prepare("SELECT qr_image, short_url, custom_url, original_url FROM links WHERE short_url = ? AND user_id = ?");
$stmt->bind_param("si", $shortCode, $currentUser['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die('QR code not found or access denied');
}

$qrData = $result->fetch_assoc();
$stmt->close();

// Determine filename
$filename = 'qr_code';
if (!empty($qrData['custom_url'])) {
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $qrData['custom_url']);
} elseif (!empty($qrData['original_url'])) {
    $host = parse_url($qrData['original_url'], PHP_URL_HOST);
    if ($host) {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $host);
    }
}

// Check if QR image exists in database
if (!empty($qrData['qr_image'])) {
    // Serve from database
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $filename . '_qr.png"');
    header('Content-Length: ' . strlen($qrData['qr_image']));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    echo $qrData['qr_image'];
    exit;
} else {
    // Generate QR code on-the-fly using the QR library
    try {
        // Get the full short URL
        $baseUrl = Config::get('SHORT_DOMAIN', 'localhost/qr/r');
        $shortUrl = (strpos($baseUrl, 'http') === 0 ? '' : 'http://') . $baseUrl . '/' . $shortCode;
        
        // Create QR code
        $qrCode = QrCode::create($shortUrl)
            ->setSize(300)
            ->setMargin(10)
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // Output the QR code
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '_qr.png"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        $generatedImageData = $result->getString();
        
        // Save this generated image to database for future use
        try {
            $updateStmt = $db->prepare("UPDATE links SET qr_image = ? WHERE short_url = ? AND user_id = ?");
            $updateStmt->bind_param("bsi", $null, $shortCode, $currentUser['id']);
            $updateStmt->send_long_data(0, $generatedImageData);
            $updateStmt->execute();
            $updateStmt->close();
        } catch (Exception $saveError) {
            error_log('Failed to save generated QR image to database: ' . $saveError->getMessage());
        }
        
        echo $generatedImageData;
        exit;
        
    } catch (Exception $e) {
        error_log('QR Code generation error: ' . $e->getMessage());
        http_response_code(500);
        die('Failed to generate QR code');
    }
}
?>
