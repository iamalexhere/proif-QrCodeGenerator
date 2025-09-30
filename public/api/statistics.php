<?php
// Mengatur header agar outputnya adalah JSON
header('Content-Type: application/json');

// Memuat class Database
require_once __DIR__ . '/../../classes/Database.php';

// Ambil ID link dari parameter URL (contoh: statistics.php?link_id=1)
$linkId = isset($_GET['link_id']) ? (int)$_GET['link_id'] : 0;

if ($linkId <= 0) {
    echo json_encode(['error' => 'ID link tidak valid.']);
    exit;
}

try {
    // Hubungkan ke database
    $db = Database::getInstance()->getConnection();
    
    $stats = [];

    // 1. Total Scans
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clicks WHERE link_id = ?");
    $stmt->bind_param("i", $linkId);
    $stmt->execute();
    $stats['total_scans'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // 2. Data Perangkat (Devices)
    $stmt = $db->prepare("SELECT device_type, COUNT(*) as count FROM clicks WHERE link_id = ? GROUP BY device_type");
    $stmt->bind_param("i", $linkId);
    $stmt->execute();
    $stats['devices'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3. Data Per Kota (Top 5)
    $stmt = $db->prepare("SELECT city, COUNT(*) as count FROM clicks WHERE link_id = ? AND city != 'Unknown' GROUP BY city ORDER BY count DESC LIMIT 5");
    $stmt->bind_param("i", $linkId);
    $stmt->execute();
    $stats['cities'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 4. Data Per Negara (Top 5)
    $stmt = $db->prepare("SELECT country, COUNT(*) as count FROM clicks WHERE link_id = ? AND country != 'Unknown' GROUP BY country ORDER BY count DESC LIMIT 5");
    $stmt->bind_param("i", $linkId);
    $stmt->execute();
    $stats['countries'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 5. Data Scan per Hari (7 hari terakhir)
    $stmt = $db->prepare("SELECT DATE(click_time) as date, COUNT(*) as count FROM clicks WHERE link_id = ? AND click_time >= CURDATE() - INTERVAL 7 DAY GROUP BY DATE(click_time) ORDER BY date ASC");
    $stmt->bind_param("i", $linkId);
    $stmt->execute();
    $stats['scans_per_day'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Kirim data sebagai JSON
    echo json_encode($stats);

} catch (Exception $e) {
    // Kirim pesan error jika terjadi masalah
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data statistik: ' . $e->getMessage()]);
}