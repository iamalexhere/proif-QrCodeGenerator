<?php
// Menampilkan error untuk mempermudah debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Memuat file koneksi database
require_once __DIR__ . '/../classes/Database.php';

// Mengambil instance koneksi database
$db = Database::getInstance()->getConnection();

// --- Query 1: Ambil QR yang statusnya bukan active (paused) ---
$result_paused = $db->query("SELECT * FROM links WHERE status != 'active' ORDER BY created_at DESC");

// Menyimpan hasil query ke dalam sebuah array
$paused_links = [];
if ($result_paused) {
    while ($row = $result_paused->fetch_assoc()) {
        $paused_links[] = $row;
    }
} else {
    die("Error saat mengambil data paused: " . $db->error);
}

// --- Query 2: Hitung total, active, paused untuk sidebar ---
$result_all = $db->query("SELECT status FROM links");
$total_qrs = 0;
$active_qrs = 0;
$paused_qrs = 0;
if ($result_all) {
    $total_qrs = $result_all->num_rows;
    while ($row = $result_all->fetch_assoc()) {
        if ($row['status'] === 'active') {
            $active_qrs++;
        } else {
            $paused_qrs++;
        }
    }
}

// Mengambil nama file PHP yang sedang dibuka untuk menentukan menu aktif di sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard QR Code - Paused</title>
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
            <span class="nav-count"><?php echo $total_qrs; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardActive.php" class="nav-link <?php echo ($current_page == 'dashboardActive.php') ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-text">Active QR Codes</span>
            <span class="nav-count"><?php echo $active_qrs; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardPause.php" class="nav-link <?php echo ($current_page == 'dashboardPause.php') ? 'active' : ''; ?>">
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
        <h1 id="page-title">Paused QR Codes</h1>
        <p id="page-subtitle">QR codes that are temporarily disabled</p>
      </div>

      <main class="dashboard">
        <?php if (empty($paused_links)): ?>
            <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <div class="empty-icon">⏸️</div>
                <h3>No Paused QR Codes</h3>
                <p>You don't have any paused QR codes at the moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($paused_links as $link): ?>
                <div class="qr-card" data-status="paused">
                    <div class="performance-indicator paused"></div>
                    <div class="card-header">
                        <div class="qr-icon">QR</div>
                        <div class="card-title">
                            <h3><?php echo htmlspecialchars($link['custom_url'] ?: 'Untitled'); ?></h3>
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
                                <?php $baseDomain = 'http://qr.local/r/'; $fullShortUrl = $baseDomain . $link['short_url'];?>
                                <a href="<?php echo htmlspecialchars($fullShortUrl); ?>" class="short-link" onclick="copyToClipboard('<?php echo htmlspecialchars($link['short_url']); ?>')">
                                    <?php echo htmlspecialchars($fullShortUrl); ?>
                                    <span>📋</span>
                                </a>
                            </div>
                        </div>

                        <div class="qr-visual">
                            <?php if (!empty($link['qr_image'])): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($link['qr_image']); ?>" alt="QR Code" class="qr-image paused-image">
                            <?php else: ?>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($link['short_url']); ?>" alt="QR Code" class="qr-image paused-image">
                            <?php endif; ?>
                            <div class="actions">
                                <!-- Tombol View Detail -->
                                <a href="view_detail.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardPause.php" class="btn btn-edit">✏️ View Details</a>
                                <!-- Tombol Download (kiri) -->
                                <button class="btn btn-download" onclick="downloadQR('<?php echo urlencode($link['short_url']); ?>', 'qr_code')">⬇️ Download</button>
                                <!-- Tombol Resume (kanan) -->
                                <button class="btn btn-resume" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', 'paused')">▶️ Resume</button>
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
    // Copy link
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        showNotification('Link copied to clipboard!', 'success');
      }).catch(() => {
        showNotification('Failed to copy link', 'error');
      });
    }

    // Download QR
    function downloadQR(url, filename) {
      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
      const link = document.createElement('a');
      link.href = qrUrl;
      link.download = `${filename}_qr_code.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      showNotification('QR Code downloaded successfully!', 'success');
    }

    // Resume (update status ke active)
    function toggleStatus(shortUrl, currentStatus) {
      const newStatus = currentStatus === 'active' ? 'paused' : 'active';

      fetch('updateStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `short_url=${encodeURIComponent(shortUrl)}&status=${newStatus}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification(`QR Code ${newStatus === 'active' ? 'resumed' : 'paused'} successfully!`, 'success');
          setTimeout(() => { location.reload(); }, 1000);
        } else {
          showNotification('Failed to update status', 'error');
        }
      })
      .catch(() => {
        showNotification('An error occurred', 'error');
      });
    }

    // Notifikasi
    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.textContent = message;
      notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px;
        border-radius: 8px; color: white; font-weight: 600; z-index: 1000;
        opacity: 0; transform: translateY(-20px); transition: all 0.3s ease;
        ${type === 'success' ? 'background: #4CAF50;' : 'background: #f44336;'}
      `;
      document.body.appendChild(notification);
      setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
      }, 100);
      setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => { document.body.removeChild(notification); }, 300);
      }, 3000);
    }
  </script>
</body>
</html>
