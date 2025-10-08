<?php
/**
 * Debug OAuth State Issues
 * Run this to check session state problems
 */

session_start();

echo "<h1>OAuth State Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

echo "<h2>Session Information</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";

echo "<h2>Current Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>GET Parameters</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>Session Configuration</h2>";
echo "<p><strong>Session Cookie Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Cookie Path:</strong> " . session_get_cookie_params()['path'] . "</p>";
echo "<p><strong>Session Cookie Domain:</strong> " . session_get_cookie_params()['domain'] . "</p>";
echo "<p><strong>Session Cookie Secure:</strong> " . (session_get_cookie_params()['secure'] ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Session Cookie HttpOnly:</strong> " . (session_get_cookie_params()['httponly'] ? 'Yes' : 'No') . "</p>";

echo "<h2>Server Information</h2>";
echo "<p><strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Yes' : 'No') . "</p>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";

// Test session write
$_SESSION['test_write'] = 'test_value_' . time();
echo "<h2>Session Write Test</h2>";
echo "<p class='success'>✓ Session write successful</p>";
echo "<p><strong>Test value:</strong> " . $_SESSION['test_write'] . "</p>";

// Generate new OAuth state for testing
$newState = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $newState;
echo "<h2>New OAuth State Generated</h2>";
echo "<p><strong>New State:</strong> <code>{$newState}</code></p>";
echo "<p class='info'>This state has been saved to session for testing</p>";

echo "<h2>Recommendations</h2>";

// Check for common session issues
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    echo "<div class='warning'>";
    echo "<p>⚠ <strong>HTTPS Issue:</strong> Sessions may not work properly without HTTPS</p>";
    echo "</div>";
}

if (session_get_cookie_params()['secure'] && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    echo "<div class='error'>";
    echo "<p>❌ <strong>Session Cookie Secure Flag:</strong> Secure cookies require HTTPS</p>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check if sessions persist between login.php and oauth-callback.php</li>";
echo "<li>Verify HTTPS is working properly on staging</li>";
echo "<li>Check session cookie settings</li>";
echo "</ol>";
echo "</div>";
?>
