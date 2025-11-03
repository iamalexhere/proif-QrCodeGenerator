// Global variables
let currentView = 'day';
let statsData = null;
let scansChart = null;

// Change view (day/week/month)
function changeView(view) {
    currentView = view;

    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.querySelector(`[data-view="${view}"]`);
    if (activeBtn) activeBtn.classList.add('active');

    if (statsData && statsData.temporal) {
        updateTimelineChart(statsData.temporal);
    }
}

function aggregateByWeek(data) {
    const weeks = {};
    data.forEach(item => {
        const date = new Date(item.date);
        const weekStart = new Date(date);
        weekStart.setDate(date.getDate() - date.getDay());
        const weekKey = weekStart.toISOString().split('T')[0];

        if (!weeks[weekKey]) weeks[weekKey] = { date: weekKey, count: 0 };
        weeks[weekKey].count += parseInt(item.count);
    });
    return Object.values(weeks).sort((a, b) => new Date(a.date) - new Date(b.date));
}

function aggregateByMonth(data) {
    const months = {};
    data.forEach(item => {
        const date = new Date(item.date);
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        if (!months[key]) months[key] = { date: key, count: 0 };
        months[key].count += parseInt(item.count);
    });
    return Object.values(months).sort((a, b) => new Date(a.date) - new Date(b.date));
}

