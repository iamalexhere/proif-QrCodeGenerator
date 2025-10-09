<?php
/**
 * Utility Script: Fix Missing QR Images
 * 
 * This script finds QR codes in the database that don't have images
 * and generates fallback PNG images for them.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;

// Require authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

// Only allow admin or the first user to run this script
if ($currentUser['id'] !== 1) {
    die('Access denied. This utility can only be run by administrators.');
}

echo "<h1>QR Image Fix Utility</h1>";
echo "<p>Checking for QR codes without images...</p>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Find all QR codes without images
    $stmt = $db->prepare("SELECT id, short_url, original_url, logo_path, qr_color FROM links WHERE qr_image IS NULL OR qr_image = ''");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $missingCount = $result->num_rows;
    echo "<p>Found <strong>{$missingCount}</strong> QR codes without images.</p>";
    
    if ($missingCount === 0) {
        echo "<p style='color: green;'>✅ All QR codes have images. No action needed.</p>";
        exit;
    }
    
    echo "<h2>Generating Missing QR Images:</h2>";
    echo "<ul>";
    
    $fixedCount = 0;
    $errorCount = 0;
    
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
    
    while ($link = $result->fetch_assoc()) {
        try {
            // Get the full short URL
            $baseUrl = Config::get('SHORT_DOMAIN', 'localhost/qr/r');
            $shortUrl = (strpos($baseUrl, 'http') === 0 ? '' : 'http://') . $baseUrl . '/' . $link['short_url'];
            
            // Create QR code
            $qrCode = QrCode::create($shortUrl)
                ->setSize(300)
                ->setMargin(10)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setForegroundColor(hexToColor($link['qr_color'] ?? '#000000'))
                ->setBackgroundColor(new Color(255, 255, 255));
            
            // Handle logo if exists
            $logoToUse = null;
            if (!empty($link['logo_path']) && file_exists($link['logo_path'])) {
                $logoToUse = Logo::create($link['logo_path'])->setResizeToWidth(100);
            }
            
            // Generate PNG
            $writer = new PngWriter();
            $result_qr = $writer->write($qrCode, logo: $logoToUse);
            $imageData = $result_qr->getString();
            
            // Save to database
            $updateStmt = $db->prepare("UPDATE links SET qr_image = ? WHERE id = ?");
            $updateStmt->bind_param("bi", $null, $link['id']);
            $updateStmt->send_long_data(0, $imageData);
            
            if ($updateStmt->execute()) {
                echo "<li style='color: green;'>✅ Fixed QR image for: <strong>{$link['original_url']}</strong> (Code: {$link['short_url']})</li>";
                $fixedCount++;
            } else {
                echo "<li style='color: red;'>❌ Failed to save QR image for: <strong>{$link['original_url']}</strong></li>";
                $errorCount++;
            }
            
            $updateStmt->close();
            
        } catch (Exception $e) {
            echo "<li style='color: red;'>❌ Error generating QR for <strong>{$link['original_url']}</strong>: " . htmlspecialchars($e->getMessage()) . "</li>";
            $errorCount++;
        }
    }
    
    $stmt->close();
    
    echo "</ul>";
    echo "<h2>Summary:</h2>";
    echo "<p><strong>Total found:</strong> {$missingCount}</p>";
    echo "<p style='color: green;'><strong>Successfully fixed:</strong> {$fixedCount}</p>";
    
    if ($errorCount > 0) {
        echo "<p style='color: red;'><strong>Errors:</strong> {$errorCount}</p>";
    }
    
    if ($fixedCount > 0) {
        echo "<p style='color: green; font-weight: bold;'>🎉 QR image fix completed successfully!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href='dashboardAll.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Dashboard</a>";
?>
