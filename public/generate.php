<?php

// Pastikan tidak ada output sebelum JSON response
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

// Memuat semua library dari Composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/UrlShortener.php';
require_once __DIR__ . '/../config/Config.php';

// Mengimpor class yang dibutuhkan
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel;

try {
    if (isset($_POST['url-input']) && !empty($_POST['url-input'])) {
        
        // --- MENGAMBIL DATA DARI FORM ---
        $longUrl = trim($_POST['url-input']);
        $qrColor = $_POST['qr_color'] ?? '#000000'; // Warna dari color picker, default hitam
        $format = $_POST['format'] ?? 'png'; // Format output: png, svg, pdf
        $logoPathForDb = null;
        $logoToUse = null;

        // --- LOGIKA PEMILIHAN LOGO (DENGAN PRIORITAS) ---

        // Prioritas 1: Cek apakah ada logo kustom yang diunggah
        if (isset($_FILES['custom-logo']) && $_FILES['custom-logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = uniqid() . '-' . basename($_FILES['custom-logo']['name']);
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['custom-logo']['tmp_name'], $uploadPath)) {
                $logoPathForDb = $uploadPath; // Simpan path logo kustom
            }
        } 
        // Prioritas 2: Jika tidak ada logo kustom, cek apakah ada logo bawaan yang dipilih
        else if (isset($_POST['default-logo']) && !empty($_POST['default-logo'])) {
            $defaultLogoName = basename($_POST['default-logo']);
            // Pastikan path ini sesuai dengan lokasi logo bawaan Anda
            $logoPathForDb = 'images/' . $defaultLogoName; 
        }

        // Jika ada path logo yang terpilih dan filenya ada, siapkan objek Logo
        if ($logoPathForDb !== null && file_exists($logoPathForDb)) {
            $logoToUse = Logo::create($logoPathForDb)
                             ->setResizeToWidth(100);
        }

        // --- LOGIKA SHORT LINK DENGAN CUSTOM URL SHORTENER ---
        $customUrlInput = ''; 
        try {
            $urlShortener = new UrlShortener();
            $result = $urlShortener->createShortUrl($longUrl, $customUrlInput, $logoPathForDb, $qrColor);
            $shortUrl = $result['short_url'];
        } catch (Exception $e) {
            // Fallback ke URL asli jika gagal
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
        $qrCode->setBackgroundColor(new Color(255, 255, 255));
        
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
                    // Generate PNG first untuk PDF
                    $pngWriter = new PngWriter();
                    $pngResult = $pngWriter->write($qrCode, logo: $logoToUse);
                    
                    // Create PDF dengan TCPDF
                    $pdf = new TCPDF();
                    $pdf->AddPage();
                    $pdf->SetFont('helvetica', 'B', 16);
                    $pdf->Cell(0, 10, 'QR Code', 0, 1, 'C');
                    
                    // Add QR code image ke PDF
                    // Try direct image embedding first
                    try {
                        // Use TCPDF's Image method with string data
                        $pdf->Image('@' . $pngResult->getString(), 55, 30, 100, 100, 'PNG');
                    } catch (Exception $directImageError) {
                        // Fallback to temporary file method
                        $tempDir = sys_get_temp_dir();
                        if (empty($tempDir) || !is_writable($tempDir)) {
                            // Fallback to uploads directory
                            $tempDir = __DIR__ . '/uploads';
                            if (!is_dir($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                        }
                        
                        $tempFile = $tempDir . '/qr_' . uniqid() . '.png';
                        if (empty($tempFile)) {
                            throw new Exception('Could not create temporary file for PDF generation');
                        }
                        
                        $writeResult = file_put_contents($tempFile, $pngResult->getString());
                        if ($writeResult === false) {
                            throw new Exception('Could not write QR code image to temporary file');
                        }
                        
                        // Verify file exists and has content
                        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                            throw new Exception('Temporary QR code file is empty or not created');
                        }
                        
                        // Add image to PDF with error handling
                        try {
                            $pdf->Image($tempFile, 55, 30, 100, 100, 'PNG');
                        } catch (Exception $imageError) {
                            @unlink($tempFile);
                            throw new Exception('Could not add QR code image to PDF: ' . $imageError->getMessage());
                        }
                        
                        @unlink($tempFile);
                    }
                    
                    // Add URL info
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->Cell(0, 10, '', 0, 1); // spacing
                    $pdf->Cell(0, 150, '', 0, 1); // spacing
                    $pdf->Cell(0, 10, 'URL: ' . $shortUrl, 0, 1, 'C');
                    
                    $imageData = $pdf->Output('', 'S');
                    $mimeType = 'application/pdf';
                    $fileExtension = 'pdf';
                } catch (Exception $pdfError) {
                    // Log the error and return it
                    @error_log('PDF generation error: ' . $pdfError->getMessage());
                    ob_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'PDF generation failed: ' . $pdfError->getMessage()]);
                    exit;
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
        
        // --- MENGIRIM RESPONSE KE FRONTEND ---
        $base64Image = base64_encode($imageData);

        // Bersihkan buffer dan set header
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'image' => $base64Image,
            'short_link' => $shortUrl,
            'format' => $format,
            'mime_type' => $mimeType,
            'file_extension' => $fileExtension
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