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

// === PROSES REDIRECT ===
try {
    // Inisialisasi URL Shortener
    $urlShortener = new UrlShortener();
    
    // Cari URL asli berdasarkan kode pendek dan catat klik
    $urlAsli = $urlShortener->recordClick($kodePendek);
    
    // Jika kode pendek tidak ditemukan
    if (!$urlAsli) {
        header('Location: ../');
        exit;
    }
    
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
    <title>Mengalihkan...</title>
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
    
    <style>
        /* === RESET DASAR === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* === STYLING BODY === */
        body {
            font-family: 'Courier New', monospace;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000000;
            padding: 2rem;
        }
        
        /* === CONTAINER UTAMA === */
        .container {
            background: #ffffff;
            border: 4px solid #000000;
            padding: 3rem;
            text-align: left;
            max-width: 700px;
            width: 100%;
            box-shadow: 8px 8px 0px #000000;
        }
        
        /* === AREA LOGO === */
        .logo {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .logo img {
            max-height: 80px;
            width: auto;
            border: 2px solid #000000;
        }
        
        /* === JUDUL HALAMAN === */
        h1 {
            color: #000000;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 4px solid #000000;
            padding-bottom: 1rem;
        }
        
        /* === INFO REDIRECT === */
        .redirect-info {
            margin-bottom: 2rem;
            color: #000000;
            line-height: 1.4;
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        /* === COUNTDOWN TIMER === */
        .countdown {
            font-size: 4rem;
            font-weight: 900;
            color: #000000;
            margin: 2rem 0;
            text-align: center;
            border: 4px solid #000000;
            padding: 1rem;
            background: #ffffff;
            letter-spacing: 4px;
        }
        
        /* === CONTAINER IKLAN === */
        .ad-container {
            margin: 2rem 0;
            min-height: 280px;
            background: #ffffff;
            border: 4px solid #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            position: relative;
        }
        
        /* === PLACEHOLDER IKLAN === */
        .ad-placeholder {
            color: #000000;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        /* === MOBILE AD CONTAINER === */
        .mobile-ad-container {
            margin: 2rem 0;
            text-align: center;
            display: none; /* Hidden by default, shown on mobile */
        }
        
        /* === ADSENSE STYLING === */
        .adsbygoogle {
            display: block;
            margin: 0 auto;
        }
        
        /* === PROGRESS BAR === */
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #ffffff;
            border: 3px solid #000000;
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #000000;
            transition: width 0.1s ease;
        }
        
        /* === TOMBOL SKIP === */
        .skip-button {
            background: #000000;
            color: #ffffff;
            border: 3px solid #000000;
            padding: 1rem 2rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            transition: all 0.1s ease;
        }
        
        .skip-button:hover {
            background: #ffffff;
            color: #000000;
            box-shadow: 4px 4px 0px #000000;
            transform: translate(-2px, -2px);
        }
        
        /* === INFO URL TUJUAN === */
        .destination-url {
            background: #ffffff;
            padding: 1.5rem;
            border: 3px solid #000000;
            border-left: 8px solid #000000;
            margin: 2rem 0;
            word-break: break-all;
            font-weight: 700;
        }
        
        .destination-url strong {
            color: #000000;
            text-transform: uppercase;
        }
        
        /* === RESPONSIVE MOBILE === */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 2rem;
                box-shadow: 4px 4px 0px #000000;
            }
            
            h1 {
                font-size: 2rem;
                letter-spacing: 1px;
            }
            
            .countdown {
                font-size: 2.5rem;
                letter-spacing: 2px;
            }
            
            .skip-button {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
            
            /* Show mobile ad on mobile devices */
            .mobile-ad-container {
                display: block;
            }
            
            /* Hide main ad container on very small screens if mobile ad is present */
            .ad-container {
                min-height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo IF UNPAR jika tersedia -->
        <?php if (file_exists(__DIR__ . '/images/logoif.png')): ?>
        <div class="logo">
            <img src="images/logoif.png" alt="Logo IF UNPAR">
        </div>
        <?php endif; ?>
        
        <!-- Judul Halaman -->
        <h1>🚀 Mengalihkan Anda...</h1>
        
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