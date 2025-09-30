<?php
// Memuat class dan konfigurasi
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Ambil ID dari URL
$linkId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$linkData = null;

if ($linkId > 0) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM links WHERE id = ?");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $linkData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $error = "Gagal mengambil data link: " . $e->getMessage();
    }
}

// Redirect jika data tidak ditemukan
if (!$linkData) {
    header('Location: dashboardAll.php');
    exit;
}

$returnPage = $_GET['return'] ?? 'dashboardAll.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit QR Code</title>
  <link rel="stylesheet" href="css/edit.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header class="navbar">
    <div class="logo">QR Code Generator</div>
    <a href="<?php echo htmlspecialchars($returnPage); ?>" class="btn-back">&larr; Back to Dashboard</a>
  </header>

  <main class="edit-container">
    <section class="edit-left">
      <img src="images/base.png" alt="QR Code" class="qr-image">
      <div class="qr-details">
        <p><strong>Short Link:</strong> <?php echo htmlspecialchars($linkData['short_url']); ?></p>
        <form class="edit-form" method="post" action="save_edit.php">
          <input type="hidden" name="id" value="<?php echo $linkData['id']; ?>">
          <label for="url">Destination URL:</label>
          <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($linkData['original_url']); ?>">
          <button type="submit">Save Changes</button>
        </form>
      </div>
    </section>

    <section class="edit-right">
      <h2>Statistics</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Scans</h3>
          <p class="stat-number" id="total-scans">...</p>
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
    // Kode JavaScript yang sebelumnya sudah benar, tinggal disalin ke sini.
    // Kode ini akan memanggil api/statistics.php dan mengisi grafiknya.
    
    function createChart(canvasId, type, labels, data, chartLabel) {
        // ... (fungsi createChart tetap sama)
    }

    const linkId = <?php echo $linkData['id']; ?>;

    if (linkId) {
        fetch(`api/statistics.php?link_id=${linkId}`)
            .then(response => response.json())
            .then(stats => {
                if (stats.error) {
                    alert(stats.error);
                    return;
                }
                
                document.getElementById('total-scans').textContent = stats.total_scans;

                const deviceLabels = stats.devices.map(d => d.device_type);
                const deviceData = stats.devices.map(d => d.count);
                createChart('deviceChart', 'doughnut', deviceLabels, deviceData, 'Perangkat');

                const cityLabels = stats.cities.map(c => c.city);
                const cityData = stats.cities.map(c => c.count);
                createChart('cityChart', 'bar', cityLabels, cityData, 'Jumlah Scan');

                const countryLabels = stats.countries.map(c => c.country);
                const countryData = stats.countries.map(c => c.count);
                createChart('countryChart', 'bar', countryLabels, countryData, 'Jumlah Scan');
                
                const scanLabels = stats.scans_per_day.map(s => s.date);
                const scanData = stats.scans_per_day.map(s => s.count);
                createChart('scansChart', 'line', scanLabels, scanData, 'Jumlah Scan');
            });
    }
</script>
</body>
</html>