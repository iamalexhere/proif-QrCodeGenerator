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