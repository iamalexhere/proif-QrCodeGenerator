<?php
// Memuat file koneksi database
require_once __DIR__ . '/../classes/Database.php';

// Mengambil instance koneksi database
$db = Database::getInstance()->getConnection();

// --- Query 1: Mengambil HANYA link yang statusnya 'active' ---
$result_active = $db->query("SELECT * FROM links WHERE status = 'active' ORDER BY created_at DESC");

// Menyimpan hasil query ke dalam sebuah array
$active_links = [];
if ($result_active) {
    while ($row = $result_active->fetch_assoc()) {
        $active_links[] = $row;
    }
} else {
    die("Error saat mengambil data aktif: " . $db->error);
}

// --- Query 2: Untuk sidebar ---
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

// Halaman aktif untuk sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard QR Code - Active</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <div class="container">
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

    <div class="main-content">
      <div class="header">
        <h1 id="page-title">Active QR Codes</h1>
        <p id="page-subtitle">Currently active QR codes receiving scans</p>
      </div>

      <main class="dashboard">
        <div class="qr-card create-card" onclick="location.href='createQR.php'">
          <div class="create-icon">+</div>
          <h3>Create New QR Code</h3>
          <p>Generate a new QR code with custom design</p>
        </div>

        <?php if (empty($active_links)): ?>
          <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <div class="empty-icon">✅</div>
            <h3>No Active QR Codes</h3>
            <p>You don't have any active QR codes. Create a new one or resume a paused QR code.</p>
          </div>
        <?php else: ?>
          <?php foreach ($active_links as $link): ?>
            <div class="qr-card" data-status="active">
              <div class="performance-indicator"></div>
              <div class="card-header">
                <div class="qr-icon">QR</div>
                <div class="card-title">
                  <h3><?php echo htmlspecialchars($link['custom_url'] ?: 'Untitled'); ?></h3>
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
                      <?php echo htmlspecialchars($link['short_url']); ?>
                      <span>📋</span>
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

                    <a href="edit.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardActive.php" class="btn btn-edit">✏️ View Details</a>
                    <button class="btn btn-download" onclick="downloadQR('<?php echo urlencode($link['short_url']); ?>', 'qr_code')">⬇️ Download</button>
                    <button class="btn btn-pause" onclick="toggleStatus('<?php echo htmlspecialchars($link['short_url']); ?>', 'active')">⏸️ Pause</button>
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
    // Copy to clipboard
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        showNotification('Link copied to clipboard!', 'success');
      });
    }

    // Download QR
    function downloadQR(url, filename) {
      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
      const link = document.createElement('a');
      link.href = qrUrl;
      link.download = `${filename}_qr_code.png`;
      link.click();
      showNotification('QR Code downloaded successfully!', 'success');
    }

    // Pop-up konfirmasi sebelum update status
    function toggleStatus(shortUrl, currentStatus) {
      const newStatus = currentStatus === 'active' ? 'paused' : 'active';
      const actionText = newStatus === 'paused' ? 'pause' : 'resume';

      const confirmBox = document.createElement('div');
      confirmBox.className = 'confirm-box';
      confirmBox.innerHTML = `
        <div class="confirm-content">
          <h3>Confirm Action</h3>
          <p>Are you sure you want to <b>${actionText}</b> this QR Code?</p>
          <div class="confirm-actions">
            <button id="confirm-yes" class="btn-confirm yes">Yes</button>
            <button id="confirm-no" class="btn-confirm no">Cancel</button>
          </div>
        </div>
      `;
      document.body.appendChild(confirmBox);
      setTimeout(() => confirmBox.classList.add('show'), 10);

      document.getElementById('confirm-yes').addEventListener('click', () => {
        updateStatus(shortUrl, newStatus);
        confirmBox.remove();
      });
      document.getElementById('confirm-no').addEventListener('click', () => {
        confirmBox.classList.remove('show');
        setTimeout(() => confirmBox.remove(), 200);
      });
    }

    // Update status QR + redirect
    function updateStatus(shortUrl, newStatus) {
      fetch('updateStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `short_url=${encodeURIComponent(shortUrl)}&status=${newStatus}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showNotification(`QR Code ${newStatus === 'active' ? 'resumed' : 'paused'} successfully!`, 'success');
          setTimeout(() => {
            if (newStatus === 'paused') {
              window.location.href = 'dashboardPause.php';
            } else {
              location.reload();
            }
          }, 1000);
        } else {
          showNotification('Failed to update status', 'error');
        }
      })
      .catch(() => showNotification('An error occurred', 'error'));
    }

    // Notification popup
    function showNotification(msg, type) {
      const el = document.createElement('div');
      el.className = `notification ${type}`;
      el.textContent = msg;
      el.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px;
        border-radius: 8px; color: white; font-weight: 600; z-index: 1000;
        opacity: 0; transform: translateY(-20px);
        transition: all 0.3s ease;
        ${type === 'success' ? 'background:#4CAF50;' : 'background:#f44336;'}
      `;
      document.body.appendChild(el);
      setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 100);
      setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(-20px)'; setTimeout(() => el.remove(), 300); }, 3000);
    }
  </script>

  <style>
    .confirm-box {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.5);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity .2s ease; z-index: 9999;
    }
    .confirm-box.show { opacity: 1; }
    .confirm-content {
      background: #fff; color: #333;
      border-radius: 12px; padding: 25px 30px;
      max-width: 340px; text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      animation: scaleIn .25s ease;
    }
    .confirm-content h3 { color: #0ea5e9; margin-bottom: 10px; }
    .confirm-actions { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
    .btn-confirm {
      padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;
    }
    .btn-confirm.yes { background: #0ea5e9; color: #fff; }
    .btn-confirm.no { background: #ddd; color: #333; }
    @keyframes scaleIn {
      from { transform: scale(0.8); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
  </style>
</body>
</html>
