<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard QR Code</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    /* CSS tambahan khusus untuk halaman Pause */
    .btn-edit,
    .btn-download {
      pointer-events: none;      /* tidak bisa di klik */
      opacity: 0.5;              /* kelihatan abu-abu */
      cursor: not-allowed;       /* cursor tanda larangan */
    }
  </style>
</head>
<body>
  <div class="container">

    <!-- Sidebar include -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
      <header class="navbar">
        <div class="left-nav">
          <button class="btn-toggle" id="toggleBtn">☰</button>
          <div class="logo">QR Code Generator PRO</div>
        </div>
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
            <label class="label">Original URL</label>
            <p class="url">https://example.com</p>
            <label class="label">Short Link</label>
            <a href="#" class="short">short.ly/abc123</a>
            <p class="scans"><strong>3</strong> scans</p>
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
            <label class="label">Original URL</label>
            <p class="url">https://binus.ac.id/</p>
            <label class="label">Short Link</label>
            <a href="#" class="short">short.ly/xyz999</a>
            <p class="scans"><strong>12</strong> scans</p>
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
