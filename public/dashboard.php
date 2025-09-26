<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard QR Code</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="menu-top">
        <a href="#" class="menu-link active">All QR Codes</a>
        <a href="#" class="menu-link">Active</a>
        <a href="#" class="menu-link">Paused</a>
      </div>
      <div class="menu-bottom">
        <p class="trial-text">Start free trial for 7 days</p>
        <a href="payment.php" class="btn-upgrade">Upgrade</a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <header class="navbar">
        <button class="btn-toggle" id="toggleBtn">☰</button>
        <div class="logo">QR Code Generator PRO</div>
        <div class="user-account">
          <img src="images/user.png" alt="User" class="user-avatar">
          <span class="user-name">John Doe</span>
        </div>
      </header>

      <div class="content-header">
        <a href="#" class="btn-create">+ Create QR Code</a>
      </div>

      <main class="dashboard">
        <!-- Card 1 -->
        <div class="qr-card">
          <div class="qr-info">
            <p class="url">https://example.com</p>
            <a href="#" class="short">short.ly/abc123</a>
            <p class="scans">3 scans</p>
          </div>
          <div class="actions">
            <a href="edit.php?code=abc123" class="btn-edit">Edit</a>
            <button class="btn-download">Download</button>
          </div>
          <div class="qr-image-wrapper">
            <img src="images/sample_qr.png" alt="QR Code" class="qr-image">
          </div>
        </div>

        <!-- Card 2 -->
        <div class="qr-card">
          <div class="qr-info">
            <p class="url">https://binus.ac.id/</p>
            <a href="#" class="short">short.ly/xyz999</a>
            <p class="scans">12 scans</p>
          </div>
          <div class="actions">
            <a href="edit.php?code=xyz999" class="btn-edit">Edit</a>
            <button class="btn-download">Download</button>
          </div>
          <div class="qr-image-wrapper">
            <img src="images/sample_qr.png" alt="QR Code" class="qr-image">
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
    });
  </script>
</body>
</html>
