<?php
/**
 * Halaman Redirect URL dengan Iklan
 * Menangani pengalihan dari URL pendek dengan tampilan iklan opsional
 * 
 * Fitur:
 * - Mengambil kode pendek dari URL
 * - Mencari URL asli berdasarkan kode pendek
 * - Menampilkan halaman iklan dengan countdown
 * - Redirect otomatis ke URL tujuan
 */

// Memuat class yang diperlukan
require_once __DIR__ . '/../classes/UrlShortener.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../classes/Statistics.php';

// === MENGAMBIL KODE PENDEK DARI URL ===
$kodePendek = '';

// Cek apakah ada parameter 'code' di URL (format: r.php?code=ABC123)
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $kodePendek = trim($_GET['code']);
} else {
    // Coba ambil dari URL bersih (format: /r/ABC123)
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $kodePendek = end($pathParts);
}

// Jika tidak ada kode pendek, redirect ke halaman utama
if (empty($kodePendek)) {
    header('Location: /');
    exit;
}

// === FUNGSI UNTUK MENAMPILKAN HALAMAN LINK YANG DI-PAUSE ===
function showPausedLinkPage($shortCode) {
    $baseUrl = Config::getBaseUrl();
    $cssPath = rtrim($baseUrl, '/') . '/css/redirect.css';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Link Temporarily Unavailable - AAARO</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath); ?>">
        <meta name="robots" content="noindex, nofollow">
    </head>
    <body>
        <div class="container">
            <?php if (file_exists(__DIR__ . '/images/logo-aaaro.png')): ?>
            <div class="logo">
                <?php 
                $imagePath = rtrim($baseUrl, '/') . '/images/logo-aaaro.png';
                ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="AAARO Logo">
            </div>
            <?php endif; ?>
            
            <h1 style="color: #ff6b6b;">⏸️ LINK TEMPORARILY UNAVAILABLE</h1>
            
            <div class="redirect-info">
                <p style="font-size: 18px; color: #666; margin: 20px 0;">
                    This short link has been temporarily paused by its owner.
                </p>
                <p style="font-size: 16px; color: #888;">
                    <strong>Short Code:</strong> <?php echo htmlspecialchars($shortCode); ?>
                </p>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: center;">
                <h3 style="color: #856404; margin-bottom: 10px;">🔒 Access Restricted</h3>
                <p style="color: #856404; margin: 0;">
                    The owner of this QR code has temporarily disabled access. 
                    Please contact them if you believe this is an error.
                </p>
            </div>
            
            <a href="<?php echo rtrim($baseUrl, '/'); ?>" class="skip-button" style="background: #007bff; text-decoration: none;">
                ← Go to AAARO Homepage
            </a>
        </div>
    </body>
    </html>
    <?php
}

