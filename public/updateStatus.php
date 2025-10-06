<?php
// updateStatus.php
require_once __DIR__ . '/../classes/Database.php';

// Ambil koneksi
$db = Database::getInstance()->getConnection();

// Ambil data dari request
$short_url = isset($_POST['short_url']) ? trim($_POST['short_url']) : null;
$status    = isset($_POST['status']) ? trim($_POST['status']) : null;

$response = ["success" => false];

if ($short_url && $status) {
    // Update status di database
    $stmt = $db->prepare("UPDATE links SET status = ? WHERE short_url = ?");
    $stmt->bind_param("ss", $status, $short_url);

    if ($stmt->execute()) {
        $response["success"] = true;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
