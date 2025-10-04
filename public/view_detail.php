<?php
// edit.php
// Tangkap parameter kode QR dan halaman asal
$code       = $_GET['code'] ?? '';                   // short code QR
$returnPage = $_GET['return'] ?? 'dashboardAll.php'; // default balik ke dashboardAll
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit QR Code</title>
  <link rel="stylesheet" href="css/view_detail.css">
  <!-- Chart.js untuk grafik -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- Navbar dengan tombol back dinamis -->
  <header class="navbar">
    <div class="logo">QR Code Generator</div>
    <a href="<?php echo htmlspecialchars($returnPage); ?>" class="btn-back">&larr; Back to Dashboard</a>
  </header>

  <main class="edit-container">
    <!-- Kiri: QR Code + form edit -->
    <section class="edit-left">
      <!-- QR Code Image -->
      <img src="images/base.png" alt="QR Code" class="qr-image">
      <div class="qr-details">
        <p><strong>Short Link:</strong> short.ly/<?php echo htmlspecialchars($code); ?></p>
        <form class="edit-form" method="post" action="save_edit.php">
          <input type="hidden" name="code" value="<?php echo htmlspecialchars($code); ?>">
          <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnPage); ?>">
          <label for="url">Destination URL:</label>
          <input type="text" id="url" name="url" value="https://example.com">
          <button type="submit">Save Changes</button>
        </form>
      </div>
    </section>

    <!-- Kanan: Statistik -->
    <section class="edit-right">
      <h2>Statistics</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Scans</h3>
          <p class="stat-number">123</p>
        </div>
        <div class="stat-card">
          <h3>Devices</h3>
          <canvas id="deviceChart"></canvas>
        </div>
        <div class="stat-card">
          <h3>By City</h3>
          <canvas id="cityChart"></canvas>
        </div>
        <div class="stat-card">
          <h3>By Country</h3>
          <canvas id="countryChart"></canvas>
        </div>
        <div class="stat-card wide">
          <h3>Scans per Week</h3>
          <canvas id="weekChart"></canvas>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Data dummy, nanti diganti PHP
    const deviceData = {
      labels: ['iOS', 'Android', 'Other'],
      datasets: [{
        data: [50, 70, 10],
        backgroundColor: ['#007bff','#28a745','#ffc107']
      }]
    };

    const cityData = {
      labels: ['Jakarta','Bandung','Surabaya'],
      datasets: [{
        data: [40,30,20],
        backgroundColor: ['#17a2b8','#6f42c1','#fd7e14']
      }]
    };

    const countryData = {
      labels: ['Indonesia','Malaysia','Singapore'],
      datasets: [{
        data: [80,10,10],
        backgroundColor: ['#20c997','#e83e8c','#6c757d']
      }]
    };

    const weekData = {
      labels: ['Week 1','Week 2','Week 3','Week 4'],
      datasets: [{
        label: 'Scans',
        data: [30,50,80,60],
        fill:false,
        borderColor:'#007bff',
        tension:0.1
      }]
    };

    new Chart(document.getElementById('deviceChart'),{type:'doughnut',data:deviceData});
    new Chart(document.getElementById('cityChart'),{type:'bar',data:cityData});
    new Chart(document.getElementById('countryChart'),{type:'bar',data:countryData});
    new Chart(document.getElementById('weekChart'),{type:'line',data:weekData});
  </script>
</body>
</html>
