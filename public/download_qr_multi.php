<?php
/**
 * Multi-Format QR Code Download Handler
 * 
 * This script handles downloading QR code images in multiple formats (PNG, SVG, PDF)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;

// Require authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

// Get parameters from request
$shortCode = $_GET['code'] ?? '';
$format = $_GET['format'] ?? 'png';

if (empty($shortCode)) {
    http_response_code(400);
    die('Missing QR code identifier');
}

// Validate format
$allowedFormats = ['png', 'svg', 'pdf'];
if (!in_array($format, $allowedFormats)) {
    http_response_code(400);
    die('Invalid format. Allowed: ' . implode(', ', $allowedFormats));
}

// Sanitize the short code
$shortCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $shortCode);

// Get database connection
$db = Database::getInstance()->getConnection();

// Fetch QR code data - ensure it belongs to the current user
$stmt = $db->prepare("SELECT qr_image, short_url, custom_url, original_url, logo_path, qr_color FROM links WHERE short_url = ? AND user_id = ?");
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

// Get the full short URL for QR generation
$baseUrl = Config::get('SHORT_DOMAIN', 'localhost/qr/r');
$shortUrl = (strpos($baseUrl, 'http') === 0 ? '' : 'http://') . $baseUrl . '/' . $shortCode;

try {
    // Helper function to convert hex to Color
    function hexToColor(string $hex): Color {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            $hex = '000000'; // Default to black if invalid
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return new Color($r, $g, $b);
    }

    // Create QR code
    $qrCode = QrCode::create($shortUrl)
        ->setSize(300)
        ->setMargin(10)
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
        ->setForegroundColor(hexToColor($qrData['qr_color'] ?? '#000000'))
        ->setBackgroundColor(new Color(255, 255, 255));

    // Handle logo if exists
    $logoToUse = null;
    if (!empty($qrData['logo_path']) && file_exists($qrData['logo_path'])) {
        $logoToUse = Logo::create($qrData['logo_path'])->setResizeToWidth(100);
    }

    // === ENSURE QR IMAGE EXISTS IN DATABASE ===
    // If no QR image exists in database, generate and save a PNG version for future use
    if (empty($qrData['qr_image'])) {
        try {
            $pngWriter = new PngWriter();
            $pngResult = $pngWriter->write($qrCode, logo: $logoToUse);
            $pngImageData = $pngResult->getString();
            
            // Save PNG to database for future use
            $updateStmt = $db->prepare("UPDATE links SET qr_image = ? WHERE short_url = ? AND user_id = ?");
            $updateStmt->bind_param("bsi", $null, $shortCode, $currentUser['id']);
            $updateStmt->send_long_data(0, $pngImageData);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Update our local data
            $qrData['qr_image'] = $pngImageData;
        } catch (Exception $e) {
            error_log('Failed to generate fallback QR image: ' . $e->getMessage());
        }
    }

    // Generate based on format
    switch ($format) {
        case 'svg':
            $writer = new SvgWriter();
            $result = $writer->write($qrCode, logo: $logoToUse);
            $imageData = $result->getString();
            $mimeType = 'image/svg+xml';
            $fileExtension = 'svg';
            break;
            
        case 'pdf':
            // Generate PNG first for PDF
            $pngWriter = new PngWriter();
            $pngResult = $pngWriter->write($qrCode, logo: $logoToUse);
            
            // Create PDF with TCPDF
            $pdf = new TCPDF();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'QR Code', 0, 1, 'C');
            
            // Add QR code image to PDF
            $pdf->Image('@' . $pngResult->getString(), 55, 30, 100, 100, 'PNG');
            
            // Add URL info
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, '', 0, 1); // spacing
            $pdf->Cell(0, 150, '', 0, 1); // spacing
            $pdf->Cell(0, 10, 'URL: ' . $shortUrl, 0, 1, 'C');
            
            $imageData = $pdf->Output('', 'S');
            $mimeType = 'application/pdf';
            $fileExtension = 'pdf';
            break;
            
        default: // png
            $writer = new PngWriter();
            $result = $writer->write($qrCode, logo: $logoToUse);
            $imageData = $result->getString();
            $mimeType = 'image/png';
            $fileExtension = 'png';
            break;
    }

    // Output the file
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '_qr.' . $fileExtension . '"');
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    echo $imageData;
    exit;

} catch (Exception $e) {
    error_log('QR Code generation error: ' . $e->getMessage());
    http_response_code(500);
    die('Failed to generate QR code: ' . $e->getMessage());
}
?>