function formatDateLabel(dateStr, view) {
    const date = new Date(dateStr);
    if (view === 'week') return `Week of ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    if (view === 'month') return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function updateTimelineChart(temporalData) {
    if (!temporalData || temporalData.length === 0) return;

    let chartData = temporalData;
    if (currentView === 'week') chartData = aggregateByWeek(temporalData);
    else if (currentView === 'month') chartData = aggregateByMonth(temporalData);

    if (scansChart) {
        scansChart.data.labels = chartData.map(s => formatDateLabel(s.date, currentView));
        scansChart.data.datasets[0].data = chartData.map(s => s.count);
        scansChart.update();
    }
}

function fetchStatistics() {
    document.getElementById('loading-stats').style.display = 'block';
    document.getElementById('stats-grid').style.display = 'none';

    fetch(`./api/statistics.php?link_id=${linkId}`)
        .then(res => res.json())
        .then(stats => {
            document.getElementById('loading-stats').style.display = 'none';
            document.getElementById('stats-grid').style.display = 'grid';
            statsData = stats;
            destroyExistingCharts();
            renderCharts(stats);
        })
        .catch(() => {
            document.getElementById('loading-stats').innerHTML = '<p style="color:red;">Failed to load statistics</p>';
        });
}

function destroyExistingCharts() {
    ['deviceChart', 'cityChart', 'countryChart', 'scansChart'].forEach(id => {
        const chart = Chart.getChart(id);
        if (chart) chart.destroy();
    });
    scansChart = null;
}

function renderCharts(stats) {
    document.getElementById('total-scans').textContent = stats.summary.total || 0;

    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: stats.devices.map(d => d.device_type),
            datasets: [{ data: stats.devices.map(d => d.count), backgroundColor: ['#667eea', '#764ba2', '#f093fb'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('cityChart'), {
        type: 'bar',
        data: {
            labels: stats.locations.cities.map(c => c.city),
            datasets: [{ label: 'Scans', data: stats.locations.cities.map(c => c.count), backgroundColor: '#667eea' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('countryChart'), {
        type: 'bar',
        data: {
            labels: stats.locations.countries.map(c => c.country),
            datasets: [{ label: 'Scans', data: stats.locations.countries.map(c => c.count), backgroundColor: '#764ba2' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    const chartData = stats.temporal || [];
    const scansCtx = document.getElementById('scansChart');
    scansChart = new Chart(scansCtx, {
        type: 'line',
        data: {
            labels: chartData.map(s => formatDateLabel(s.date, currentView)),
            datasets: [{
                label: 'Scans',
                data: chartData.map(s => s.count),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102,126,234,0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', fetchStatistics);

// ==================== FUNGSI DOWNLOAD - SEMUA PERIODE SEKALIGUS ====================

// Helper function untuk format tanggal dan waktu Indonesia (24 jam, tanpa detik)
function formatIndonesianDateTime() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Helper function untuk mendapatkan data temporal untuk semua periode
function getAllPeriodsData() {
    if (!statsData || !statsData.temporal) return { daily: [], weekly: [], monthly: [] };

    const daily = statsData.temporal.map(item => ({
        date: formatDateLabel(item.date, 'day'),
        count: item.count
    }));

    const weekly = aggregateByWeek(statsData.temporal).map(item => ({
        date: formatDateLabel(item.date, 'week'),
        count: item.count
    }));

    const monthly = aggregateByMonth(statsData.temporal).map(item => ({
        date: formatDateLabel(item.date, 'month'),
        count: item.count
    }));

    return { daily, weekly, monthly };
}

// Download sebagai CSV - Semua periode
function downloadCSV() {
    if (!statsData) {
        alert('No data available to download');
        return;
    }

    const periods = getAllPeriodsData();
    let csvContent = "data:text/csv;charset=utf-8,";

    // Header
    csvContent += "AAARO QR Code Statistics Report\n\n";
    csvContent += `Generated: ${formatIndonesianDateTime()}\n`;
    csvContent += `Link ID: ${linkId}\n\n`;

    // Total Scans
    csvContent += "Total Scans," + (statsData.summary?.total || 0) + "\n\n";

    // Devices
    csvContent += "Device Statistics\n";
    csvContent += "Device,Scans\n";
    if (statsData.devices && statsData.devices.length > 0) {
        statsData.devices.forEach(device => {
            csvContent += `${device.device_type},${device.count}\n`;
        });
    }
    csvContent += "\n";

    // Cities
    csvContent += "Top Cities\n";
    csvContent += "City,Scans\n";
    if (statsData.locations?.cities && statsData.locations.cities.length > 0) {
        statsData.locations.cities.forEach(city => {
            csvContent += `${city.city || 'Unknown'},${city.count}\n`;
        });
    }
    csvContent += "\n";

    // Countries
    csvContent += "Top Countries\n";
    csvContent += "Country,Scans\n";
    if (statsData.locations?.countries && statsData.locations.countries.length > 0) {
        statsData.locations.countries.forEach(country => {
            csvContent += `${country.country || 'Unknown'},${country.count}\n`;
        });
    }
    csvContent += "\n";

    // Daily Scans
    csvContent += "Scans Over Time - Daily View\n";
    csvContent += "Date,Scans\n";
    periods.daily.forEach(item => {
        csvContent += `${item.date},${item.count}\n`;
    });
    csvContent += "\n";

    // Weekly Scans
    csvContent += "Scans Over Time - Weekly View\n";
    csvContent += "Week,Scans\n";
    periods.weekly.forEach(item => {
        csvContent += `${item.date},${item.count}\n`;
    });
    csvContent += "\n";

    // Monthly Scans
    csvContent += "Scans Over Time - Monthly View\n";
    csvContent += "Month,Scans\n";
    periods.monthly.forEach(item => {
        csvContent += `${item.date},${item.count}\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `qr_stats_${linkId}_${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Download sebagai Excel - Semua periode
function downloadExcel() {
    if (!statsData) {
        alert('No data available to download');
        return;
    }

    const periods = getAllPeriodsData();
    let htmlContent = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
            <x:Name>QR Statistics</x:Name>
            <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>
            </x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
            <style>
                table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #52b788; color: white; font-weight: bold; }
                .header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
                .section { margin-top: 20px; font-weight: bold; font-size: 14px; background-color: #e8f5e9; padding: 8px; }
            </style>
        </head>
        <body>
            <div class="header">AAARO QR Code Statistics Report</div>
            <p>Generated: ${formatIndonesianDateTime()}</p>
            <p>Link ID: ${linkId}</p>
            
            <div class="section">Summary</div>
            <table>
                <tr><th>Metric</th><th>Value</th></tr>
                <tr><td>Total Scans</td><td>${statsData.summary?.total || 0}</td></tr>
            </table>
            
            <div class="section">Device Statistics</div>
            <table>
                <tr><th>Device</th><th>Scans</th></tr>
                ${statsData.devices && statsData.devices.length > 0
            ? statsData.devices.map(device =>
                `<tr><td>${device.device_type}</td><td>${device.count}</td></tr>`
            ).join('')
            : '<tr><td colspan="2">No data</td></tr>'}
            </table>
            
            <div class="section">Top Cities</div>
            <table>
                <tr><th>City</th><th>Scans</th></tr>
                ${statsData.locations?.cities && statsData.locations.cities.length > 0
            ? statsData.locations.cities.map(city =>
                `<tr><td>${city.city || 'Unknown'}</td><td>${city.count}</td></tr>`
            ).join('')
            : '<tr><td colspan="2">No data</td></tr>'}
            </table>
            
            <div class="section">Top Countries</div>
            <table>
                <tr><th>Country</th><th>Scans</th></tr>
                ${statsData.locations?.countries && statsData.locations.countries.length > 0
            ? statsData.locations.countries.map(country =>
                `<tr><td>${country.country || 'Unknown'}</td><td>${country.count}</td></tr>`
            ).join('')
            : '<tr><td colspan="2">No data</td></tr>'}
            </table>
            
            <div class="section">Scans Over Time - Daily View</div>
            <table>
                <tr><th>Date</th><th>Scans</th></tr>
                ${periods.daily.map(item =>
                `<tr><td>${item.date}</td><td>${item.count}</td></tr>`
            ).join('')}
            </table>
            
            <div class="section">Scans Over Time - Weekly View</div>
            <table>
                <tr><th>Week</th><th>Scans</th></tr>
                ${periods.weekly.map(item =>
                `<tr><td>${item.date}</td><td>${item.count}</td></tr>`
            ).join('')}
            </table>
            
            <div class="section">Scans Over Time - Monthly View</div>
            <table>
                <tr><th>Month</th><th>Scans</th></tr>
                ${periods.monthly.map(item =>
                `<tr><td>${item.date}</td><td>${item.count}</td></tr>`
            ).join('')}
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `qr_stats_${linkId}_${Date.now()}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Download sebagai PDF - Semua periode
async function downloadPDF() {
    if (!statsData) {
        alert('No data available to download');
        return;
    }

    // Show loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'pdf-loading';
    loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); z-index: 9999; text-align: center;';
    loadingDiv.innerHTML = '<p style="color: #52b788; font-size: 1.2rem; margin: 0;">Generating PDF...</p>';
    document.body.appendChild(loadingDiv);

    try {
        if (typeof window.jspdf === 'undefined') {
            throw new Error('jsPDF library not loaded');
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const periods = getAllPeriodsData();
        let yPos = 20;

        // Load and add logo
        const logo = new Image();
        logo.src = './images/logo-aaaro.png';

        await new Promise((resolve, reject) => {
            logo.onload = resolve;
            logo.onerror = reject;
        });

        // Add logo (adjust size and position as needed)
        doc.addImage(logo, 'PNG', 20, yPos - 5, 15, 15);

        // Header text next to logo
        doc.setFontSize(20);
        doc.setTextColor(82, 183, 136);
        doc.text('AAARO QR Code Statistics', 38, yPos + 5);

        yPos += 10;
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text(`Generated: ${formatIndonesianDateTime()}`, 20, yPos);
        doc.text(`Link ID: ${linkId}`, 20, yPos + 5);

        yPos += 15;

        // Total Scans
        doc.setFontSize(14);
        doc.setTextColor(0);
        doc.text('Summary', 20, yPos);
        yPos += 7;

        doc.autoTable({
            startY: yPos,
            head: [['Metric', 'Value']],
            body: [['Total Scans', statsData.summary?.total || 0]],
            theme: 'grid',
            headStyles: { fillColor: [82, 183, 136] },
            margin: { left: 20 }
        });

        yPos = doc.lastAutoTable.finalY + 10;

        // Device Statistics
        doc.setFontSize(14);
        doc.text('Device Statistics', 20, yPos);
        yPos += 7;

        const deviceData = statsData.devices && statsData.devices.length > 0
            ? statsData.devices.map(device => [device.device_type, device.count])
            : [['No data', '0']];

        doc.autoTable({
            startY: yPos,
            head: [['Device', 'Scans']],
            body: deviceData,
            theme: 'grid',
            headStyles: { fillColor: [82, 183, 136] },
            margin: { left: 20 }
        });

        yPos = doc.lastAutoTable.finalY + 10;

        if (yPos > 250) {
            doc.addPage();
            yPos = 20;
        }

        // Top Cities
        doc.setFontSize(14);
        doc.text('Top Cities', 20, yPos);
        yPos += 7;

        const cityData = statsData.locations?.cities && statsData.locations.cities.length > 0
            ? statsData.locations.cities.map(city => [city.city || 'Unknown', city.count])
            : [['No data', '0']];

        doc.autoTable({
            startY: yPos,
            head: [['City', 'Scans']],
            body: cityData,
            theme: 'grid',
            headStyles: { fillColor: [82, 183, 136] },
            margin: { left: 20 }
        });

        yPos = doc.lastAutoTable.finalY + 10;

        if (yPos > 250) {
            doc.addPage();
            yPos = 20;
        }

        // Top Countries
        doc.setFontSize(14);
        doc.text('Top Countries', 20, yPos);
        yPos += 7;

        const countryData = statsData.locations?.countries && statsData.locations.countries.length > 0
            ? statsData.locations.countries.map(country => [country.country || 'Unknown', country.count])
            : [['No data', '0']];

        doc.autoTable({
            startY: yPos,
            head: [['Country', 'Scans']],
            body: countryData,
            theme: 'grid',
            headStyles: { fillColor: [82, 183, 136] },
            margin: { left: 20 }
        });

        yPos = doc.lastAutoTable.finalY + 10;

        if (yPos > 250) {
            doc.addPage();
            yPos = 20;
        }

        // ========== DAILY SCANS ==========
        doc.setFontSize(14);
        doc.setTextColor(82, 183, 136);
        doc.text('Scans Over Time - Daily View', 20, yPos);
        doc.setTextColor(0);
        yPos += 7;

        const dailyData = periods.daily.map(item => [item.date, item.count]);

        doc.autoTable({
            startY: yPos,
            head: [['Date', 'Scans']],
            body: dailyData.length > 0 ? dailyData : [['No data', '0']],
            theme: 'grid',
            headStyles: { fillColor: [82, 183, 136] },
            margin: { left: 20 }
        });

        yPos = doc.lastAutoTable.finalY + 10;

        if (yPos > 250) {
            doc.addPage();
            yPos = 20;
        }

        // ========== WEEKLY SCANS ==========
        doc.setFontSize(14);
        doc.setTextColor(82, 183, 136);
        doc.text('Scans Over Time - Weekly View', 20, yPos);
        doc.setTextColor(0);
        yPos += 7;

        const weeklyData = periods.weekly.map(item => [item.date, item.count]);

        doc.autoTable({
            startY: yPos,
            head: [['Week', 'Scans']],
            body: weeklyData.length > 0 ? weeklyData : [['No data', '0']],
            theme: 'grid',
            headStyles: { fillColor: [102, 126, 234] },
            margin: { left: 20 }
        });

        yPos = doc.lastAutoTable.finalY + 10;

        if (yPos > 250) {
            doc.addPage();
            yPos = 20;
        }

        // ========== MONTHLY SCANS ==========
        doc.setFontSize(14);
        doc.setTextColor(82, 183, 136);
        doc.text('Scans Over Time - Monthly View', 20, yPos);
        doc.setTextColor(0);
        yPos += 7;

        const monthlyData = periods.monthly.map(item => [item.date, item.count]);

        doc.autoTable({
            startY: yPos,
            head: [['Month', 'Scans']],
            body: monthlyData.length > 0 ? monthlyData : [['No data', '0']],
            theme: 'grid',
            headStyles: { fillColor: [118, 75, 162] },
            margin: { left: 20 }
        });

        // Save PDF
        doc.save(`qr_stats_${linkId}_${Date.now()}.pdf`);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please make sure jsPDF library is loaded.');
    } finally {
        const loadingDiv = document.getElementById('pdf-loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }
}