<?php
// updateStatus.php - Enhanced with authentication and delete functionality
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';

// Require authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

// Ambil koneksi
$db = Database::getInstance()->getConnection();

// Ambil data dari request
$short_url = isset($_POST['short_url']) ? trim($_POST['short_url']) : null;
$action = isset($_POST['action']) ? trim($_POST['action']) : 'update_status';
$status = isset($_POST['status']) ? trim($_POST['status']) : null;

$response = ["success" => false, "message" => ""];

if (!$short_url) {
    $response["message"] = "Short URL is required";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    if ($action === 'delete') {
        // === DELETE QR CODE ===
        // First verify the QR code belongs to the current user
        $checkStmt = $db->prepare("SELECT id FROM links WHERE short_url = ? AND user_id = ?");
        $checkStmt->bind_param("si", $short_url, $currentUser['id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $response["message"] = "QR code not found or access denied";
        } else {
            // Delete the QR code
            $deleteStmt = $db->prepare("DELETE FROM links WHERE short_url = ? AND user_id = ?");
            $deleteStmt->bind_param("si", $short_url, $currentUser['id']);
            
            if ($deleteStmt->execute()) {
                $response["success"] = true;
                $response["message"] = "QR code deleted successfully";
                
                // Also decrement user's quota since QR code is deleted
                Auth::decrementQRCodeUsage($currentUser['id']);
            } else {
                $response["message"] = "Failed to delete QR code";
            }
            $deleteStmt->close();
        }
        $checkStmt->close();
        
    } else {
        // === UPDATE STATUS ===
        if (!$status || !in_array($status, ['active', 'paused'])) {
            $response["message"] = "Valid status is required (active or paused)";
        } else {
            // Update status with user verification
            $stmt = $db->prepare("UPDATE links SET status = ? WHERE short_url = ? AND user_id = ?");
            $stmt->bind_param("ssi", $status, $short_url, $currentUser['id']);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response["success"] = true;
                    $response["message"] = "Status updated successfully";
                } else {
                    $response["message"] = "QR code not found or access denied";
                }
            } else {
                $response["message"] = "Failed to update status";
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
    error_log("updateStatus.php error: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
