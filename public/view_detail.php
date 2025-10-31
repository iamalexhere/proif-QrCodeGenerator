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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="stylesheet" href="css/view_detail.css"> 
    <link rel="stylesheet" href="css/detail_responsive.css">
    <style>
        /* Download Buttons Styling */
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stats-header h2 {
            margin: 0;
        }
        
        .download-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #52b788 0%, #40916c 100%);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(82, 183, 136, 0.3);
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(82, 183, 136, 0.5);
            background: linear-gradient(135deg, #40916c 0%, #2d6a4f 100%);
        }
        
        .btn-download:active {
            transform: translateY(0);
        }
        
        .btn-download svg {
            width: 16px;
            height: 16px;
        }
        
        /* Responsive untuk Mobile */
        @media (max-width: 768px) {
            .stats-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .download-buttons {
                width: 100%;
            }
            
            .btn-download {
                flex: 1;
                justify-content: center;
                min-width: 0;
                padding: 10px 12px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .download-buttons {
                flex-direction: column;
            }
            
            .btn-download {
                width: 100%;
            }
        }
    </style> 
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <title>View QR Code Details</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jsPDF untuk export PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="js/view_detail.js"></script>
    <link rel="icon" href="images/logo-aaaro.png" type="image/x-icon">
</head>
<body>
    <header class="navbar">
        <div class="navbar-left">
            <img src='images/logo-aaaro.png' alt="AAARO Logo">
            <div class="brand-text">
                <div class="brand-title">AAARO</div>
                <div class="brand-subtitle">Complexity, simplified</div>
            </div>
        </div>
        <div class="navbar-right">
            <a href="<?php echo htmlspecialchars($returnPage); ?>" class="btn-back">&larr; Back to Dashboard</a>
            <a href="logout.php" style="color: #dc3545; text-decoration: none; font-size: 12px;" title="Logout">
              <img class="img_logout" src="images/logout_icon.png" alt="logout_icon">
            </a>
        </div>
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
            <div class="stats-header">
                <h2>Statistics</h2>
                <div class="download-buttons">
                    <button class="btn-download" onclick="downloadPDF()" title="Download as PDF">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        PDF
                    </button>
                    <button class="btn-download" onclick="downloadCSV()" title="Download as CSV">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        CSV
                    </button>
                    <button class="btn-download" onclick="downloadExcel()" title="Download as Excel">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Excel
                    </button>
                </div>
            </div>
            
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
        const linkId = <?php echo (int)$linkData['id']; ?>;
        const linkCreatedAt = "<?php echo $linkCreatedAt; ?>";
    </script>
</body>
</html>