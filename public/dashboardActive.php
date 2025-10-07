<?php
/*****************************************************
 * DASHBOARD ACTIVE QR CODES
 * ---------------------------------------------------
 * File ini menampilkan daftar QR Code yang berstatus
 * "active" dengan fitur pencarian (search), pagination,
 * dan statistik total QR untuk sidebar.
 * 
 * Struktur utama:
 * Koneksi Database & Inisialisasi
 * Logika Pencarian (Search)
 * Hitung Total Data & Pagination
 * Ambil Data QR Aktif
 * Hitung Statistik untuk Sidebar
 * Tampilkan Tampilan HTML (Sidebar + Konten + Pagination)
 *****************************************************/

// KONEKSI DATABASE

// Memanggil file Database.php untuk menggunakan kelas Database
// Database.php berisi konfigurasi koneksi dan metode getInstance() agar hanya satu koneksi yang digunakan (Singleton Pattern)
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Membuat koneksi tunggal (singleton) ke database MySQL
$db = Database::getInstance()->getConnection();

// KONFIGURASI PAGINATION

// Jumlah item per halaman
$items_per_page = 3;

// Ambil nomor halaman saat ini dari parameter URL (?page=)
// Jika tidak ada parameter, default ke halaman 1
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Pastikan halaman minimal adalah 1 (hindari nilai negatif atau nol)
$current_page = max(1, $current_page);



// FITUR PENCARIAN (SEARCH)

// Ambil kata kunci pencarian dari URL (?search=)
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Default kondisi WHERE hanya menampilkan QR aktif
$where_clause = "WHERE l.status = 'active'";

// Variabel untuk parameter pencarian (akan digunakan di prepared statement)
$search_param = '';

// Jika ada input pencarian, tambahkan filter pada query
if (!empty($search_query)) {
    // Format pencarian menggunakan LIKE (wildcard %)
    $search_param = '%' . $search_query . '%';
    // Tambahkan kondisi pencarian untuk kolom custom_url dan original_url
    $where_clause .= " AND (l.custom_url LIKE ? OR l.original_url LIKE ?)";
}



// HITUNG TOTAL DATA (UNTUK PAGINATION)
if (!empty($search_query)) {
    // Jika sedang melakukan pencarian, gunakan prepared statement untuk keamanan (hindari SQL Injection)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM links l " . $where_clause);
    // Bind parameter pencarian ke query
    $stmt->bind_param('ss', $search_param, $search_param);
    $stmt->execute();
    // Ambil total hasil pencarian
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    // Jika tidak ada pencarian, cukup hitung total link dengan status active
    $count_query = $db->query("SELECT COUNT(*) as total FROM links WHERE status = 'active'");
    $total_items = $count_query->fetch_assoc()['total'];
}

// Hitung jumlah total halaman berdasarkan total item
$total_pages = max(1, ceil($total_items / $items_per_page));

// Pastikan current_page tidak melebihi total halaman yang ada
$current_page = min($current_page, $total_pages);

// Hitung offset untuk query LIMIT (data yang akan ditampilkan di halaman ini)
$offset = ($current_page - 1) * $items_per_page;


// AMBIL DATA QR AKTIF DARI DATABASE DENGAN STATISTIK
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
    // Jika ada pencarian, gunakan prepared statement agar aman
    $stmt = $db->prepare($base_query . $where_clause . " ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ssii', $search_param, $search_param, $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Jika tidak ada pencarian, ambil semua link aktif berdasarkan urutan waktu pembuatan
    $result = $db->query($base_query . " WHERE l.status = 'active' ORDER BY l.created_at DESC LIMIT $items_per_page OFFSET $offset");
}

// Simpan hasil query ke dalam array untuk digunakan di tampilan HTML
$active_links = [];
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
        $active_links[] = $row;
    }
}



// HITUNG STATISTIK UNTUK SIDEBAR
// Statistik total QR (aktif + paused)
// Query ini tidak menggunakan pagination atau filter search
$all_links_result = $db->query("SELECT status FROM links");

$total_qrs = 0;   // Jumlah semua QR
$active_qrs = 0;  // Jumlah QR aktif
$paused_qrs = 0;  // Jumlah QR paused

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

// Ambil nama file PHP yang sedang dibuka untuk keperluan highlight menu di sidebar
$current_page_name = basename($_SERVER['PHP_SELF']);
?>


