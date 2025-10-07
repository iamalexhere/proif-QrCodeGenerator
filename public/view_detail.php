<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Tangkap parameter kode QR dan halaman asal
$code = $_GET['code'] ?? '';
$returnPage = $_GET['return'] ?? 'dashboardAll.php';
$linkData = null;

// Mengambil data detail untuk link ini dari tabel 'links' berdasarkan short_url
if (!empty($code)) {
    try {
        $db = Database::getInstance()->getConnection();
        // mengambil kolom created_at
        $stmt = $db->prepare("SELECT id, original_url, short_url, custom_url, logo_path, qr_color, qr_image, status, created_at FROM links WHERE short_url = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $linkData = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        // Jika terjadi error, isi $linkData null
    }
}

// Jika data link tidak ditemukan di database, redirect kembali ke dashboard
if (!$linkData) {
    header('Location: dashboardAll.php');
    exit;
}

// Format created_at untuk digunakan di JavaScript (YYYY-MM-DD)
// Jika created_at ada, gunakan; fallback ke hari ini.
$linkCreatedAt = $linkData['created_at'] ? date('Y-m-d', strtotime($linkData['created_at'])) : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <!-- Total Scans -->
                <div class="stat-card">
                    <h3>Total Scans</h3>
                    <p class="stat-number" id="total-scans">0</p>
                </div>

                <!-- Devices -->
                <div class="stat-card">
                    <h3>Devices</h3>
                    <canvas id="deviceChart"></canvas>
                </div>

                <!-- By City -->
                <div class="stat-card">
                    <h3>By City</h3>
                    <canvas id="cityChart"></canvas>
                </div>

                <!-- By Country -->
                <div class="stat-card">
                    <h3>By Country</h3>
                    <canvas id="countryChart"></canvas>
                </div>

                <!-- Filter Controls - Full Width -->
                <div class="stat-card filter-card">
                    <h3>Filter Options</h3>
                    <div class="filter-controls-inner">
                        <div class="filter-group">
                            <label for="start-date">Start Date</label>
                            <input type="date" id="start-date">
                        </div>
                        <div class="filter-group">
                            <label for="end-date">End Date</label>
                            <input type="date" id="end-date">
                        </div>
                        <div class="filter-group">
                            <label for="quick-filter">Quick Filter</label>
                            <select id="quick-filter">
                                <option value="">Custom Range</option>
                                <option value="7">Last 7 Days</option>
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>
                        <div class="filter-buttons">
                            <button class="btn-filter" onclick="applyFilter()">Apply Filter</button>
                            <button class="btn-reset" onclick="resetFilter()">Reset</button>
                        </div>
                    </div>
                </div>

                <!-- View Toggle - Full Width -->
                <div class="stat-card view-card">
                    <h3>Time Range View</h3>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="day" onclick="changeView('day')">Daily</button>
                        <button class="view-btn" data-view="week" onclick="changeView('week')">Weekly</button>
                        <button class="view-btn" data-view="month" onclick="changeView('month')">Monthly</button>
                    </div>
                </div>

                <!-- Scans Over Time -->
                <div class="stat-card wide">
                    <h3>Scans Over Time</h3>
                    <canvas id="scansChart"></canvas>
                </div>
            </div>
        </section>
    </main>

    <script>
        const linkId = <?php echo (int)$linkData['id']; ?>;
        const linkCreatedAt = "<?php echo $linkCreatedAt; ?>";
        let currentView = 'day';
        let statsData = null;
        let scansChart = null;

        // Menetapkan batas minimum pada input tanggal
        function setDateLimits() {
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            
            if (!startDateInput || !endDateInput) return;
            
            // Set batas minimum ke tanggal pembuatan QR Code
            startDateInput.setAttribute('min', linkCreatedAt);
            endDateInput.setAttribute('min', linkCreatedAt);

            // Batasan: End Date tidak boleh lebih awal dari Start Date
            startDateInput.addEventListener('change', function() {
                endDateInput.setAttribute('min', this.value);
                // Jika tanggal mulai yang baru lebih besar dari tanggal akhir, perbarui tanggal akhir
                if (new Date(this.value) > new Date(endDateInput.value)) {
                    endDateInput.value = this.value;
                }
            });

            // Batasan: Start Date tidak boleh lebih awal dari End Date
            endDateInput.addEventListener('change', function() {
                // Jika tanggal akhir yang baru lebih kecil dari tanggal mulai, perbarui tanggal mulai
                if (new Date(this.value) < new Date(startDateInput.value)) {
                    startDateInput.value = this.value;
                }
            });
        }

        function setDefaultDates() {
            const today = new Date();
            const createdAt = new Date(linkCreatedAt);
            
            // Format tanggal dalam YYYY-MM-DD
            const todayStr = today.toISOString().split('T')[0];

            const endDateInput = document.getElementById('end-date');
            const startDateInput = document.getElementById('start-date');
            
            if (!endDateInput || !startDateInput) return;

            endDateInput.value = todayStr;
            
            // Default Start Date: 30 hari yang lalu atau linkCreatedAt
            let defaultStartDate = new Date();
            defaultStartDate.setDate(today.getDate() - 30);

            // Ambil yang paling baru antara 30 hari atau linkCreatedAt
            const finalStartDate = defaultStartDate > createdAt ? defaultStartDate : createdAt;
            
            startDateInput.valueAsDate = finalStartDate;

            // Perbarui batas min End Date
            endDateInput.setAttribute('min', startDateInput.value);
        }

        // Setup Quick Filter Handler
        function setupQuickFilter() {
            const quickFilter = document.getElementById('quick-filter');
            if (!quickFilter) return;
            
            quickFilter.addEventListener('change', function() {
                const days = parseInt(this.value);
                const createdAt = new Date(linkCreatedAt);
                
                if (days) {
                    const endDate = new Date();
                    const startDate = new Date();
                    startDate.setDate(endDate.getDate() - days);
                    
                    // tanggal mulai tidak lebih awal dari created_at
                    const finalStartDate = startDate > createdAt ? startDate : createdAt;
                    
                    document.getElementById('end-date').valueAsDate = endDate;
                    document.getElementById('start-date').valueAsDate = finalStartDate;
                    
                    // batas min End Date setelah Quick Filter
                    document.getElementById('end-date').setAttribute('min', document.getElementById('start-date').value);
                } else {
                    // Custom Range: mengembalikan ke batas created_at dan nilai default
                    document.getElementById('end-date').setAttribute('min', linkCreatedAt);
                    setDefaultDates();
                }
            });
        }

        // Untuk melakukan apply filter sesuai masukkan
        function applyFilter() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (startDate && endDate) {
                // Cek sekali lagi di sisi client
                if (new Date(startDate) > new Date(endDate)) {
                    alert('Start date must be before or equal to end date.');
                    return;
                }
                if (new Date(startDate) < new Date(linkCreatedAt)) {
                    alert('Start date cannot be before the QR code creation date (' + linkCreatedAt + ').');
                    return;
                }

                fetchStatistics(startDate, endDate);
            } else {
                alert('Please select both start and end dates.');
            }
        }

        // Reset filter
        function resetFilter() {
            const quickFilter = document.getElementById('quick-filter');
            if (quickFilter) quickFilter.value = '';
            
            setDateLimits();
            setDefaultDates();
            fetchStatistics();
        }
        
        // Change view (day/week/month)
        function changeView(view) {
            currentView = view;
            
            // Update active button
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            const activeBtn = document.querySelector(`[data-view="${view}"]`);
            if (activeBtn) activeBtn.classList.add('active');
            
            if (statsData && statsData.temporal) {
                updateTimelineChart(statsData.temporal);
            }
        }

        // Aggregasi data untuk tiap minggu 
        function aggregateByWeek(data) {
            const weeks = {};
            data.forEach(item => {
                const date = new Date(item.date);
                const weekStart = new Date(date);
                weekStart.setDate(date.getDate() - date.getDay()); 
                const weekKey = weekStart.toISOString().split('T')[0];
                
                if (!weeks[weekKey]) {
                    weeks[weekKey] = { date: weekKey, count: 0 };
                }
                weeks[weekKey].count += parseInt(item.count);
            });
            
            return Object.values(weeks).sort((a, b) => new Date(a.date) - new Date(b.date));
        }

        // Aggregasi data untuk tiap bulan 
        function aggregateByMonth(data) {
            const months = {};
            data.forEach(item => {
                const date = new Date(item.date);
                const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                
                if (!months[monthKey]) {
                    months[monthKey] = { date: monthKey, count: 0 };
                }
                months[monthKey].count += parseInt(item.count);
            });
            
            return Object.values(months).sort((a, b) => new Date(a.date) - new Date(b.date));
        }

        // Format date labels
        function formatDateLabel(dateStr, view) {
            const date = new Date(dateStr);
            if (view === 'week') {
                return `Week of ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
            } else if (view === 'month') {
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            } else {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }
        }

        // Update timeline chart
        function updateTimelineChart(temporalData) {
            if (!temporalData || temporalData.length === 0) return;
            
            let chartData = temporalData;
            
            if (currentView === 'week') {
                chartData = aggregateByWeek(temporalData);
            } else if (currentView === 'month') {
                chartData = aggregateByMonth(temporalData);
            }
            
            if (scansChart) {
                scansChart.data.labels = chartData.map(s => formatDateLabel(s.date, currentView));
                scansChart.data.datasets[0].data = chartData.map(s => s.count);
                scansChart.update();
            }
        }

        // Fetch statistics from API
        function fetchStatistics(startDate = null, endDate = null) {
            document.getElementById('loading-stats').style.display = 'block';
            document.getElementById('stats-grid').style.display = 'none';
            
            // Jika tanggal tidak diberikan, gunakan tanggal yang ada di input form
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            
            const finalStartDate = startDate || (startDateInput ? startDateInput.value : '');
            const finalEndDate = endDate || (endDateInput ? endDateInput.value : '');

            let url = `./api/statistics.php?link_id=${linkId}`;
            if (finalStartDate && finalEndDate) {
                url += `&start_date=${finalStartDate}&end_date=${finalEndDate}`;
            }
            
            console.log(`Fetching: ${url}`);
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(stats => {
                    document.getElementById('loading-stats').style.display = 'none';
                    document.getElementById('stats-grid').style.display = 'grid';

                    if (stats.error) {
                        console.error('API Error:', stats.error);
                        return;
                    }

                    statsData = stats;
                    destroyExistingCharts();
                    renderCharts(stats);
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    document.getElementById('loading-stats').innerHTML = '<p style="color: red;">Failed to load statistics</p>';
                });
        }
        
    
        function destroyExistingCharts() {
            const chartIds = ['deviceChart', 'cityChart', 'countryChart', 'scansChart'];
            chartIds.forEach(id => {
                const ctx = document.getElementById(id);
                if (ctx) {
                    const existingChart = Chart.getChart(ctx);
                    if (existingChart) {
                        existingChart.destroy();
                    }
                }
            });
            scansChart = null; 
        }

        function renderCharts(stats) {
            // Update total scans
            document.getElementById('total-scans').textContent = stats.summary.total || 0;

            // Device Chart
            const deviceCtx = document.getElementById('deviceChart');
            if (deviceCtx) {
                new Chart(deviceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: stats.devices.map(d => d.device_type),
                        datasets: [{
                            data: stats.devices.map(d => d.count),
                            backgroundColor: ['#667eea', '#764ba2', '#f093fb']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
            
            // City Chart
            const cityCtx = document.getElementById('cityChart');
            if (cityCtx) {
                new Chart(cityCtx, {
                    type: 'bar',
                    data: {
                        labels: stats.locations.cities.map(c => c.city),
                        datasets: [{
                            label: 'Scans',
                            data: stats.locations.cities.map(c => c.count),
                            backgroundColor: '#667eea'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Country Chart
            const countryCtx = document.getElementById('countryChart');
            if (countryCtx) {
                new Chart(countryCtx, {
                    type: 'bar',
                    data: {
                        labels: stats.locations.countries.map(c => c.country),
                        datasets: [{
                            label: 'Scans',
                            data: stats.locations.countries.map(c => c.count),
                            backgroundColor: '#764ba2'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Timeline Chart
            let chartData = stats.temporal || [];
            if (currentView === 'week') {
                chartData = aggregateByWeek(stats.temporal);
            } else if (currentView === 'month') {
                chartData = aggregateByMonth(stats.temporal);
            }

            const scansCtx = document.getElementById('scansChart');
            if (scansCtx) {
                scansChart = new Chart(scansCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(s => formatDateLabel(s.date, currentView)),
                        datasets: [{
                            label: 'Scans',
                            data: chartData.map(s => s.count),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: { 
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // setTimeout untuk memastikan semua elemen sudah di-render
            setTimeout(() => {
                setDateLimits(); // Tetapkan batas minimum
                setDefaultDates(); // Atur nilai default
                setupQuickFilter(); // Setup event handler untuk quick filter
                fetchStatistics(); // Muat statistik
            }, 100);
        });
    </script>
</body>
</html>