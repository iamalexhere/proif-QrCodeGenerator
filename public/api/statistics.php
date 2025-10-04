<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Statistics.php';

if (!isset($_GET['link_id']) || !is_numeric($_GET['link_id'])) {
    echo json_encode(['error' => 'Invalid or missing link_id']);
    exit;
}

$linkId = (int) $_GET['link_id'];

try {
    $stats = new Statistics();
    $data = $stats->getLinkStatistics($linkId);
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}