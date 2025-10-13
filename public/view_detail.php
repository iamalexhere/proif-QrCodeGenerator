<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Tangkap parameter kode QR dan halaman asal
$code = $_GET['code'] ?? '';                   // short code QR
$returnPage = $_GET['return'] ?? 'dashboardAll.php'; // default balik ke dashboardAll
$linkData = null;

// Mengambil data detail untuk link ini dari tabel 'links' berdasarkan short_url
if (!empty($code)) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, original_url, short_url, custom_url, logo_path, qr_color, qr_image, status, created_at FROM links WHERE short_url = ?");
        $stmt->bind_param("s", $code);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View QR Code Details</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/view_detail.css"> 
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- Navbar dengan tombol back dinamis -->
  <header class="navbar">
    <div class="navbar-left">
      <div class="brand-text">
        <div class="brand-title">AAARO</div>
        <div class="brand-subtitle">Complexity, simplified</div>
      </div>
    </div>
    <div class="navbar-right">
      <a href="<?php echo htmlspecialchars($returnPage); ?>" class="btn-back">&larr; Back to Dashboard</a>
      <a href="logout.php" style="color: #dc3545; text-decoration: none; font-size: 25px;" title="Logout">
        🚪
      </a>
    </div>
  </header>

  <main class="edit-container">
    <section class="edit-left">
      <?php if (!empty($linkData['qr_image'])): ?>
        <img src="data:image/png;base64,<?php echo base64_encode($linkData['qr_image']); ?>" alt="QR Code" class="qr-image">
      <?php else: ?>
        <img src="images/base.png" alt="QR Code" class="qr-image">
      <?php endif; ?>
      <div class="qr-details">
        <?php
          $fullShortUrl = Config::getShortUrlBase() . '/' . $linkData['short_url'];
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
    console.log(`Fetching: api/statistics.php?link_id=${linkId}`);
      fetch(`./api/statistics.php?link_id=${linkId}`)
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
