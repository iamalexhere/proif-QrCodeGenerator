<?php
// Pastikan tidak ada output sebelum JSON response
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

// Memuat semua library dari Composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/UrlShortener.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../classes/Database.php';

// Require authentication - user must be logged in
Auth::requireAuth('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'generate.php'));

// Get current user
$currentUser = Auth::getCurrentUser();
if (!$currentUser) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Mengimpor class yang dibutuhkan
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;

try {
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        
        // --- CHECK USER QUOTA WITH ATOMIC TRANSACTION ---
        $db = Database::getInstance()->getConnection();
        $db->begin_transaction();
        
        try {
            // Lock the user quota row for update to prevent race conditions
            $monthYear = date('Y-m');
            $limit = Config::getPlanLimit($currentUser['plan'], 'qr_codes_per_month', 10);
            
            // Get or create quota record with lock
            $stmt = $db->prepare("
                INSERT INTO user_quotas (user_id, month_year, qr_codes_created)
                VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE qr_codes_created = qr_codes_created
            ");
            $stmt->bind_param("is", $currentUser['id'], $monthYear);
            $stmt->execute();
            $stmt->close();
            
            // Now lock and check the quota
            $stmt = $db->prepare("
                SELECT qr_codes_created 
                FROM user_quotas 
                WHERE user_id = ? AND month_year = ?
                FOR UPDATE
            ");
            $stmt->bind_param("is", $currentUser['id'], $monthYear);
            $stmt->execute();
            $result = $stmt->get_result();
            $quota = $result->fetch_assoc();
            $stmt->close();
            
            $used = $quota ? $quota['qr_codes_created'] : 0;
            
            if ($used >= $limit) {
                $db->rollback();
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'Monthly QR code limit reached',
                    'quota_info' => [
                        'used' => $used,
                        'limit' => $limit,
                        'plan' => $currentUser['plan']
                    ],
                    'upgrade_required' => true
                ]);
                exit;
            }
            
            // Reserve the quota slot immediately
            $stmt = $db->prepare("
                UPDATE user_quotas 
                SET qr_codes_created = qr_codes_created + 1
                WHERE user_id = ? AND month_year = ?
            ");
            $stmt->bind_param("is", $currentUser['id'], $monthYear);
            $stmt->execute();
            $stmt->close();
            
            // Commit the quota reservation
            $db->commit();
            
            // Get updated quota for response
            $newUsed = $used + 1;
            
        } catch (Exception $e) {
            $db->rollback();
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Quota check failed: ' . $e->getMessage()]);
            exit;
        }
        
        // --- MENGAMBIL DATA DARI FORM ---
        $longUrl = trim($_POST['url-input']);
        $qrColor = $_POST['qr_color'] ?? '#000000';
        $qrBgColor = $_POST['qr_bg_color'] ?? '#FFFFFF';
        $format = $_POST['format'] ?? 'png';
        $logoPathForDb = null;
        $logoToUse = null;

        // --- LOGIKA PEMILIHAN LOGO (DENGAN PRIORITAS) ---
        if (isset($_FILES['custom-logo']) && $_FILES['custom-logo']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['custom-logo'];
            
            // Validate file size (max 5MB)
            $maxFileSize = 5 * 1024 * 1024;
            if ($uploadedFile['size'] > $maxFileSize) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Logo file too large. Maximum size is 5MB.']);
                exit;
            }
            
            // Validate file type by MIME type and extension
            $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            $allowedExtensions = ['png', 'jpg', 'jpeg'];
            
            $fileMimeType = mime_content_type($uploadedFile['tmp_name']);
            $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Invalid file type. Only PNG and JPG files are allowed.']);
                exit;
            }
            
            // Validate that it's actually an image
            $imageInfo = getimagesize($uploadedFile['tmp_name']);
            if ($imageInfo === false) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Invalid image file. File appears to be corrupted.']);
                exit;
            }
            
            // Check image dimensions
            $maxWidth = 2000;
            $maxHeight = 2000;
            if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => "Image dimensions too large. Maximum size is {$maxWidth}x{$maxHeight} pixels."]);
                exit;
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate secure filename
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
                $logoPathForDb = $uploadPath;
            } else {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Failed to upload logo file.']);
                exit;
            }
        } else if (isset($_POST['default-logo']) && !empty($_POST['default-logo'])) {
            $defaultLogoName = basename($_POST['default-logo']);
            $logoPathForDb = 'images/' . $defaultLogoName; 
        }

        if ($logoPathForDb !== null && file_exists($logoPathForDb)) {
            $logoToUse = Logo::create($logoPathForDb)->setResizeToWidth(100);
        }

        // --- LOGIKA SHORT LINK DENGAN CUSTOM URL SHORTENER ---
        $customUrlInput = ''; 
        try {
            $urlShortener = new UrlShortener();
            $result = $urlShortener->createShortUrl($longUrl, $currentUser['id'], $customUrlInput, $logoPathForDb, $qrColor, $qrBgColor);
            $shortUrl = $result['short_url'];
            $shortCode = $result['short_code'];
            $isExistingUrl = $result['existing'] ?? false;
            
            // If URL already exists for this user, give back the quota slot
            if ($isExistingUrl) {
                Auth::decrementQRCodeUsage($currentUser['id'], true);
                $newUsed = $newUsed - 1; // Adjust the quota count for response
            }
        } catch (Exception $e) {
            $shortUrl = $longUrl;
            error_log('URL shortener error: ' . $e->getMessage());
        }

        // --- PEMBUATAN QR CODE ---
        function hexToColor(string $hex): Color {
            $hex = ltrim($hex, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return new Color($r, $g, $b);
        }

        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High);
        $qrCode->setForegroundColor(hexToColor($qrColor));
        $qrCode->setBackgroundColor(hexToColor($qrBgColor));
        
        // --- MENENTUKAN WRITER BERDASARKAN FORMAT ---
        switch ($format) {
            case 'svg':
                $writer = new SvgWriter();
                $result = $writer->write($qrCode, logo: $logoToUse);
                $imageData = $result->getString();
                $mimeType = 'image/svg+xml';
                $fileExtension = 'svg';
                break;
                
            case 'pdf':
                try {
                    $pngWriter = new PngWriter();
                    $pngResult = $pngWriter->write($qrCode, logo: $logoToUse);
                    
                    $pdf = new TCPDF();
                    $pdf->AddPage();
                    $pdf->SetFont('helvetica', 'B', 16);
                    $pdf->Cell(0, 10, 'QR Code', 0, 1, 'C');
                    
                    $tempFile = null;
                    try {
                        $pdf->Image('@' . $pngResult->getString(), 55, 30, 100, 100, 'PNG');
                    } catch (Exception $directImageError) {
                        $tempDir = sys_get_temp_dir();
                        if (empty($tempDir) || !is_writable($tempDir)) {
                            $tempDir = __DIR__ . '/uploads';
                            if (!is_dir($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                        }
                        
                        $tempFile = $tempDir . '/qr_' . uniqid() . '_' . time() . '.png';
                        
                        $writeResult = file_put_contents($tempFile, $pngResult->getString());
                        if ($writeResult === false) {
                            throw new Exception('Could not write QR code image to temporary file');
                        }
                        
                        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                            @unlink($tempFile);
                            throw new Exception('Temporary QR code file is empty or not created');
                        }
                        
                        $pdf->Image($tempFile, 55, 30, 100, 100, 'PNG');
                    }
                    
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->Cell(0, 10, '', 0, 1);
                    $pdf->Cell(0, 150, '', 0, 1);
                    $pdf->Cell(0, 10, 'URL: ' . $shortUrl, 0, 1, 'C');
                    
                    $imageData = $pdf->Output('', 'S');
                    $mimeType = 'application/pdf';
                    $fileExtension = 'pdf';
                    
                } catch (Exception $pdfError) {
                    @error_log('PDF generation error: ' . $pdfError->getMessage());
                    ob_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'PDF generation failed: ' . $pdfError->getMessage()]);
                    exit;
                } finally {
                    if ($tempFile && file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                }
                break;
                
            default: // png
                $writer = new PngWriter();
                $result = $writer->write($qrCode, logo: $logoToUse);
                $imageData = $result->getString();
                $mimeType = 'image/png';
                $fileExtension = 'png';
                break;
        }

        // === MENYIMPAN GAMBAR QR KE DATABASE ===
        try {
            $db = Database::getInstance()->getConnection();
            
            $checkStmt = $db->prepare("SELECT qr_image FROM links WHERE short_url = ?");
            $checkStmt->bind_param("s", $shortCode);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $existingData = $checkResult->fetch_assoc();
            $checkStmt->close();
            
            if (empty($existingData['qr_image']) || $format === 'png') {
                $pngImageData = $imageData;
                
                if ($format !== 'png') {
                    $pngWriter = new PngWriter();
                    $pngResult = $pngWriter->write($qrCode, logo: $logoToUse);
                    $pngImageData = $pngResult->getString();
                }
                
                $stmt = $db->prepare("UPDATE links SET qr_image = ? WHERE short_url = ?");
                $stmt->bind_param("bs", $null, $shortCode);
                $stmt->send_long_data(0, $pngImageData);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Failed to save QR image to database: " . $e->getMessage());
        }
        
        // --- MENGIRIM RESPONSE KE FRONTEND ---
        $base64Image = base64_encode($imageData);

        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'image' => $base64Image,
            'short_link' => $shortUrl,
            'format' => $format,
            'mime_type' => $mimeType,
            'file_extension' => $fileExtension,
            'quota_info' => [
                'used' => $newUsed,
                'limit' => $limit,
                'plan' => $currentUser['plan']
            ]
        ]);
        exit;

    } else {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No URL provided!']);
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'An error occurred: '.$e->getMessage()]);
    exit;
}