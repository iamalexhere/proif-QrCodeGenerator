<?php
/**
 * Halaman Redirect URL dengan Iklan dan Pencatatan Statistik
 */

// Memuat class dan konfigurasi yang diperlukan
require_once __DIR__ . '/../classes/UrlShortener.php';
require_once __DIR__ . '/../config/Config.php';

// === MENGAMBIL KODE PENDEK DARI URL ===
$kodePendek = '';
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $kodePendek = trim($_GET['code']);
} else {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $kodePendek = end($pathParts);
}

if (empty($kodePendek)) {
    header('Location: /');
    exit;
}

// === PROSES REDIRECT DAN PENCATATAN STATISTIK ===
try {
    $urlShortener = new UrlShortener();
    
    // Cari URL asli dan ID-nya berdasarkan kode pendek
    $linkData = $urlShortener->getLinkDataByShortCode($kodePendek);
    
    // Jika kode pendek tidak ditemukan
    if (!$linkData) {
        header('Location: ../');
        exit;
    }
    
    $urlAsli = $linkData['original_url'];
    $linkId = $linkData['id'];

    // --- PENCATATAN STATISTIK DIMULAI DI SINI ---
    
    // 1. Ambil Informasi Pengunjung
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // 2. Dapatkan Lokasi dari IP Address menggunakan API (ip-api.com)
    $geoData = @json_decode(@file_get_contents("http://ip-api.com/json/{$ipAddress}"), true);
    $country = $geoData['country'] ?? 'Unknown';
    $city = $geoData['city'] ?? 'Unknown';

    // 3. Tebak Jenis Perangkat dari User Agent
    $deviceType = 'Desktop';
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($userAgent))) {
        $deviceType = 'Tablet';
    } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($userAgent))) {
        $deviceType = 'Mobile';
    }

    // 4. Simpan ke Tabel `clicks`
    $db = Database::getInstance()->getConnection();
    $sql = "INSERT INTO clicks (link_id, ip_address, user_agent, country, city, device_type) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("isssss", $linkId, $ipAddress, $userAgent, $country, $city, $deviceType);
    $stmt->execute();
    $stmt->close();
    
    // --- AKHIR DARI PENCATATAN STATISTIK ---
    
    // === PENGATURAN IKLAN (Tetap sama seperti sebelumnya) ===
    $waktuTampilIklan = (int) Config::get('AD_DISPLAY_TIME', 3);
    if (isset($_GET['skip_ads']) || $waktuTampilIklan <= 0) {
        header('Location: ' . $urlAsli);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error redirect: " . $e->getMessage());
    header('Location: ../');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mengalihkan...</title>
    <meta http-equiv="refresh" content="<?php echo $waktuTampilIklan; ?>;url=<?php echo htmlspecialchars($urlAsli); ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../css/redirect.css"> </head>
<body>
    <div class="container">
        <h1>🚀 Mengalihkan Anda...</h1>
        <div class="redirect-info">
            <p>Anda akan dialihkan otomatis ke tujuan dalam:</p>
        </div>
        <div class="countdown" id="countdown"><?php echo $waktuTampilIklan; ?></div>
        </div>
    <script>
        // ... (kode JavaScript tidak perlu diubah) ...
    </script>
</body>
</html>