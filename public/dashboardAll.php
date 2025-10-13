<?php 
/***********************************************************
 * DASHBOARD QR CODE - ALL
 * ---------------------------------------------------------
 * File ini menampilkan semua QR Code yang tersimpan di DB 
 * dengan fitur pencarian, pagination, dan statistik QR aktif.
 * 
 * Struktur utama:
 *  Authentication & User Check
 *  Koneksi Database
 *  Pagination + Pencarian (Search)
 *  Query Data + Perhitungan Total
 *  Perhitungan Statistik Sidebar
 *  Render Tampilan (Sidebar, Main, Pagination)
 ***********************************************************/

// AUTHENTICATION & USER CHECK
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Require authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// KONEKSI DATABASE
$db = Database::getInstance()->getConnection();

// PAGINATION 
// Jumlah item per halaman
$items_per_page = 3;

// Ambil halaman aktif dari parameter URL (default = 1)
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Pastikan minimal halaman = 1

// FITUR PENCARIAN
// Ambil kata kunci pencarian (jika ada)
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = " WHERE user_id = ?";  // Always filter by user
$search_param = '';  // Menyimpan parameter untuk prepared statement

// Jika user mengetikkan sesuatu di search bar
if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    // Mencari berdasarkan custom_url atau original_url dengan user filter
    $where_clause = " WHERE user_id = ? AND (custom_url LIKE ? OR original_url LIKE ?)";
}

