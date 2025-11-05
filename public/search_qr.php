<?php
/***********************************************************
 * AJAX SEARCH ENDPOINT - Real-time QR Code Search
 ***********************************************************/
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../classes/FaviconService.php';

// Check authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Connect to database
$db = Database::getInstance()->getConnection();

// Build query
$where_clause = " WHERE user_id = ? AND deleted_at IS NULL";
$params = [$currentUser['id']];
$param_types = 'i';

if (!empty($search_query)) {
    $where_clause .= " AND (custom_url LIKE ? OR original_url LIKE ? OR short_url LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

// Query data
$base_query = "
    SELECT
        l.*,
        COALESCE(stats.scan_count, 0) as scan_count,
        COALESCE(stats.top_device, 'N/A') as top_device,
        COALESCE(stats.top_city, 'N/A') as top_city
    FROM links l
    LEFT JOIN (
        SELECT
            link_id,
            COUNT(*) as scan_count,
            (SELECT user_agent FROM clicks c2 WHERE c2.link_id = c.link_id
             GROUP BY user_agent ORDER BY COUNT(*) DESC LIMIT 1) as top_device,
            (SELECT city FROM clicks c3 WHERE c3.link_id = c.link_id AND city IS NOT NULL
             GROUP BY city ORDER BY COUNT(*) DESC LIMIT 1) as top_city
        FROM clicks c
        GROUP BY link_id
    ) stats ON l.id = stats.link_id
";

$stmt = $db->prepare($base_query . $where_clause . " ORDER BY l.created_at DESC LIMIT 50");
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$links = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Process top_device
        if ($row['top_device'] && $row['top_device'] !== 'N/A') {
            if (stripos($row['top_device'], 'iPhone') !== false || stripos($row['top_device'], 'iPad') !== false) {
                $row['top_device'] = 'iOS';
            } elseif (stripos($row['top_device'], 'Android') !== false) {
                $row['top_device'] = 'Android';
            } elseif (stripos($row['top_device'], 'Windows') !== false) {
                $row['top_device'] = 'Windows';
            } elseif (stripos($row['top_device'], 'Mac') !== false) {
                $row['top_device'] = 'Mac';
            } else {
                $row['top_device'] = 'Other';
            }
        }

        // Generate display name
        $display_name = $row['custom_url'];
        if (empty($display_name) && !empty($row['original_url'])) {
            $host = parse_url($row['original_url'], PHP_URL_HOST);
            if ($host) {
                $host = preg_replace('/^www\./', '', strtolower($host));

                if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
                    $display_name = 'YOUTUBE';
                } elseif (strpos($host, 'facebook.com') !== false) {
                    $display_name = 'FACEBOOK';
                } elseif (strpos($host, 'instagram.com') !== false) {
                    $display_name = 'INSTAGRAM';
                } elseif (strpos($host, 'docs.google.com') !== false) {
                    $display_name = 'DOCS';
                } elseif (strpos($host, 'drive.google.com') !== false) {
                    $display_name = 'DRIVE';
                } elseif (strpos($host, 'linkedin.com') !== false) {
                    $display_name = 'LINKEDIN';
                } else {
                    $parts = explode('.', $host);
                    $display_name = strtoupper($parts[0]);
                }
            } else {
                $display_name = 'UNTITLED';
            }
        }
        $row['display_name'] = $display_name;

        // Get favicon info
        $websiteIcon = FaviconService::getEnhancedIcon($row['original_url']);
        $row['favicon_url'] = $websiteIcon['url'];
        $row['favicon_category'] = $websiteIcon['category'];
        $row['domain_name'] = FaviconService::getDomainName($row['original_url']);

        // Format QR image
        if (!empty($row['qr_image'])) {
            $row['qr_image_base64'] = base64_encode($row['qr_image']);
        }

        // Generate full short URL
        $row['full_short_url'] = Config::getShortUrlBase() . '/' . $row['short_url'];

        // Check analytics access
        $row['has_analytics'] = Auth::hasAnalyticsAccess($currentUser);

        $links[] = $row;
    }
}

$stmt->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'query' => $search_query,
    'total' => count($links),
    'links' => $links
]);
?>