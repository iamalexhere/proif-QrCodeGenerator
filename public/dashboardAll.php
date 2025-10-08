<?php 
/***********************************************************
 * DASHBOARD QR CODE - ALL
 * ---------------------------------------------------------
 * File ini menampilkan semua QR Code yang tersimpan di DB 
 * dengan fitur pencarian, pagination, dan statistik QR aktif.
 * 
 * Struktur utama:
 *  Koneksi Database
 *  Pagination + Pencarian (Search)
 *  Query Data + Perhitungan Total
 *  Perhitungan Statistik Sidebar
 *  Render Tampilan (Sidebar, Main, Pagination)
 ***********************************************************/

// KONEKSI DATABASE
// Memuat koneksi database
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';
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
$where_clause = '';  // Menyimpan kondisi pencarian SQL
$search_param = '';  // Menyimpan parameter untuk prepared statement

// Jika user mengetikkan sesuatu di search bar
if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    // Mencari berdasarkan custom_url atau original_url
    $where_clause = " WHERE custom_url LIKE ? OR original_url LIKE ?";
}

// HITUNG TOTAL DATA
// Tujuan: untuk menentukan total halaman yang tersedia
if (!empty($search_query)) {
    // Jika ada pencarian, gunakan prepared statement agar aman dari SQL Injection
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links" . $where_clause);
    $stmt->bind_param('ss', $search_param, $search_param);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    // Jika tidak ada pencarian, hitung total semua data
    $count_query = $db->query("SELECT COUNT(*) as total FROM links");
    $total_items = $count_query->fetch_assoc()['total'];
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
    $stmt->bind_param('ssii', $search_param, $search_param, $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($base_query . " ORDER BY l.created_at DESC LIMIT $items_per_page OFFSET $offset");
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

// HITUNG STATISTIK UNTUK SIDEBAR
// Mengambil semua status QR untuk menampilkan jumlah total, aktif, dan pause
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

      <!-- Navigasi Menu -->
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

      <!-- Tombol Buat QR Baru & Upgrade -->
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

    <!-- MAIN CONTENT-->
    <div class="main-content">
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
                    <?php $fullShortUrl = Config::getShortUrlBase() . '/' . $link['short_url'];?>
                    <a href="<?php echo htmlspecialchars($fullShortUrl); ?>" 
                      class="short-link" 
                      target="_blank"
                      onclick="event.preventDefault(); window.open('<?php echo htmlspecialchars($fullShortUrl); ?>', '_blank');">
                      <?php echo htmlspecialchars($fullShortUrl); ?> 
                      <span class="copy-icon" 
                            title="Copy short URL" 
                            onclick="copyToClipboard('<?php echo htmlspecialchars($fullShortUrl); ?>', event)">📋</span>
                    </a>
                  </div>
                </div>

                <!-- Gambar QR-->
                <div class="qr-visual">
                  <?php if (!empty($link['qr_image'])): ?>
                    <img src="data:image/png;base64,<?php echo base64_encode($link['qr_image']); ?>" alt="QR Code" class="qr-image">
                  <?php else: ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($link['short_url']); ?>" alt="QR Code" class="qr-image">
                  <?php endif; ?>

                  <div class="actions">
                    <!-- Statistik, diambil dari view_details-->
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

                    <!-- tombol view details, donwload, dan resume /pause -->
                    <button class="btn btn-edit" onclick="window.location.href='view_detail.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardAll.php'">✏️ View Details</button>
                    <button class="btn btn-download" onclick="downloadQR('<?php echo urlencode($link['short_url']); ?>', 'qr_code')">⬇️ Download</button>
                    <button class="btn btn-pause" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', '<?php echo htmlspecialchars($link['status']); ?>')">
                      <?php echo ($link['status'] === 'active') ? '⏸️ Pause' : '▶️ Resume'; ?>
                    </button>
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