// HITUNG TOTAL DATA
// Tujuan: untuk menentukan total halaman yang tersedia
if (!empty($search_query)) {
    // Jika ada pencarian, gunakan prepared statement agar aman dari SQL Injection
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links" . $where_clause);
    $stmt->bind_param('iss', $currentUser['id'], $search_param, $search_param);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    // Jika tidak ada pencarian, hitung total data user
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links" . $where_clause);
    $stmt->bind_param('i', $currentUser['id']);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// Hitung total halaman berdasarkan jumlah data
$total_pages = max(1, ceil($total_items / $items_per_page));

// Pastikan halaman saat ini tidak melebihi total halaman
$current_page = min($current_page, $total_pages);

// ==================== 5. HITUNG OFFSET QUERY ====================
// Digunakan untuk menentukan data mana yang akan diambil dari database
$offset = ($current_page - 1) * $items_per_page;

// ==================== 6. QUERY DATA LINKS DENGAN STATISTIK ====================
// Ambil data QR sesuai halaman dan kondisi pencarian dengan statistik
$base_query = "
    SELECT 
        l.*,
        COALESCE(stats.scan_count, 0) as scan_count,
        COALESCE(stats.top_device, 'N/A') as top_device,
        COALESCE(stats.top_city, 'N/A') as top_city
    FROM links l
    LEFT JOIN (
        SELECT 
            link_id,
            COUNT(*) as scan_count,
            (SELECT user_agent FROM clicks c2 WHERE c2.link_id = c.link_id 
             GROUP BY user_agent ORDER BY COUNT(*) DESC LIMIT 1) as top_device,
            (SELECT city FROM clicks c3 WHERE c3.link_id = c.link_id AND city IS NOT NULL
             GROUP BY city ORDER BY COUNT(*) DESC LIMIT 1) as top_city
        FROM clicks c
        GROUP BY link_id
    ) stats ON l.id = stats.link_id
";

if (!empty($search_query)) {
    $stmt = $db->prepare($base_query . $where_clause . " ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('issii', $currentUser['id'], $search_param, $search_param, $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $db->prepare($base_query . $where_clause . " ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('iii', $currentUser['id'], $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Simpan hasil query ke array $links
$links = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Extract device type from user agent for better display
        if ($row['top_device'] && $row['top_device'] !== 'N/A') {
            if (stripos($row['top_device'], 'iPhone') !== false || stripos($row['top_device'], 'iPad') !== false) {
                $row['top_device'] = 'iOS';
            } elseif (stripos($row['top_device'], 'Android') !== false) {
                $row['top_device'] = 'Android';
            } elseif (stripos($row['top_device'], 'Windows') !== false) {
                $row['top_device'] = 'Windows';
            } elseif (stripos($row['top_device'], 'Mac') !== false) {
                $row['top_device'] = 'Mac';
            } else {
                $row['top_device'] = 'Other';
            }
        }
        $links[] = $row;
    }
}

// HITUNG STATISTIK UNTUK SIDEBAR (USER-SPECIFIC)
// Mengambil semua status QR milik user yang login
$stmt = $db->prepare("SELECT status FROM links WHERE user_id = ?");
$stmt->bind_param('i', $currentUser['id']);
$stmt->execute();
$all_links_result = $stmt->get_result();

$total_qrs = 0;
$active_qrs = 0;
$paused_qrs = 0;

if ($all_links_result) {
    while ($row = $all_links_result->fetch_assoc()) {
        $total_qrs++;
        if ($row['status'] === 'active') {
            $active_qrs++;
        } else {
            $paused_qrs++;
        }
    }
}
$stmt->close();

// Get user quota information
$quotaInfo = Auth::canCreateQRCode($currentUser['id']);
$planLimits = Config::getPlanLimits();
$userPlan = $planLimits[$currentUser['plan']] ?? $planLimits['free'];

// Simpan nama file halaman aktif untuk menentukan item menu mana yang disorot
$current_page_name = basename($_SERVER['PHP_SELF']);
?>

<!--BAGIAN HTML-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title>Dashboard QR Code - All</title>
  <!-- Load file CSS dan JS -->
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/searchbar.css">
  <link rel="stylesheet" href="css/popUp.css">
  <link rel="stylesheet" href="css/pagination.css">
  <link rel="stylesheet" href="css/device-responsive.css">
  <link rel="icon" href="images/logo-aaaro.png" type="image/x-icon">
  <script src="js/script.js"></script>
</head>

<body>
  <div class="container">

    <!--SIDEBAR-->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>QR Dashboard</h2>
        <p>Manage your QR codes</p>
      </div>

      <!-- Form Pencarian -->
      <div class="search-container">
        <form action="" method="GET" id="searchForm">
          <input 
            type="text"
            id="searchInput"
            name="search"
            class="search-input"
            placeholder="Search QRCodes..."
            value="<?php echo htmlspecialchars($search_query); ?>"
          >
        </form>
      </div>

      <!-- Navigasi Menu
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="dashboardAll.php" class="nav-link <?php echo ($current_page_name == 'dashboardAll.php') ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-text">All QR Codes</span>
            <span class="nav-count"><?php echo $total_qrs; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardActive.php" class="nav-link <?php echo ($current_page_name == 'dashboardActive.php') ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-text">Active QR Codes</span>
            <span class="nav-count"><?php echo $active_qrs; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardPause.php" class="nav-link <?php echo ($current_page_name == 'dashboardPause.php') ? 'active' : ''; ?>">
            <span class="nav-icon">⏸️</span>
            <span class="nav-text">Paused QR Codes</span>
            <span class="nav-count"><?php echo $paused_qrs; ?></span>
          </a>
        </li>
      </ul> -->

      <!-- Quota Display -->
      <div class="quota-section" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 8px; width: 100%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span style="font-size: 14px; color: #666;">Monthly Quota:</span>
          <span style="font-size: 14px; font-weight: bold;"><?php echo $quotaInfo['used']; ?> / <?php echo $quotaInfo['limit']; ?></span>
        </div>
        <div style="background: #e9ecef; border-radius: 10px; height: 6px; overflow: hidden;">
          <div style="background: <?php echo $quotaInfo['used'] >= $quotaInfo['limit'] ? '#dc3545' : '#28a745'; ?>; height: 100%; width: <?php echo ($quotaInfo['used'] / $quotaInfo['limit']) * 100; ?>%;"></div>
        </div>
        <div style="margin-top: 8px; font-size: 12px; color: #666;">
          <?php echo ucfirst($currentUser['plan']); ?> Plan
        </div>
      </div>

      <!-- Tombol Buat QR Baru & Upgrade -->
      <div class="sidebar-footer">
        <?php if ($quotaInfo['canCreate']): ?>
          <a href="index.php" class="create-btn">
            <span class="create-btn-icon">+</span>
            Create New QR Code
          </a>
        <?php else: ?>
          <div class="create-btn" style="opacity: 0.6; cursor: not-allowed; background: #ccc;">
            <span class="create-btn-icon">⚠️</span>
            Quota Exceeded
          </div>
        <?php endif; ?>

        <?php if ($currentUser['plan'] === 'free'): ?>
          <?php 
          $trialActive = Auth::hasAnalyticsAccess($currentUser);
          $trialDaysLeft = 0;
          if ($currentUser['trial_ends_at']) {
              $trialEnd = new DateTime($currentUser['trial_ends_at']);
              $now = new DateTime();
              $diff = $now->diff($trialEnd);
              $trialDaysLeft = $trialActive ? $diff->days : 0;
          }
          ?>
          <div class="trial-section">
            <?php if ($trialActive): ?>
              <div class="trial-text">30-Day Analytics Trial: <?php echo $trialDaysLeft; ?> days left</div>
            <?php else: ?>
              <div class="trial-text">30-day analytics trial expired</div>
            <?php endif; ?>
            <a href="payment.php" class="upgrade-btn">Upgrade Plan</a>
          </div>
        <?php else: ?>
          <div class="trial-section">
            <div class="trial-text"><?php echo ucfirst($currentUser['plan']); ?> Plan Active</div>
            <a href="payment.php" class="upgrade-btn">Manage Plan</a>
          </div>
        <?php endif; ?>

        <!-- User Profile Section -->
        <div class="user-profile" style="margin-top: 20px; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e0e0e0;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <img src="<?php echo htmlspecialchars($currentUser['picture'] ?? 'images/default-avatar.png'); ?>" 
                 alt="Profile" 
                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            <div style="flex: 1; min-width: 0;">
              <div style="font-weight: 600; font-size: 14px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars($currentUser['name']); ?>
              </div>
              <div style="font-size: 12px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars($currentUser['email']); ?>
              </div>
            </div>
            <a href="logout.php" style="color: #dc3545; text-decoration: none; font-size: 12px;" title="Logout">
              <img class="img_logout" src="images/logout_icon.png" alt="logout_icon">
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT-->
    <div class="main-content">

    <!-- nav -->
      <div class="navigator">
        <div>
          <a href="dashboardAll.php" class="nav-link <?php echo ($current_page_name == 'dashboardAll.php') ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-text">All QR Codes</span>
            <span class="nav-count"><?php echo $total_qrs; ?></span>
          </a>
        </div>
        
        <div>
          <a href="dashboardActive.php" class="nav-link <?php echo ($current_page_name == 'dashboardActive.php') ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-text">Active QR Codes</span>
            <span class="nav-count"><?php echo $active_qrs; ?></span>
          </a>
        </div>

        <div>
          <a href="dashboardPause.php" class="nav-link <?php echo ($current_page_name == 'dashboardPause.php') ? 'active' : ''; ?>">
            <span class="nav-icon">⏸️</span>
            <span class="nav-text">Paused QR Codes</span>
            <span class="nav-count"><?php echo $paused_qrs; ?></span>
          </a>
        </div>
      </div>

      <div class="header">
        <h1 id="page-title">All QR Codes</h1>
        <p id="page-subtitle">
          <?php if (!empty($search_query)): ?>
            <!-- Jika user sedang mencari sesuatu -->
            Search results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>" - <?php echo $total_items; ?> found
          <?php else: ?>
            Manage and track your QR codes with advanced analytics
          <?php endif; ?>
        </p>
      </div>

      <!--DAFTAR QR CODE-->
      <main class="dashboard">

        <!-- Kartu Buat Baru (disembunyikan saat pencarian) -->
        <?php if (empty($search_query)): ?>
          <div class="qr-card create-card" onclick="location.href='createQR.php'">
            <div class="create-icon">+</div>
            <h3>Create New QR Code</h3>
            <p>Generate a new QR code with custom design</p>
          </div>
        <?php endif; ?>

        <!-- Tampilkan pesan jika data kosong -->
        <?php if (empty($links)): ?>
          <div class="empty-state">
            <h3>No QR Codes Found</h3>
            <?php if (!empty($search_query)): ?>
              <p>No results match your search "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
            <?php else: ?>
              <p>You haven't created any QR codes yet. Let's create one!</p>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <!-- Loop untuk setiap QR code -->
          <?php foreach ($links as $link): ?>

            <?php
            // Menentukan nama tampilan QR
            // Berdasarkan custom URL, jika kosong maka ambil host dari original_url
            // Jika domain cocok dengan platform populer → tampilkan nama platform
             
            $display_name = $link['custom_url'];
            if (empty($display_name) && !empty($link['original_url'])) {
                $host = parse_url($link['original_url'], PHP_URL_HOST);
                if ($host) {
                    $host = preg_replace('/^www\./', '', strtolower($host));

                    // Deteksi platform populer
                    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
                        $display_name = 'YOUTUBE';
                    } elseif (strpos($host, 'facebook.com') !== false) {
                        $display_name = 'FACEBOOK';
                    } elseif (strpos($host, 'instagram.com') !== false) {
                        $display_name = 'INSTAGRAM';
                    } elseif (strpos($host, 'docs.google.com') !== false) {
                        $display_name = 'DOCS';
                    } elseif (strpos($host, 'drive.google.com') !== false) {
                        $display_name = 'DRIVE';
                    } elseif (strpos($host, 'linkedin.com') !== false) {
                        $display_name = 'LINKEDIN';
                    } else {
                        // Default ambil domain pertama
                        $parts = explode('.', $host);
                        $display_name = strtoupper($parts[0]);
                    }
                } else {
                    $display_name = 'UNTITLED';
                }
            }
            ?>

            <!-- Card individual QR -->
            <div class="qr-card" data-status="<?php echo htmlspecialchars($link['status']); ?>" data-qr-title="<?php echo htmlspecialchars(strtolower($display_name)); ?>">
              <div class="performance-indicator <?php echo ($link['status'] !== 'active') ? 'paused' : ''; ?>"></div>

              <div class="card-header">
                <div class="qr-icon">QR</div>
                <div class="card-title">
                  <h3><?php echo htmlspecialchars($display_name); ?></h3>
                  <div class="created-date">Created: <?php echo date('F d, Y', strtotime($link['created_at'])); ?></div>
                </div>

                <span class="status-badge status-<?php echo htmlspecialchars($link['status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($link['status'])); ?>
                </span>
              </div>

              <!-- Konten QR -->
              <div class="qr-content">
                <div class="qr-info">
                  <div class="info-item">
                    <span class="info-label">Original URL</span>
                    <div class="url-display"><?php echo htmlspecialchars($link['original_url']); ?></div>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Short Link</span>
                                        <div class="url-display">
                      <?php $fullShortUrl = Config::getShortUrlBase() . '/' . $link['short_url'];?>
                      <a href="<?php echo htmlspecialchars($fullShortUrl); ?>" 
                        class="short-link" 
                        target="_blank"
                        onclick="event.preventDefault(); window.open('<?php echo htmlspecialchars($fullShortUrl); ?>', '_blank');">
                        <?php echo htmlspecialchars($fullShortUrl); ?> 
                      </a>
                      <span
                        title="Copy short URL" 
                        onclick="copyToClipboard('<?php echo htmlspecialchars($fullShortUrl); ?>', event)">
                        <img class="copy-icon" src="images/copy_icon.png" alt="copy_icon">
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Gambar QR-->
                <div class="qr-visual">
                  <?php if (!empty($link['qr_image'])): ?>
                    <img src="data:image/png;base64,<?php echo base64_encode($link['qr_image']); ?>" alt="QR Code" class="qr-image">
                  <?php else: ?>
                    <!-- QR tidak tersimpan di database, tampilkan placeholder -->
                    <div class="qr-placeholder" style="width: 140px; height: 140px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc; border-radius: 8px;">
                      <span style="color: #666; font-size: 12px; text-align: center;">QR Code<br>Not Available</span>
                    </div>
                  <?php endif; ?>

                  <div class="actions">
                    <!-- Statistik, diambil dari view_details-->
                    <?php $hasAnalyticsAccess = Auth::hasAnalyticsAccess($currentUser); ?>
                    <div class="qr-stats <?php echo !$hasAnalyticsAccess ? 'analytics-locked' : ''; ?>" style="position: relative;">
                      <?php if (!$hasAnalyticsAccess): ?>
                        <div class="analytics-overlay" style="
                          position: absolute;
                          top: 0;
                          left: 0;
                          right: 0;
                          bottom: 0;
                          background: rgba(255, 255, 255, 0.9);
                          backdrop-filter: blur(4px);
                          -webkit-backdrop-filter: blur(4px);
                          display: flex;
                          flex-direction: column;
                          align-items: center;
                          justify-content: center;
                          border-radius: 8px;
                          z-index: 10;
                        ">
                          <div style="text-align: center; color: #666;">
                            <div style="font-size: 24px; margin-bottom: 8px;">🔒</div>
                            <div style="font-weight: bold; margin-bottom: 4px;">Analytics Locked</div>
                            <div style="font-size: 12px; margin-bottom: 12px;">30-day trial expired</div>
                            <a href="payment.php" style="
                              background: #007bff;
                              color: white;
                              padding: 6px 12px;
                              border-radius: 4px;
                              text-decoration: none;
                              font-size: 12px;
                              font-weight: bold;
                            ">Upgrade Plan</a>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <div class="stat-box" style="<?php echo !$hasAnalyticsAccess ? 'filter: blur(2px);' : ''; ?>">
                        <div class="stat-icon">📊</div>
                        <span class="stat-value"><?php echo $hasAnalyticsAccess ? number_format($link['scan_count'] ?? 0) : '•••'; ?></span>
                        <div class="stat-label">Total Scans</div>
                      </div>
                      <div class="stat-box" style="<?php echo !$hasAnalyticsAccess ? 'filter: blur(2px);' : ''; ?>">
                        <div class="stat-icon">📱</div>
                        <span class="stat-value"><?php echo $hasAnalyticsAccess ? ($link['top_device'] ?? 'N/A') : '•••'; ?></span>
                        <div class="stat-label">Top Device</div>
                      </div>
                      <div class="stat-box" style="<?php echo !$hasAnalyticsAccess ? 'filter: blur(2px);' : ''; ?>">
                        <div class="stat-icon">🌍</div>
                        <span class="stat-value"><?php echo $hasAnalyticsAccess ? ($link['top_city'] ?? 'N/A') : '•••'; ?></span>
                        <div class="stat-label">Top City</div>
                      </div>
                    </div>

                    <!-- tombol view details, donwload, dan resume /pause -->
                    <?php if ($hasAnalyticsAccess): ?>
                      <button class="btn btn-edit" onclick="window.location.href='view_detail.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardAll.php'">✏️ View Details</button>
                    <?php else: ?>
                      <button class="btn btn-edit" style="opacity: 0.6; cursor: not-allowed;" onclick="alert('Analytics features require an active plan. Please upgrade to view detailed analytics.'); event.preventDefault();" title="Upgrade required">🔒 View Details</button>
                    <?php endif; ?>
                    <button class="btn btn-download" onclick="showDownloadOptions('<?php echo htmlspecialchars($link['short_url']); ?>', '<?php echo htmlspecialchars($link['short_url']); ?>')">⬇️ Download</button>
                    <button class="btn btn-pause" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', '<?php echo htmlspecialchars($link['status']); ?>')">
                      <?php echo ($link['status'] === 'active') ? '⏸️ Pause' : '▶️ Resume'; ?>
                    </button>
                    <button class="btn btn-delete" onclick="deleteQRCode('<?php echo htmlspecialchars($link['short_url']); ?>')" style="background: #dc3545;">🗑️ Delete</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </main>

      <!-- PAGINATION-->
      <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
          <div class="pagination">
            <!-- Tombol Prev -->
            <?php 
            $prev_link = "?page=" . ($current_page - 1);
            if (!empty($search_query)) {
                $prev_link .= "&search=" . urlencode($search_query);
            }
            ?>
            <?php if ($current_page > 1): ?>
              <a href="<?php echo $prev_link; ?>" class="pagination-btn pagination-prev">Prev</a>
            <?php else: ?>
              <span class="pagination-btn pagination-prev disabled">Prev</span>
            <?php endif; ?>

            <?php
            // Hitung range halaman
            $max_pages_shown = 5;
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $start_page + $max_pages_shown - 1);

            if ($end_page - $start_page < $max_pages_shown - 1) {
                $start_page = max(1, $end_page - $max_pages_shown + 1);
            }

            function buildPageUrl($page, $search) {
                $url = "?page=" . $page;
                if (!empty($search)) {
                    $url .= "&search=" . urlencode($search);
                }
                return $url;
            }

            // Tampilkan halaman pertama jika terlewati
            if ($start_page > 1): ?>
              <a href="<?php echo buildPageUrl(1, $search_query); ?>" class="pagination-btn">1</a>
              <?php if ($start_page > 2): ?>
                <span class="pagination-ellipsis">...</span>
              <?php endif; ?>
            <?php endif; ?>

            <!-- Looping halaman -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
              <?php if ($i == $current_page): ?>
                <span class="pagination-btn active"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="<?php echo buildPageUrl($i, $search_query); ?>" class="pagination-btn"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>

            <!-- Halaman terakhir -->
            <?php if ($end_page < $total_pages): ?>
              <?php if ($end_page < $total_pages - 1): ?>
                <span class="pagination-ellipsis">...</span>
              <?php endif; ?>
              <a href="<?php echo buildPageUrl($total_pages, $search_query); ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <!-- Tombol Next -->
            <?php 
            $next_link = "?page=" . ($current_page + 1);
            if (!empty($search_query)) {
                $next_link .= "&search=" . urlencode($search_query);
            }
            ?>
            <?php if ($current_page < $total_pages): ?>
              <a href="<?php echo $next_link; ?>" class="pagination-btn pagination-next">Next</a>
            <?php else: ?>
              <span class="pagination-btn pagination-next disabled">Next</span>
            <?php endif; ?>
          </div>

          <!-- Info tambahan -->
          <div class="pagination-info">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> entries
            <?php if (!empty($search_query)): ?>
              <span class="search-active-badge">🔍 Search Active</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>