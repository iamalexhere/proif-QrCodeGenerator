<?php
// Memuat class dan konfigurasi
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Mengambil nama file untuk menu aktif di sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$links = [];
$stats = ['all' => 0, 'active' => 0, 'paused' => 0];

try {
    $db = Database::getInstance()->getConnection();

    // Query untuk mengambil SEMUA link
    $result = $db->query("SELECT * FROM links ORDER BY create_at DESC;");
    while ($row = $result->fetch_assoc()) {
        $links[] = $row;
    }

    // Query untuk menghitung jumlah link berdasarkan status
    $countResult = $db->query("SELECT status, COUNT(*) as count FROM links GROUP BY status");
    while ($row = $countResult->fetch_assoc()) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = $row['count'];
        }
    }
    $stats['all'] = array_sum($stats);

} catch (Exception $e) {
    $error = "Gagal mengambil data: " . $e->getMessage();
}
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
            <span class="nav-count"><?php echo $stats['all']; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardActive.php" class="nav-link <?php echo ($current_page == 'dashboardActive.php') ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-text">Active QR Codes</span>
            <span class="nav-count"><?php echo $stats['active']; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboardPause.php" class="nav-link <?php echo ($current_page == 'dashboardPause.php') ? 'active' : ''; ?>">
            <span class="nav-icon">⏸️</span>
            <span class="nav-text">Paused QR Codes</span>
            <span class="nav-count"><?php echo $stats['paused']; ?></span>
          </a>
        </li>
      </ul>

      <div class="sidebar-footer">
        <a href="index.php" class="create-btn"> <span class="create-btn-icon">+</span>
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
        <h1>All QR Codes</h1>
        <p>Manage and track your QR codes with advanced analytics</p>
      </div>

      <main class="dashboard">
        <div class="qr-card create-card" onclick="location.href='index.php'"> <div class="create-icon">+</div>
          <h3>Create New QR Code</h3>
          <p>Generate a new QR code with custom design</p>
        </div>

        <?php if (!empty($links)): ?>
            <?php foreach ($links as $link): ?>
                <div class="qr-card" data-status="<?php echo htmlspecialchars($link['status']); ?>">
                  <div class="card-header">
                    <div class="qr-icon">QR</div>
                    <div class="card-title">
                      <h3><?php echo htmlspecialchars($link['original_url']); ?></h3>
                      <div class="created-date">Created: <?php echo date('F d, Y', strtotime($link['create_at'])); ?></div>
                    </div>
                    <span class="status-badge status-<?php echo htmlspecialchars($link['status']); ?>"><?php echo ucfirst(htmlspecialchars($link['status'])); ?></span>
                  </div>
                  <div class="qr-content">
                    <div class="qr-info">
                      <div class="info-item">
                        <span class="info-label">Short Link</span>
                        <a href="<?php echo htmlspecialchars($link['short_url']); ?>" target="_blank" class="short-link"><?php echo htmlspecialchars($link['short_url']); ?></a>
                      </div>
                      </div>
                    <div class="qr-visual">
                      <img src="images/base.png" alt="QR Code" class="qr-image">
                      <div class="actions">
                        <a href="edit.php?id=<?php echo $link['id']; ?>" class="btn btn-edit">✏️ Edit</a>
                        <button class="btn btn-download">⬇️ Download</button>
                      </div>
                    </div>
                  </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Belum ada QR code yang dibuat.</p>
        <?php endif; ?>

      </main>
    </div>
  </div>
  <script>
    // Script JavaScript Anda bisa ditaruh di sini
  </script>
</body>
</html>