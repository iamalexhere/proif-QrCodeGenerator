<?php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Mengambil ID dari URL. Di dashboard, kita mengirim 'id'
$linkId = $_GET['id'] ?? 0;
$linkData = null;

// Mengambil data detail untuk link ini dari tabel 'links'
if ($linkId > 0) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, original_url, short_url, custom_url, logo_path, qr_color, qr_image, status, created_at FROM links WHERE id = ?");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        $linkData = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        // Jika terjadi error, biarkan $linkData null
    }
}

// Jika data link tidak ditemukan di database, redirect kembali ke dashboard
if (!$linkData) {
    header('Location: dashboardAll.php');
    exit;
}

// Menentukan halaman kembali
$returnPage = $_GET['return'] ?? 'dashboardAll.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View QR Code Details</title>
  <link rel="stylesheet" href="css/view_detail.css"> 
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header class="navbar">
    <div class="logo">QR Code Generator</div>
    <a href="<?php echo htmlspecialchars($returnPage); ?>" class="btn-back">&larr; Back to Dashboard</a>
  </header>

  <main class="edit-container">
    <section class="edit-left">
      <img src="data:image/png;base64,<?php echo base64_encode($linkData['qr_image']); ?>" alt="QR Code" class="qr-image">
      <div class="qr-details">
        <?php
          $baseDomain = 'http://qr.local/r/';
          $fullShortUrl = $baseDomain . $linkData['short_url'];
          ?>
          <p>
            <strong>Short Link:</strong>
            <a href="<?php echo htmlspecialchars($fullShortUrl); ?>" target="_blank">
              <?php echo htmlspecialchars($fullShortUrl); ?>
            </a>
          </p>
        <div class="destination-url">
            <strong>Destination URL:</strong>
            <p style="word-break: break-all; margin-top: 5px;"><?php echo htmlspecialchars($linkData['original_url']); ?></p>
        </div>
      </div>
    </section>

    <section class="edit-right">
      <h2>Statistics</h2>
      <div id="loading-stats" style="text-align: center; padding: 2rem;">
        <p>Loading statistics...</p>
      </div>
      <div class="stats-grid" id="stats-grid" style="display: none;">
        <div class="stat-card">
          <h3>Total Scans</h3>
          <p class="stat-number" id="total-scans">0</p>
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
          <h3>Scans per Day</h3>
          <canvas id="scansChart"></canvas> 
        </div>
      </div>
    </section>
  </main>

  <script>
    // --- SEMUA DATA DUMMY DIHAPUS DAN DIGANTI DENGAN KODE DINAMIS INI ---

    // Mengambil ID link dari PHP
    const linkId = <?php echo (int)$linkData['id']; ?>;

    // Fetch data statistik dari API
    fetch(`api/statistics.php?link_id=${linkId}`)
      .then(response => {
          if (!response.ok) {
              throw new Error('Network response was not ok');
          }
          return response.json();
      })
      .then(stats => {
        // Sembunyikan pesan "loading" dan tampilkan grid statistik
        document.getElementById('loading-stats').style.display = 'none';
        document.getElementById('stats-grid').style.display = 'grid';

        if (stats.error) {
            console.error('API Error:', stats.error);
            return;
        }

        // Update angka total scan
        document.getElementById('total-scans').textContent = stats.summary.total || 0;

        // Buat Chart untuk Perangkat (Devices)
        new Chart(document.getElementById('deviceChart'), {
          type: 'doughnut',
          data: {
            labels: stats.devices.map(d => d.device_type),
            datasets: [{
              data: stats.devices.map(d => d.count),
              backgroundColor: ['#007bff', '#28a745', '#ffc107']
            }]
          }
        });
        
        // Buat Chart untuk Kota
        new Chart(document.getElementById('cityChart'), {
          type: 'bar',
          data: {
            labels: stats.locations.cities.map(c => c.city),
            datasets: [{
              label: 'Scans',
              data: stats.locations.cities.map(c => c.count),
              backgroundColor: '#17a2b8'
            }]
          },
          options: { plugins: { legend: { display: false } } }
        });

        // Buat Chart untuk Negara
        new Chart(document.getElementById('countryChart'), {
          type: 'bar',
          data: {
            labels: stats.locations.countries.map(c => c.country),
            datasets: [{
              label: 'Scans',
              data: stats.locations.countries.map(c => c.count),
              backgroundColor: '#20c997'
            }]
          },
          options: { plugins: { legend: { display: false } } }
        });

        // Buat Chart untuk Scan per Hari
        new Chart(document.getElementById('scansChart'), {
          type: 'line',
          data: {
            labels: stats.temporal.map(s => s.date),
            datasets: [{
              label: 'Scans',
              data: stats.temporal.map(s => s.count),
              borderColor: '#007bff',
              tension: 0.1
            }]
          }
        });

      })
      .catch(error => {
        console.error('Fetch Error:', error);
        document.getElementById('loading-stats').innerHTML = '<p style="color: red;">Failed to load statistics</p>';
      });
  </script>
</body>
</html>