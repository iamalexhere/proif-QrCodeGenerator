<?php
// Mengambil nama file PHP yang sedang dibuka untuk menentukan menu aktif di sidebar
$current_page = basename($_SERVER['PHP_SELF']);
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
        <h1 id="page-title">All QR Codes</h1>
        <p id="page-subtitle">Manage and track your QR codes with advanced analytics</p>
      </div>

      <main class="dashboard">
        <!-- Create New QR Card -->
        <div class="qr-card create-card" onclick="location.href='createQR.php'">
          <div class="create-icon">+</div>
          <h3>Create New QR Code</h3>
          <p>Generate a new QR code with custom design</p>
        </div>

        <!-- Active QR Card 1 -->
        <div class="qr-card" data-type="active" data-status="active">
          <div class="performance-indicator"></div>
          <div class="card-header">
            <div class="qr-icon">QR</div>
            <div class="card-title">
              <h3>Example Website</h3>
              <div class="created-date">Created: March 15, 2024</div>
            </div>
            <span class="status-badge status-active">Active</span>
          </div>

          <div class="qr-content">
            <div class="qr-info">
              <div class="info-item">
                <span class="info-label">Original URL</span>
                <div class="url-display">https://example.com</div>
              </div>
              
              <div class="info-item">
                <span class="info-label">Short Link</span>
                <a href="#" class="short-link" onclick="copyToClipboard('short.ly/abc123')">
                  short.ly/abc123
                  <span>📋</span>
                </a>
              </div>

              <div class="stats-row">
                <div class="stat-item">
                  <span class="stat-number">247</span>
                  <div class="stat-label">Total Scans</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number">12</span>
                  <div class="stat-label">Today</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number">85%</span>
                  <div class="stat-label">Mobile</div>
                </div>
              </div>
            </div>

            <div class="qr-visual">
              <!-- sementara mengambil gambar dari api nanti diganti  -->
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=https://example.com" alt="QR Code" class="qr-image">
              <div class="actions">
                <a href="edit.php?id=1&return=dashboardAll.php" class="btn btn-edit">✏️ Edit</a>
                <button class="btn btn-download" onclick="downloadQR('https://example.com', 'example_website')">⬇️ Download</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Active QR Card 2 -->
        <div class="qr-card" data-type="active" data-status="active">
          <div class="performance-indicator"></div>
          <div class="card-header">
            <div class="qr-icon">QR</div>
            <div class="card-title">
              <h3>Binus University</h3>
              <div class="created-date">Created: March 10, 2024</div>
            </div>
            <span class="status-badge status-active">Active</span>
          </div>

          <div class="qr-content">
            <div class="qr-info">
              <div class="info-item">
                <span class="info-label">Original URL</span>
                <div class="url-display">https://binus.ac.id/</div>
              </div>
              
              <div class="info-item">
                <span class="info-label">Short Link</span>
                <a href="#" class="short-link" onclick="copyToClipboard('short.ly/xyz999')">
                  short.ly/xyz999
                  <span>📋</span>
                </a>
              </div>

              <div class="stats-row">
                <div class="stat-item">
                  <span class="stat-number">1,256</span>
                  <div class="stat-label">Total Scans</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number">45</span>
                  <div class="stat-label">Today</div>
                </div>
                <div class="stat-item">
                  <span class="stat-number">92%</span>
                  <div class="stat-label">Mobile</div>
                </div>
              </div>
            </div>

            <div class="qr-visual">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=https://binus.ac.id/" alt="QR Code" class="qr-image">
              <div class="actions">
                <a href="edit.php?id=2&return=dashboardAll.php" class="btn btn-edit">✏️ Edit</a>
                <button class="btn btn-download" onclick="downloadQR('https://binus.ac.id/', 'binus_university')">⬇️ Download</button>
              </div>
            </div>
          </div>
        </div>

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
            </div>

            <div class="qr-visual">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=https://marketing.example.com/promo" alt="QR Code" class="qr-image">
              <div class="actions">
                <a href="edit.php?id=3&return=dashboardAll.php" class="btn btn-secondary">✏️ Resume</a>
                <button class="btn btn-download" onclick="downloadQR('https://marketing.example.com/promo', 'marketing_campaign')">⬇️ Download</button>
              </div>
            </div>
          </div>
        </div>
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