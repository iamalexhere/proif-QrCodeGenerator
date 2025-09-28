<?php
// Get current page from URL
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
            <span class="nav-count">4</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardActive.php" class="nav-link <?php echo ($current_page == 'dashboardActive.php') ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-text">Active QR Codes</span>
            <span class="nav-count">2</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardPause.php" class="nav-link <?php echo ($current_page == 'dashboardPause.php') ? 'active' : ''; ?>">
            <span class="nav-icon">⏸️</span>
            <span class="nav-text">Paused QR Codes</span>
            <span class="nav-count">1</span>
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
        <!-- Paused QR Card -->
        <div class="qr-card" data-type="paused" data-status="paused">
          <div class="performance-indicator paused"></div>
          <div class="card-header">
            <div class="qr-icon">QR</div>
            <div class="card-title">
              <h3>Marketing Campaign</h3>
              <div class="created-date">Created: February 28, 2024</div>
            </div>
            <span class="status-badge status-paused">Paused</span>
          </div>

          <div class="qr-content">
            <div class="qr-info">
              <div class="info-item">
                <span class="info-label">Original URL</span>
                <div class="url-display">https://marketing.example.com/promo</div>
              </div>
              
              <div class="info-item">
                <span class="info-label">Short Link</span>
                <a href="#" class="short-link" onclick="copyToClipboard('short.ly/promo24')">
                  short.ly/promo24
                  <span>📋</span>
                </a>
              </div>

              <div class="stats-row">
                <div class="stat-item">
                  <span class="stat-number">892</span>
                  <div class="stat-label">Total Scans</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number">0</span>
                  <div class="stat-label">Today</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number">78%</span>
                  <div class="stat-label">Mobile</div>
                </div>
              </div>

              <div class="pause-info">
                <div class="info-item">
                  <span class="info-label">Paused Since</span>
                  <div class="pause-date">March 20, 2024 - 3 days ago</div>
                </div>
                <div class="pause-reason">
                  <span class="info-label">Reason</span>
                  <div class="reason-text">Campaign ended - scheduled pause</div>
                </div>
              </div>
            </div>

            <div class="qr-visual">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=https://marketing.example.com/promo" alt="QR Code" class="qr-image paused-image">
              <div class="actions">
                <button class="btn btn-edit" onclick="resumeQR(3)">▶️ Resume</button>
                <button class="btn btn-download" onclick="downloadQR('https://marketing.example.com/promo', 'marketing_campaign')">⬇️ Download</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State Message (shown when no paused QR codes) -->
        <div class="empty-state" style="display: none;">
          <div class="empty-icon">⏸️</div>
          <h3>No Paused QR Codes</h3>
          <p>You don't have any paused QR codes at the moment. All your QR codes are currently active and receiving scans.</p>
          <a href="dashboardActive.php" class="btn btn-edit">View Active QR Codes</a>
        </div>
      </main>
    </div>
  </div>

  <style>
    .pause-info {
      margin-top: 15px;
      padding: 15px;
      background: #fff3cd;
      border-radius: 8px;
      border-left: 4px solid #ffc107;
    }

    .pause-date, .reason-text {
      font-size: 0.9rem;
      color: #856404;
      font-weight: 500;
      margin-top: 2px;
    }

    .qr-image.paused-image {
      opacity: 0.7;
      filter: grayscale(0.3);
    }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .empty-icon {
      font-size: 4rem;
      margin-bottom: 20px;
    }

    .empty-state h3 {
      color: #333;
      margin-bottom: 15px;
      font-size: 1.5rem;
    }

    .empty-state p {
      color: #666;
      margin-bottom: 25px;
      font-size: 1.1rem;
      line-height: 1.6;
    }
  </style>

  <script>
    // Copy to clipboard function
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        const linkElement = event.target.closest('.short-link');
        const originalColor = linkElement.style.color;
        linkElement.style.color = '#4CAF50';
        
        showNotification('Link copied to clipboard!', 'success');
        
        setTimeout(() => {
          linkElement.style.color = originalColor || '#667eea';
        }, 1000);
      }).catch(err => {
        showNotification('Failed to copy link', 'error');
      });
    }

    // Download QR Code function
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

    // Resume QR Code function
    function resumeQR(id) {
      if (confirm('Are you sure you want to resume this QR code? It will start receiving scans again.')) {
        showNotification('QR Code resumed successfully! Redirecting...', 'success');
        
        // Simulate redirect after resume
        setTimeout(() => {
          window.location.href = 'dashboardActive.php';
        }, 2000);
      }
    }

    // Show notification function
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

    // Navigation loading effect
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