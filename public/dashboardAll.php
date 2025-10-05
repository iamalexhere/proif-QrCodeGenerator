<?php
// Mengambil nama file PHP yang sedang dibuka untuk menentukan menu aktif di sidebar
$current_page = basename($_SERVER['PHP_SELF']);

// Require authentication
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Statistics.php';
require_once __DIR__ . '/../config/Config.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();
$statistics = new Statistics();

// Get user's links only
$sql = "SELECT id, original_url, short_url, custom_url, logo_path, qr_color, status, created_at FROM links WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$links = [];
while ($row = $result->fetch_assoc()) {
    // Get statistics for each link
    $stats = $statistics->getLinkStatistics($row['id']);
    $row['stats'] = $stats['summary'];
    $links[] = $row;
}
$stmt->close();

// Count by status
$totalLinks = count($links);
$activeLinks = count(array_filter($links, fn($l) => $l['status'] === 'active'));
$pausedLinks = count(array_filter($links, fn($l) => $l['status'] === 'paused'));

$shortDomain = Config::get('SHORT_DOMAIN', 'localhost/qr/public/r');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard QR Code - All</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>QR Dashboard</h2>
        <p>Manage your QR codes</p>
      </div>

      <ul class="nav-menu">
        <li class="nav-item">
          <a href="dashboardAll.php" class="nav-link <?php echo ($current_page == 'dashboardAll.php') ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-text">All QR Codes</span>
            <span class="nav-count"><?php echo $totalLinks; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardActive.php" class="nav-link <?php echo ($current_page == 'dashboardActive.php') ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-text">Active QR Codes</span>
            <span class="nav-count"><?php echo $activeLinks; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardPause.php" class="nav-link <?php echo ($current_page == 'dashboardPause.php') ? 'active' : ''; ?>">
            <span class="nav-icon">⏸️</span>
            <span class="nav-text">Paused QR Codes</span>
            <span class="nav-count"><?php echo $pausedLinks; ?></span>
          </a>
        </li>
      </ul>

      <div class="sidebar-footer">
        <a href="index.php" class="create-btn">
          <span class="create-btn-icon">+</span>
          Create New QR Code
        </a>

        <?php if ($user['plan'] === 'free'): ?>
        <div class="trial-section">
          <div class="trial-text"><?php echo $auth->getRemainingQRCodes(); ?> QR codes left this month</div>
          <a href="payment.php" class="upgrade-btn">Upgrade to Pro</a>
        </div>
        <?php else: ?>
        <div class="trial-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
          <div class="trial-text">Pro Plan Active</div>
        </div>
        <?php endif; ?>
        
        <div style="padding: 15px; border-top: 1px solid #e0e0e0;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <?php if ($user['picture']): ?>
            <img src="<?php echo htmlspecialchars($user['picture']); ?>" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%;">
            <?php endif; ?>
            <div style="flex: 1; overflow: hidden;">
              <div style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars($user['name']); ?>
              </div>
              <div style="font-size: 0.75rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars($user['email']); ?>
              </div>
            </div>
          </div>
          <a href="logout.php" style="display: block; text-align: center; padding: 8px; background: #f5f5f5; border-radius: 6px; text-decoration: none; color: #666; font-size: 0.9rem; transition: all 0.2s;">
            🚪 Logout
          </a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <h1 id="page-title">All QR Codes</h1>
        <p id="page-subtitle">Manage and track your QR codes with advanced analytics</p>
      </div>

      <main class="dashboard">
        <!-- Create New QR Card -->
        <div class="qr-card create-card" onclick="location.href='index.php'">
          <div class="create-icon">+</div>
          <h3>Create New QR Code</h3>
          <p>Generate a new QR code with custom design</p>
        </div>

        <?php if (empty($links)): ?>
        <!-- Empty State -->
        <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
          <div style="font-size: 4rem; margin-bottom: 20px;">📊</div>
          <h3>No QR Codes Yet</h3>
          <p>Create your first QR code to get started!</p>
          <a href="index.php" class="btn btn-edit">Create QR Code</a>
        </div>
        <?php else: ?>
        <?php foreach ($links as $link): 
            $shortUrl = (strpos($shortDomain, 'http') === 0 ? '' : 'http://') . $shortDomain . '/' . $link['short_url'];
            $statusClass = $link['status'] === 'active' ? 'status-active' : 'status-paused';
            $statusLabel = ucfirst($link['status']);
            $totalScans = $link['stats']['total_clicks'] ?? 0;
            $todayScans = $link['stats']['clicks_today'] ?? 0;
            $mobilePercentage = $link['stats']['mobile_percentage'] ?? 0;
            $createdDate = date('F j, Y', strtotime($link['created_at']));
            $linkTitle = parse_url($link['original_url'], PHP_URL_HOST) ?: 'Link';
        ?>
        <!-- QR Card -->
        <div class="qr-card" data-type="<?php echo $link['status']; ?>" data-status="<?php echo $link['status']; ?>">
          <div class="performance-indicator <?php echo $link['status'] === 'paused' ? 'paused' : ''; ?>"></div>
          <div class="card-header">
            <div class="qr-icon">QR</div>
            <div class="card-title">
              <h3><?php echo htmlspecialchars($linkTitle); ?></h3>
              <div class="created-date">Created: <?php echo $createdDate; ?></div>
            </div>
            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
          </div>

          <div class="qr-content">
            <div class="qr-info">
              <div class="info-item">
                <span class="info-label">Original URL</span>
                <div class="url-display"><?php echo htmlspecialchars($link['original_url']); ?></div>
              </div>
              
              <div class="info-item">
                <span class="info-label">Short Link</span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <a href="<?php echo htmlspecialchars($shortUrl); ?>" target="_blank" class="short-link" style="flex: 1;">
                    <?php echo htmlspecialchars($shortUrl); ?>
                  </a>
                  <span onclick="copyToClipboard('<?php echo htmlspecialchars($shortUrl); ?>')" style="cursor: pointer; font-size: 1.2rem;" title="Copy to clipboard">📋</span>
                </div>
              </div>

              <div class="stats-row">
                <div class="stat-item">
                  <span class="stat-number"><?php echo number_format($totalScans); ?></span>
                  <div class="stat-label">Total Scans</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number"><?php echo number_format($todayScans); ?></span>
                  <div class="stat-label">Today</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number"><?php echo round($mobilePercentage); ?>%</span>
                  <div class="stat-label">Mobile</div>
                </div>
              </div>
            </div>

            <div class="qr-visual">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($link['original_url']); ?>" alt="QR Code" class="qr-image">
              <div class="actions">
                <a href="edit.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardAll.php" class="btn btn-edit">✏️ Edit</a>
                <button class="btn btn-download" onclick="downloadQR('<?php echo htmlspecialchars($link['original_url']); ?>', '<?php echo htmlspecialchars($link['short_url']); ?>')">⬇️ Download</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <script>
    // Untuk copy text 
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        // Jika sudah berhasil copy maka ubah warna text nya 
        const linkElement = event.target.closest('.short-link');
        const originalColor = linkElement.style.color;
        linkElement.style.color = '#4CAF50';
        
        // Jika bisa di copy maka ada munculkan notifikasi bahwa sukses 
        showNotification('Link copied to clipboard!', 'success');
        
        //Mengembalikan ke warna awal setelah 1 detik 
        setTimeout(() => {
          linkElement.style.color = originalColor || '#667eea';
        }, 1000);
      }).catch(err => {
        //Jika gagal untuk di copy 
        showNotification('Failed to copy link', 'error');
      });
    }

    // Download QR Code nantinya akan disesuaikan lagi dengan page utama 
    function downloadQR(url, filename) {
      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
      
      // Create temporary link element
      const link = document.createElement('a');
      link.href = qrUrl;
      link.download = `${filename}_qr_code.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      showNotification('QR Code downloaded successfully!', 'success');
    }

    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.textContent = message;
      
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 1000;
        opacity: 0;
        transform: translateY(-20px);
        transition: all 0.3s ease;
        ${type === 'success' ? 'background: #4CAF50;' : 'background: #f44336;'}
      `;
      
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
      }, 100);
      
      setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      }, 3000);
    }

    document.querySelectorAll('.nav-link').forEach(link => {
      if (!link.classList.contains('active')) {
        link.addEventListener('click', function(e) {
          const spinner = document.createElement('div');
          spinner.innerHTML = '⏳';
          spinner.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            z-index: 1000;
            animation: spin 1s linear infinite;
          `;
          
          const style = document.createElement('style');
          style.textContent = `
            @keyframes spin {
              0% { transform: translate(-50%, -50%) rotate(0deg); }
              100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
          `;
          document.head.appendChild(style);
          document.body.appendChild(spinner);
          
          setTimeout(() => {
            document.body.removeChild(spinner);
            document.head.removeChild(style);
          }, 500);
        });
      }
    });
  </script>
</body>
</html>