// === PROSES REDIRECT ===
try {
    // Inisialisasi URL Shortener
    $urlShortener = new UrlShortener();
    
    // --- Mengambil data link (termasuk ID) ---
    $linkData = $urlShortener->getLinkDataByShortCode($kodePendek);

    // Jika kode pendek tidak ditemukan
    if (!$linkData) {
        header('Location: ../');
        exit;
    }

    // === CEK STATUS LINK ===
    // Jika link di-pause, tampilkan halaman error
    if ($linkData['status'] === 'paused') {
        showPausedLinkPage($kodePendek);
        exit;
    }

    // Simpan ID dan URL asli ke variabel
    $urlAsli = $linkData['original_url'];
    $linkId = $linkData['id'];

    // --- Menjalankan pencatatan statistik ---
    // $analytics = new Statistics();
    // $analytics->recordClick($linkId);
    // Catat statistik klik QR
    $stats = new Statistics();
    $stats->recordClick($linkId);

    
    // === PENGATURAN IKLAN ===
    $tampilkanIklan = true;
    $waktuTampilIklan = (int) Config::get('AD_DISPLAY_TIME', 3); // detik
    
    // Get AdSense configuration
    $adSenseConfig = Config::getAdSenseConfig();
    $adSenseEnabled = Config::isAdSenseEnabled();
    
    // Kondisi untuk skip iklan:
    // 1. Parameter skip_ads=1 di URL
    // 2. Iklan dinonaktifkan
    // 3. Waktu tampil iklan <= 0
    if (isset($_GET['skip_ads']) || !$tampilkanIklan || $waktuTampilIklan <= 0) {
        header('Location: ' . $urlAsli);
        exit;
    }
    
} catch (Exception $e) {
    // Log error dan redirect ke halaman utama
    error_log("Error redirect: " . $e->getMessage());
    header('Location: ../');
    exit;
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AAARO - Mengalihkan...</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <?php 
    // Get the base URL for CSS path
    $baseUrl = Config::getBaseUrl();
    $cssPath = rtrim($baseUrl, '/') . '/css/redirect.css';
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath); ?>">
    <!-- Auto refresh ke URL tujuan setelah waktu yang ditentukan -->
    <meta http-equiv="refresh" content="<?php echo $waktuTampilIklan; ?>;url=<?php echo htmlspecialchars($urlAsli); ?>">
    
    <!-- Meta tags SEO -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Anda sedang dialihkan ke tujuan Anda.">
    
    <?php if ($adSenseEnabled): ?>
    <!-- Google AdSense Script -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars($adSenseConfig['client_id']); ?>"
            crossorigin="anonymous"></script>
    
    <?php if ($adSenseConfig['auto_ads']): ?>
    <!-- AdSense Auto Ads -->
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({
              google_ad_client: "<?php echo htmlspecialchars($adSenseConfig['client_id']); ?>",
              enable_page_level_ads: true
         });
    </script>
    <?php endif; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <?php if (file_exists(__DIR__ . '/images/logo-aaaro.png')): ?>
        <div class="logo">
            <?php 
            $baseUrl = Config::getBaseUrl();
            $imagePath = rtrim($baseUrl, '/') . '/images/logo-aaaro.png';
            ?>
            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="AAARO Logo">
        </div>
        <?php endif; ?>
        
        <!-- Judul Halaman -->
        <h1>🚀 MENGALIHKAN ANDA...</h1>
        
        <!-- Informasi Redirect -->
        <div class="redirect-info">
            <p>Anda akan dialihkan otomatis ke tujuan dalam:</p>
        </div>
        
        <!-- Countdown Timer -->
        <div class="countdown" id="countdown"><?php echo $waktuTampilIklan; ?></div>
        
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-fill" id="progress"></div>
        </div>
        
        <!-- Container Iklan -->
        <div class="ad-container" id="ad-container">
            <?php if ($adSenseEnabled && !empty($adSenseConfig['rectangle_slot'])): ?>
                <!-- Google AdSense Rectangle Ad (300x250) -->
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-client="<?php echo htmlspecialchars($adSenseConfig['client_id']); ?>"
                     data-ad-slot="<?php echo htmlspecialchars($adSenseConfig['rectangle_slot']); ?>"
                     data-ad-format="rectangle"
                     data-full-width-responsive="true"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            <?php elseif ($adSenseEnabled && !empty($adSenseConfig['banner_slot'])): ?>
                <!-- Google AdSense Banner Ad (728x90 or responsive) -->
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-client="<?php echo htmlspecialchars($adSenseConfig['client_id']); ?>"
                     data-ad-slot="<?php echo htmlspecialchars($adSenseConfig['banner_slot']); ?>"
                     data-ad-format="auto"
                     data-full-width-responsive="true"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            <?php else: ?>
                <!-- Fallback placeholder -->
                <div class="ad-placeholder">
                    <h3>Ruang Iklan</h3>
                    <p>Konten Anda akan segera dimuat...</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Info URL Tujuan -->
        <div class="destination-url">
            <strong>Tujuan:</strong> <?php echo htmlspecialchars($urlAsli); ?>
        </div>
        
        <?php if ($adSenseEnabled && !empty($adSenseConfig['mobile_banner_slot'])): ?>
        <!-- Mobile AdSense Banner (shown only on mobile) -->
        <div class="mobile-ad-container">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="<?php echo htmlspecialchars($adSenseConfig['client_id']); ?>"
                 data-ad-slot="<?php echo htmlspecialchars($adSenseConfig['mobile_banner_slot']); ?>"
                 data-ad-format="banner"
                 data-full-width-responsive="true"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
        <?php endif; ?>
        
        <!-- Tombol Skip -->
        <a href="<?php echo htmlspecialchars($urlAsli); ?>" class="skip-button" id="skip-button">
            Lewati & Lanjutkan →
        </a>
    </div>

    <!-- JavaScript untuk Countdown dan Redirect -->
    <script>
        (function() {
            // === VARIABEL UTAMA ===
            const totalWaktu = <?php echo $waktuTampilIklan; ?>; // Total waktu dalam detik
            let waktuSaatIni = totalWaktu;                      // Waktu countdown saat ini
            
            // Ambil elemen DOM
            const elemenCountdown = document.getElementById('countdown');
            const elemenProgress = document.getElementById('progress');
            const tombolSkip = document.getElementById('skip-button');
            const urlTujuan = <?php echo json_encode($urlAsli); ?>;
            
            // === FUNGSI UPDATE PROGRESS BAR ===
            function updateProgress() {
                // Hitung persentase progress (0-100%)
                const persentase = ((totalWaktu - waktuSaatIni) / totalWaktu) * 100;
                elemenProgress.style.width = persentase + '%';
            }
            
            // === TIMER COUNTDOWN ===
            const timer = setInterval(function() {
                // Kurangi waktu
                waktuSaatIni--;
                
                // Update tampilan countdown
                if (elemenCountdown) {
                    elemenCountdown.textContent = waktuSaatIni;
                }
                
                // Update progress bar
                updateProgress();
                
                // Jika waktu habis, redirect ke tujuan
                if (waktuSaatIni <= 0) {
                    clearInterval(timer);
                    window.location.href = urlTujuan;
                }
            }, 1000); // Eksekusi setiap 1 detik
            
            // === EVENT HANDLER TOMBOL SKIP ===
            if (tombolSkip) {
                tombolSkip.addEventListener('click', function(e) {
                    e.preventDefault();           // Cegah default action
                    clearInterval(timer);         // Hentikan timer
                    window.location.href = urlTujuan; // Redirect langsung
                });
            }
            
            // === EVENT HANDLER KEYBOARD ===
            document.addEventListener('keydown', function(e) {
                // Tekan Spasi atau Enter untuk skip
                if (e.code === 'Space' || e.code === 'Enter') {
                    e.preventDefault();
                    clearInterval(timer);
                    window.location.href = urlTujuan;
                }
            });
            
            // === INISIALISASI ===
            updateProgress(); // Set progress bar awal
            
            // === OPTIMASI: PRELOAD HALAMAN TUJUAN ===
            // Muat halaman tujuan di background untuk loading lebih cepat
            const linkPrefetch = document.createElement('link');
            linkPrefetch.rel = 'prefetch';
            linkPrefetch.href = urlTujuan;
            document.head.appendChild(linkPrefetch);
        })();
    </script>
</body>
</html>