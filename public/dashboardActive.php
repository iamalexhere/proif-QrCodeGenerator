<?php
// Koneksi db
require_once __DIR__ . '/../classes/Database.php';

// Mengambil instance koneksi database
$db = Database::getInstance()->getConnection();

// Pagination settings
$items_per_page = 3;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Minimal halaman 1

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "WHERE status = 'active'";
$search_param = '';

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $where_clause .= " AND (custom_url LIKE ? OR original_url LIKE ?)";
}

// Hitung total records dengan search (hanya active)
if (!empty($search_query)) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links " . $where_clause);
    $stmt->bind_param('ss', $search_param, $search_param);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $count_query = $db->query("SELECT COUNT(*) as total FROM links WHERE status = 'active'");
    $total_items = $count_query->fetch_assoc()['total'];
}

$total_pages = max(1, ceil($total_items / $items_per_page));

// Batasi current_page tidak melebihi total_pages
$current_page = min($current_page, $total_pages);

// Hitung offset
$offset = ($current_page - 1) * $items_per_page;

// Menyiapkan dan menjalankan query dengan LIMIT dan OFFSET
if (!empty($search_query)) {
    $stmt = $db->prepare("SELECT * FROM links " . $where_clause . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ssii', $search_param, $search_param, $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query("SELECT * FROM links WHERE status = 'active' ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset");
}

// Menyimpan hasil query
$active_links = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $active_links[] = $row;
    }
}

// Hitung total QR untuk sidebar (tanpa pagination dan search)
$all_links_result = $db->query("SELECT status FROM links");
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

// Nama halaman aktif
$current_page_name = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard QR Code - Active</title>
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
            placeholder="Search active QR codes..."
            value="<?php echo htmlspecialchars($search_query); ?>"
          >
        </form>
      </div>

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
      </ul>

      <div class="sidebar-footer">
        <a href="createQR.php" class="create-btn">
          <span class="create-btn-icon">+</span>
          Create New QR Code
        </a>

        <div class="trial-section">
          <div class="trial-text">Start Free Trial for 7 days</div>
          <a href="payment.php" class="upgrade-btn">Upgrade</a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <h1 id="page-title">Active QR Codes</h1>
        <p id="page-subtitle">
          <?php if (!empty($search_query)): ?>
            Search results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>" in active QR codes - <?php echo $total_items; ?> found
          <?php else: ?>
            Currently active QR codes receiving scans
          <?php endif; ?>
        </p>
      </div>

      <main class="dashboard">
        <!-- Create New QR Card (hidden saat search) -->
        <?php if (empty($search_query)): ?>
        <div class="qr-card create-card" onclick="location.href='createQR.php'">
          <div class="create-icon">+</div>
          <h3>Create New QR Code</h3>
          <p>Generate a new QR code with custom design</p>
        </div>
        <?php endif; ?>

        <?php if (empty($active_links)): ?>
            <div class="empty-state">
                <?php if (!empty($search_query)): ?>
                  <h3>No Active QR Codes Found</h3>
                  <p>No active QR codes match your search "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                <?php else: ?>
                  <div class="empty-icon">✅</div>
                  <h3>No Active QR Codes</h3>
                  <p>You don't have any active QR codes. Create a new one or resume a paused QR code.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($active_links as $link): ?>
                <?php
                $display_name = $link['custom_url'];
                if (empty($display_name) && !empty($link['original_url'])) {
                    $host = parse_url($link['original_url'], PHP_URL_HOST);
                    if ($host) {
                        $host = preg_replace('/^www\./', '', strtolower($host));
                        
                        // Deteksi host dan ubah sesuai nama platform populer
                        if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
                            $display_name = 'YOUTUBE';
                        } elseif (strpos($host, 'facebook.com') !== false || strpos($host, 'fb.com') !== false) {
                            $display_name = 'FACEBOOK';
                        } elseif (strpos($host, 'instagram.com') !== false || strpos($host, 'ig.com') !== false) {
                            $display_name = 'INSTAGRAM';
                        } elseif (strpos($host, 'docs.google.com') !== false) {
                            $display_name = 'DOCS';
                        } elseif (strpos($host, 'drive.google.com') !== false) {
                            $display_name = 'DRIVE';
                        } elseif (strpos($host, 'linkedin.com') !== false) {
                            $display_name = 'LINKEDIN';
                        } else {
                            // Default ambil nama domain pertama
                            $parts = explode('.', $host);
                            $display_name = strtoupper($parts[0]);
                        }
                    } else {
                        $display_name = 'UNTITLED';
                    }
                }
                ?>
                <div class="qr-card" data-status="active" data-qr-title="<?php echo htmlspecialchars(strtolower($display_name)); ?>">
                    <div class="performance-indicator"></div>
                    
                    <div class="card-header">
                        <div class="qr-icon">QR</div>
                        <div class="card-title">
                            <h3><?php echo htmlspecialchars($display_name); ?></h3>
                            <div class="created-date">Created: <?php echo date('F d, Y', strtotime($link['created_at'])); ?></div>
                        </div>
                        <span class="status-badge status-active">Active</span>
                    </div>

                    <div class="qr-content">
                        <div class="qr-info">
                            <div class="info-item">
                                <span class="info-label">Original URL</span>
                                <div class="url-display"><?php echo htmlspecialchars($link['original_url']); ?></div>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Short Link</span>
                                <a href="#" class="short-link" onclick="copyToClipboard('<?php echo htmlspecialchars($link['short_url']); ?>')">
                                    <?php echo htmlspecialchars($link['short_url']); ?> <span>📋</span>
                                </a>
                            </div>
                        </div>

                        <div class="qr-visual">
                            <?php if (!empty($link['qr_image'])): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($link['qr_image']); ?>" alt="QR Code" class="qr-image">
                            <?php else: ?>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($link['short_url']); ?>" alt="QR Code" class="qr-image">
                            <?php endif; ?>
                            <div class="actions">
                                <div class="qr-stats">
                                    <div class="stat-box">
                                        <div class="stat-icon">📊</div>
                                        <span class="stat-value"><?php echo number_format($link['scan_count'] ?? 0); ?></span>
                                        <div class="stat-label">Total Scans</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-icon">📱</div>
                                        <span class="stat-value"><?php echo $link['top_device'] ?? 'N/A'; ?></span>
                                        <div class="stat-label">Top Device</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-icon">🌍</div>
                                        <span class="stat-value"><?php echo $link['top_city'] ?? 'N/A'; ?></span>
                                        <div class="stat-label">Top City</div>
                                    </div>
                                </div>
                                <a href="view_detail.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardActive.php" class="btn btn-edit">✏️ View Details</a>
                                <button class="btn btn-download" onclick="downloadQR('<?php echo urlencode($link['short_url']); ?>', 'qr_code')">⬇️ Download</button>
                                <button class="btn btn-pause" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', 'active')">⏸️ Pause</button>
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
          Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> active entries
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