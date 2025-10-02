<?php

//Koneksi db
require_once __DIR__ . '/../classes/Database.php';

// Mengambil instance koneksi database
$db = Database::getInstance()->getConnection();

// Menyiapkan dan menjalankan query untuk mengambil semua data dari tabel 'links'
$result = $db->query("SELECT * FROM links ORDER BY created_at DESC");

// Menyimpan semua hasil query ke dalam sebuah array
$links = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $links[] = $row;
    }
}

// Menghitung jumlah total, QR aktif, dan QR yang dijeda
$total_qrs = count($links);
$active_qrs = 0;
$paused_qrs = 0;
foreach ($links as $link) {
    if ($link['status'] === 'active') {
        $active_qrs++;
    } else {
        $paused_qrs++;
    }
}

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

        <?php if (empty($links)): ?>
            <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <h3>No QR Codes Found</h3>
                <p>You haven't created any QR codes yet. Let's create one!</p>
            </div>
        <?php else: ?>
            <?php foreach ($links as $link): ?>
                <div class="qr-card" data-status="<?php echo htmlspecialchars($link['status']); ?>">
                    <div class="performance-indicator <?php echo ($link['status'] !== 'active') ? 'paused' : ''; ?>"></div>
                    
                    <div class="card-header">
                        <div class="qr-icon">QR</div>
                        <div class="card-title">
                            <h3><?php echo htmlspecialchars($link['custom_url'] ?: 'Untitled'); ?></h3>
                            <div class="created-date">Created: <?php echo date('F d, Y', strtotime($link['created_at'])); ?></div>
                        </div>
                        <span class="status-badge status-<?php echo htmlspecialchars($link['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($link['status'])); ?>
                        </span>
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
                                <a href="edit.php?code=<?php echo htmlspecialchars($link['short_url']); ?>&return=dashboardAll.php" class="btn btn-edit">✏️ View Details</a>
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

    // Toggle status QR Code (Pause/Resume)
    function toggleStatus(shortUrl, currentStatus) {
      const newStatus = currentStatus === 'active' ? 'paused' : 'active';
      
      // Kirim request ke server untuk update status
      fetch('updateStatus.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `short_url=${encodeURIComponent(shortUrl)}&status=${newStatus}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification(`QR Code ${newStatus === 'active' ? 'resumed' : 'paused'} successfully!`, 'success');
          // Reload halaman setelah 1 detik
          setTimeout(() => {
            location.reload();
          }, 1000);
        } else {
          showNotification('Failed to update status', 'error');
        }
      })
      .catch(error => {
        showNotification('An error occurred', 'error');
      });
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