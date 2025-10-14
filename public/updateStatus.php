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
        // === SOFT DELETE QR CODE ===
        // First verify the QR code belongs to the current user and is not already deleted
        $checkStmt = $db->prepare("SELECT id FROM links WHERE short_url = ? AND user_id = ? AND deleted_at IS NULL");
        $checkStmt->bind_param("si", $short_url, $currentUser['id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $response["message"] = "QR code not found or access denied";
        } else {
            // Soft delete the QR code by setting deleted_at timestamp
            $deleteStmt = $db->prepare("UPDATE links SET deleted_at = NOW() WHERE short_url = ? AND user_id = ? AND deleted_at IS NULL");
            $deleteStmt->bind_param("si", $short_url, $currentUser['id']);
            
            if ($deleteStmt->execute()) {
                if ($deleteStmt->affected_rows > 0) {
                    $response["success"] = true;
                    $response["message"] = "QR code deleted successfully";
                    
                    // NOTE: We do NOT decrement quota when deleting QR codes
                    // Quota represents total QR codes created per month, not active QR codes
                    // Once created, it counts toward monthly limit regardless of deletion
                } else {
                    $response["message"] = "QR code not found or already deleted";
                }
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
            // Update status with user verification (exclude soft-deleted records)
            $stmt = $db->prepare("UPDATE links SET status = ? WHERE short_url = ? AND user_id = ? AND deleted_at IS NULL");
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
