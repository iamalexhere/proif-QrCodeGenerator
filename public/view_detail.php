<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Tangkap parameter kode QR dan halaman asal
$code = $_GET['code'] ?? '';
$returnPage = $_GET['return'] ?? 'dashboardAll.php';
$linkData = null;

// Mengambil data detail untuk link ini dari tabel 'links' berdasarkan short_url
if (!empty($code)) {
    try {
        $db = Database::getInstance()->getConnection();
        // mengambil kolom created_at
        $stmt = $db->prepare("SELECT id, original_url, short_url, custom_url, logo_path, qr_color, qr_image, status, created_at FROM links WHERE short_url = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $linkData = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        // Jika terjadi error, isi $linkData null
    }
}

// Jika data link tidak ditemukan di database, redirect kembali ke dashboard
if (!$linkData) {
    header('Location: dashboardAll.php');
    exit;
}

// Format created_at untuk digunakan di JavaScript (YYYY-MM-DD)
$linkCreatedAt = $linkData['created_at'] ? date('Y-m-d', strtotime($linkData['created_at'])) : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View QR Code Details</title>
    <link rel="stylesheet" href="css/view_detail.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/view_detail.js"></script>
</head>
<body>
    <header class="navbar">
        <div class="logo">QR Code Generator</div>
        <a href="<?php echo htmlspecialchars($returnPage); ?>" class="btn-back">&larr; Back to Dashboard</a>
    </header>

    <main class="edit-container">
        <section class="edit-left">
            <?php if (!empty($linkData['qr_image'])): ?>
                <img src="data:image/png;base64,<?php echo base64_encode($linkData['qr_image']); ?>" alt="QR Code" class="qr-image">
            <?php else: ?>
                <img src="images/base.png" alt="QR Code" class="qr-image">
            <?php endif; ?>
            <div class="qr-details">
                <?php
                    $fullShortUrl = Config::getShortUrlBase() . '/' . $linkData['short_url'];
                ?>
                <p>
                    <strong>Short Link:</strong>
                    <a href="<?php echo htmlspecialchars($fullShortUrl); ?>" target="_blank">
                        <?php echo htmlspecialchars($fullShortUrl); ?>
                    </a>
                </p>
                <div class="destination-url">
                    <strong>Destination URL:</strong>
                    <p style="word-break: break-all; margin-top: 5px;"><?php echo htmlspecialchars($linkData['original_url']); ?></p>
                </div>
            </div>
        </section>

        <section class="edit-right">
            <h2>Statistics</h2>
            
            <div id="loading-stats" style="text-align: center; padding: 2rem;">
                <p>Loading statistics...</p>
            </div>

            <div class="stats-grid" id="stats-grid" style="display: none;">
                <!-- Total Scans -->
                <div class="stat-card">
                    <h3>Total Scans</h3>
                    <p class="stat-number" id="total-scans">0</p>
                </div>

                <!-- Devices -->
                <div class="stat-card">
                    <h3>Devices</h3>
                    <canvas id="deviceChart"></canvas>
                </div>

                <!-- By City -->
                <div class="stat-card">
                    <h3>By City</h3>
                    <canvas id="cityChart"></canvas>
                </div>

                <!-- By Country -->
                <div class="stat-card">
                    <h3>By Country</h3>
                    <canvas id="countryChart"></canvas>
                </div>

                <!-- View Toggle - Full Width -->
                <div class="stat-card view-card">
                    <h3>Time Range View</h3>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="day" onclick="changeView('day')">Daily</button>
                        <button class="view-btn" data-view="week" onclick="changeView('week')">Weekly</button>
                        <button class="view-btn" data-view="month" onclick="changeView('month')">Monthly</button>
                    </div>
                </div>

                <!-- Scans Over Time -->
                <div class="stat-card wide">
                    <h3>Scans Over Time</h3>
                    <canvas id="scansChart"></canvas>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Pass PHP variables to JavaScript
        const linkId = <?php echo (int)$linkData['id']; ?>;
        const linkCreatedAt = "<?php echo $linkCreatedAt; ?>";
    </script>
</body>
</html>