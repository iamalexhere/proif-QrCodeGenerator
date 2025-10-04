<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../classes/Analytics.php';

$linkId = isset($_GET['link_id']) ? (int)$_GET['link_id'] : 0;
if ($linkId <= 0) {
    echo json_encode(['error' => 'ID link tidak valid.']);
    exit;
}

try {
    $analytics = new Analytics();
    $stats = $analytics->getLinkStatistics($linkId);
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data: ' . $e->getMessage()]);
}