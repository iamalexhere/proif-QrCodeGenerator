<?php
/**
 * Debug QR Code Generation
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

// Start session and check auth
Auth::startSession();

echo "<h1>QR Code Generation Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

echo "<h2>1. Authentication Check</h2>";
if (Auth::isLoggedIn()) {
    echo "<div class='success'>";
    echo "<p>✅ User is logged in</p>";
    
    $currentUser = Auth::getCurrentUser();
    if ($currentUser) {
        echo "<p><strong>User ID:</strong> " . $currentUser['id'] . "</p>";
        echo "<p><strong>Email:</strong> " . $currentUser['email'] . "</p>";
        echo "<p><strong>Plan:</strong> " . $currentUser['plan'] . "</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Could not get current user data</p>";
        echo "</div>";
        exit;
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ User is not logged in</p>";
    echo "</div>";
    exit;
}

echo "<h2>2. Database Connection Test</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<div class='success'>";
    echo "<p>✅ Database connection successful</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    exit;
}

echo "<h2>3. Required Tables Check</h2>";

// Check user_quotas table
$result = $db->query("SHOW TABLES LIKE 'user_quotas'");
if ($result && $result->num_rows > 0) {
    echo "<div class='success'>";
    echo "<p>✅ user_quotas table exists</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ user_quotas table missing</p>";
    echo "</div>";
}

// Check links table
$result = $db->query("SHOW TABLES LIKE 'links'");
if ($result && $result->num_rows > 0) {
    echo "<div class='success'>";
    echo "<p>✅ links table exists</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ links table missing</p>";
    echo "</div>";
}

echo "<h2>4. Quota Check</h2>";
try {
    $monthYear = date('Y-m');
    $limit = Config::getPlanLimit($currentUser['plan'], 'qr_codes_per_month', 10);
    
    echo "<p><strong>Current Month:</strong> " . $monthYear . "</p>";
    echo "<p><strong>Plan Limit:</strong> " . $limit . "</p>";
    
    // Check current quota
    $stmt = $db->prepare("
        SELECT qr_codes_created 
        FROM user_quotas 
        WHERE user_id = ? AND month_year = ?
    ");
    $stmt->bind_param("is", $currentUser['id'], $monthYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $quota = $result->fetch_assoc();
    $stmt->close();
    
    $used = $quota ? $quota['qr_codes_created'] : 0;
    
    echo "<p><strong>Used:</strong> " . $used . "</p>";
    echo "<p><strong>Remaining:</strong> " . ($limit - $used) . "</p>";
    
    if ($used >= $limit) {
        echo "<div class='error'>";
        echo "<p>❌ Quota exceeded</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ Quota available</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ Quota check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<h2>5. Composer Dependencies Check</h2>";

// Check if vendor/autoload.php exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "<div class='success'>";
    echo "<p>✅ Composer autoload exists</p>";
    echo "</div>";
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Check QR Code library
    if (class_exists('Endroid\QrCode\QrCode')) {
        echo "<div class='success'>";
        echo "<p>✅ QR Code library loaded</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ QR Code library not found</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ Composer autoload not found</p>";
    echo "<p>Run: <code>composer install</code></p>";
    echo "</div>";
}

echo "<h2>6. UrlShortener Class Check</h2>";
if (file_exists(__DIR__ . '/../classes/UrlShortener.php')) {
    echo "<div class='success'>";
    echo "<p>✅ UrlShortener.php exists</p>";
    echo "</div>";
    
    require_once __DIR__ . '/../classes/UrlShortener.php';
    
    if (class_exists('UrlShortener')) {
        echo "<div class='success'>";
        echo "<p>✅ UrlShortener class loaded</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ UrlShortener class not found</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ UrlShortener.php not found</p>";
    echo "</div>";
}

echo "<h2>7. Test QR Generation</h2>";

if (isset($_POST['test_url']) && !empty($_POST['test_url'])) {
    echo "<div class='info'>";
    echo "<p>🔄 Testing QR generation for: " . htmlspecialchars($_POST['test_url']) . "</p>";
    echo "</div>";
    
    try {
        // Test URL shortener
        $urlShortener = new UrlShortener();
        $shortUrl = $urlShortener->createShortUrl($_POST['test_url'], $currentUser['id']);
        
        if ($shortUrl) {
            echo "<div class='success'>";
            echo "<p>✅ Short URL created: " . htmlspecialchars($shortUrl) . "</p>";
            echo "</div>";
            
            // Test QR code generation
            use Endroid\QrCode\QrCode;
            use Endroid\QrCode\Writer\PngWriter;
            
            $qrCode = QrCode::create($shortUrl)
                ->setSize(300)
                ->setMargin(10);
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            echo "<div class='success'>";
            echo "<p>✅ QR Code generated successfully</p>";
            echo "<p><strong>Size:</strong> " . strlen($result->getString()) . " bytes</p>";
            echo "</div>";
            
            // Display QR code
            echo "<h3>Generated QR Code:</h3>";
            echo '<img src="data:image/png;base64,' . base64_encode($result->getString()) . '" alt="QR Code">';
            
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Short URL creation failed</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<p>❌ QR generation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<h4>Stack Trace:</h4>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
}

echo "<h2>8. Test Form</h2>";
echo '<form method="POST">';
echo '<p><label>Test URL: <input type="url" name="test_url" value="https://example.com" required></label></p>';
echo '<p><button type="submit">Test QR Generation</button></p>';
echo '</form>';

echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