<!-- HTML -->
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
    <!-- SIDEBAR -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>QR Dashboard</h2>
        <p>Manage your QR codes</p>
      </div>

      <!-- Form pencarian QR -->
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

      <!-- MENU SIDEBAR -->
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

      <!-- Bagian bawah sidebar -->
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


    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="header">
        <h1 id="page-title">Active QR Codes</h1>
        <p id="page-subtitle">
          <?php if (!empty($search_query)): ?>
            <!-- Menampilkan hasil pencarian -->
            Search results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>" in active QR codes - <?php echo $total_items; ?> found
          <?php else: ?>
            <!-- Jika tidak ada pencarian -->
            Currently active QR codes receiving scans
          <?php endif; ?>
        </p>
      </div>

      <main class="dashboard">
        <!-- Kartu untuk membuat QR baru (tidak ditampilkan jika sedang mencari) -->
        <?php if (empty($search_query)): ?>
        <div class="qr-card create-card" onclick="location.href='createQR.php'">
          <div class="create-icon">+</div>
          <h3>Create New QR Code</h3>
          <p>Generate a new QR code with custom design</p>
        </div>
        <?php endif; ?>

        <!-- Jika tidak ada QR aktif -->
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
          <!-- Looping untuk menampilkan setiap QR aktif -->
          <?php foreach ($active_links as $link): ?>
            <?php
            //  Jika custom_url kosong, ambil nama domain dari original_url.
            //  Kemudian, ubah beberapa domain populer menjadi label khusus.
            $display_name = $link['custom_url'];

            if (empty($display_name) && !empty($link['original_url'])) {
                $host = parse_url($link['original_url'], PHP_URL_HOST);

                if ($host) {
                    $host = preg_replace('/^www\./', '', strtolower($host));
                    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
                        $display_name = 'YOUTUBE';
                    } elseif (strpos($host, 'facebook.com') !== false || strpos($host, 'fb.com') !== false) {
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
                        $parts = explode('.', $host);
                        $display_name = strtoupper($parts[0]);
                    }
                } else {
                    $display_name = 'UNTITLED';
                }
            }
            ?>

            <!-- Card QR-->
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
                    <?php $fullShortUrl = Config::getShortUrlBase() . '/' . $link['short_url'];?>
                    <a href="<?php echo htmlspecialchars($fullShortUrl); ?>" class="short-link" onclick="copyToClipboard('<?php echo htmlspecialchars($fullShortUrl); ?>')">
                      <?php echo htmlspecialchars($fullShortUrl); ?> <span>📋</span>
                    </a>
                  </div>
                </div>

                <div class="qr-visual">
                  <?php if (!empty($link['qr_image'])): ?>
                    <!-- QR disimpan di database dalam format BLOB -->
                    <img src="data:image/png;base64,<?php echo base64_encode($link['qr_image']); ?>" alt="QR Code" class="qr-image">
                  <?php else: ?>
                    <!-- QR di-generate dari API eksternal -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($link['short_url']); ?>" alt="QR Code" class="qr-image">
                  <?php endif; ?>

                  <!-- Untuk mengambil Statistik diambil dari view_details-->
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

                    <!-- Tombol untuk melakukan view details, donwload, dan resume/pause -->
                    <button class="btn btn-edit" onclick="window.location.href='view_detail.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardActive.php'">✏️ View Details</button>
                    <button class="btn btn-download" onclick="downloadQR('<?php echo base64_encode($link['qr_image']); ?>', '<?php echo $link['short_url']; ?>')">⬇️ Download</button>
                    <button class="btn btn-pause" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', 'active')">⏸️ Pause</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </main>

      <!-- PAGINATION -->
      <?php if ($total_pages > 1): ?>
      <div class="pagination-container">
        <div class="pagination">
          <!-- Tombol Previous -->
          <?php 
          $prev_link = "?page=" . ($current_page - 1);
          if (!empty($search_query)) $prev_link .= "&search=" . urlencode($search_query);
          ?>
          <?php if ($current_page > 1): ?>
            <a href="<?php echo $prev_link; ?>" class="pagination-btn pagination-prev">Prev</a>
          <?php else: ?>
            <span class="pagination-btn pagination-prev disabled">Prev</span>
          <?php endif; ?>

          <!-- Nomor Halaman -->
          <?php
          $max_pages_shown = 5;
          $start_page = max(1, $current_page - 2);
          $end_page = min($total_pages, $start_page + $max_pages_shown - 1);
          if ($end_page - $start_page < $max_pages_shown - 1) {
              $start_page = max(1, $end_page - $max_pages_shown + 1);
          }

          // Fungsi bantu untuk membentuk URL halaman
          function buildPageUrl($page, $search) {
              $url = "?page=" . $page;
              if (!empty($search)) $url .= "&search=" . urlencode($search);
              return $url;
          }

          // Jika range tidak mencakup halaman pertama
          if ($start_page > 1): ?>
            <a href="<?php echo buildPageUrl(1, $search_query); ?>" class="pagination-btn">1</a>
            <?php if ($start_page > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
          <?php endif; ?>

          <!-- Looping halaman aktif -->
          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
              <span class="pagination-btn active"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="<?php echo buildPageUrl($i, $search_query); ?>" class="pagination-btn"><?php echo $i; ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <!-- Jika range tidak mencakup halaman terakhir -->
          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
            <a href="<?php echo buildPageUrl($total_pages, $search_query); ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
          <?php endif; ?>

          <!-- Tombol Next -->
          <?php 
          $next_link = "?page=" . ($current_page + 1);
          if (!empty($search_query)) $next_link .= "&search=" . urlencode($search_query);
          ?>
          <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo $next_link; ?>" class="pagination-btn pagination-next">Next</a>
          <?php else: ?>
            <span class="pagination-btn pagination-next disabled">Next</span>
          <?php endif; ?>
        </div>

        <!-- Info halaman -->
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
