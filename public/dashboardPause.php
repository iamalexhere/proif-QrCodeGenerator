<?php
/**
 * ============================================================
 * DASHBOARD - PAUSED QR CODES
 * ------------------------------------------------------------
 * File ini menampilkan semua QR Code yang memiliki status "paused".
 * Termasuk fitur:
 *  - Authentication & User Check
 *  - Pagination
 *  - Search filter
 *  - Statistik QR aktif & paused
 *  - Tombol resume, view detail, dan download QR
 * ============================================================
 */

// Menampilkan error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Mengambil koneksi
$db = Database::getInstance()->getConnection();

// Pagination settings
$items_per_page = 3;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Minimal halaman 1

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "WHERE status != 'active' AND user_id = ?";
$search_param = '';

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $where_clause .= " AND (custom_url LIKE ? OR original_url LIKE ?)";
}

// Hitung total records dengan search (hanya paused milik user)
if (!empty($search_query)) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links " . $where_clause);
    $stmt->bind_param('iss', $currentUser['id'], $search_param, $search_param);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links " . $where_clause);
    $stmt->bind_param('i', $currentUser['id']);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

$total_pages = max(1, ceil($total_items / $items_per_page));

// Batasi current_page tidak melebihi total_pages
$current_page = min($current_page, $total_pages);

// Hitung offset
$offset = ($current_page - 1) * $items_per_page;

// Menyiapkan dan menjalankan query dengan LIMIT dan OFFSET serta statistik
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

// Menyimpan hasil query
$paused_links = [];
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
        $paused_links[] = $row;
    }
}

// Hitung total QR untuk sidebar (USER-SPECIFIC)
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

// Nama halaman aktif
$current_page_name = basename($_SERVER['PHP_SELF']);

/**
 * Fungsi untuk menentukan nama tampilan otomatis dari URL
 */
function getDisplayName($link) {
    if (!empty($link['custom_url'])) {
        return $link['custom_url'];
    }
    if (empty($link['original_url'])) {
        return 'UNTITLED';
    }

    $host = parse_url($link['original_url'], PHP_URL_HOST);
    if (!$host) return 'UNTITLED';

    // Hilangkan www.
    $host = preg_replace('/^www\./', '', strtolower($host));
    
    // Deteksi host dan ubah sesuai nama platform populer
    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
        return 'YOUTUBE';
    } elseif (strpos($host, 'facebook.com') !== false || strpos($host, 'fb.com') !== false) {
        return 'FACEBOOK';
    } elseif (strpos($host, 'instagram.com') !== false || strpos($host, 'ig.com') !== false) {
        return 'INSTAGRAM';
    } elseif (strpos($host, 'docs.google.com') !== false) {
        return 'DOCS';
    } elseif (strpos($host, 'drive.google.com') !== false) {
        return 'DRIVE';
    } elseif (strpos($host, 'linkedin.com') !== false) {
        return 'LINKEDIN';
    } else {
        // Default ambil nama domain pertama
        $parts = explode('.', $host);
        return strtoupper($parts[0]);
    }
}
?>

<!-- Halaman HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title>Dashboard QR Code - Paused</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/searchbar.css">
  <link rel="stylesheet" href="css/popUp.css">
  <link rel="stylesheet" href="css/pagination.css">
  <script src="js/script.js"></script>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>QR Dashboard</h2>
        <p>Manage your QR codes</p>
      </div>

      <!-- Search Bar -->
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

      <!-- <ul class="nav-menu">
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
      <div class="quota-section" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 8px;">
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
              🚪
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
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
        <h1 id="page-title">Paused QR Codes</h1>
        <p id="page-subtitle">
          <?php if (!empty($search_query)): ?>
            Search results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>" in paused QR codes - <?php echo $total_items; ?> found
          <?php else: ?>
            QR codes that are temporarily disabled
          <?php endif; ?>
        </p>
      </div>

      <main class="dashboard">
        <?php if (empty($paused_links)): ?>
            <div class="empty-state">
                <?php if (!empty($search_query)): ?>
                  <h3>No Paused QR Codes Found</h3>
                  <p>No paused QR codes match your search "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                <?php else: ?>
                  <div class="empty-icon">⏸️</div>
                  <h3>No Paused QR Codes</h3>
                  <p>You don't have any paused QR codes at the moment.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
          <?php foreach ($paused_links as $link): ?>
            <div class="qr-card" data-status="paused" data-qr-title="<?php echo htmlspecialchars(strtolower(getDisplayName($link))); ?>">
              <div class="performance-indicator paused"></div>
              <div class="card-header">
                <div class="qr-icon">QR</div>
                <div class="card-title">
                  <h3><?php echo htmlspecialchars(getDisplayName($link)); ?></h3>
                  <div class="created-date">Created: <?php echo date('F d, Y', strtotime($link['created_at'])); ?></div>
                </div>
                <span class="status-badge status-paused">Paused</span>
              </div>

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

                <div class="qr-visual">
                  <?php if (!empty($link['qr_image'])): ?>
                    <img src="data:image/png;base64,<?php echo base64_encode($link['qr_image']); ?>" alt="QR Code" class="qr-image paused-image">
                  <?php else: ?>
                    <!-- QR tidak tersimpan di database, tampilkan placeholder -->
                    <div class="qr-placeholder paused-image" style="width: 140px; height: 140px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc; border-radius: 8px; opacity: 0.6;">
                      <span style="color: #666; font-size: 12px; text-align: center;">QR Code<br>Not Available</span>
                    </div>
                  <?php endif; ?>

                  <div class="actions">
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

                    <!-- Tombol view_details, donwload, pause/resume -->
                    <?php if ($hasAnalyticsAccess): ?>
                      <button class="btn btn-edit" onclick="window.location.href='view_detail.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardPause.php'">✏️ View Details</button>
                    <?php else: ?>
                      <button class="btn btn-edit" style="opacity: 0.6; cursor: not-allowed;" onclick="alert('Analytics features require an active plan. Please upgrade to view detailed analytics.'); event.preventDefault();" title="Upgrade required">🔒 View Details</button>
                    <?php endif; ?>
                    <button class="btn btn-download" onclick="showDownloadOptions('<?php echo htmlspecialchars($link['short_url']); ?>', '<?php echo htmlspecialchars($link['short_url']); ?>')">⬇️ Download</button>
                    <button class="btn btn-resume" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', 'paused')">▶️ Resume</button>
                    <button class="btn btn-delete" onclick="deleteQRCode('<?php echo htmlspecialchars($link['short_url']); ?>')" style="background: #dc3545;">🗑️ Delete</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </main>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="pagination-container">
        <div class="pagination">
          <!-- Previous Button -->
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

          <!-- Page Numbers -->
          <?php
          // Hitung range halaman yang akan ditampilkan
          $max_pages_shown = 5;
          $start_page = max(1, $current_page - 2);
          $end_page = min($total_pages, $start_page + $max_pages_shown - 1);
          
          // Adjust start_page jika end_page sudah mentok
          if ($end_page - $start_page < $max_pages_shown - 1) {
              $start_page = max(1, $end_page - $max_pages_shown + 1);
          }

          // Helper function untuk build URL dengan search
          function buildPageUrl($page, $search) {
              $url = "?page=" . $page;
              if (!empty($search)) {
                  $url .= "&search=" . urlencode($search);
              }
              return $url;
          }

          // Tampilkan halaman pertama jika tidak termasuk dalam range
          if ($start_page > 1): ?>
            <a href="<?php echo buildPageUrl(1, $search_query); ?>" class="pagination-btn">1</a>
            <?php if ($start_page > 2): ?>
              <span class="pagination-ellipsis">...</span>
            <?php endif; ?>
          <?php endif; ?>

          <!-- Tampilkan range halaman -->
          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
              <span class="pagination-btn active"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="<?php echo buildPageUrl($i, $search_query); ?>" class="pagination-btn"><?php echo $i; ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <!-- Tampilkan halaman terakhir jika tidak termasuk dalam range -->
          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
              <span class="pagination-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?php echo buildPageUrl($total_pages, $search_query); ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
          <?php endif; ?>

          <!-- Next Button -->
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
        
        <div class="pagination-info">
          Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> paused entries
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