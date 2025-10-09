<?php
/**
 * Test Session Configuration for HTTPS
 */

require_once __DIR__ . '/../classes/Auth.php';

// Start session with new HTTPS configuration
Auth::startSession();

echo "<h1>Session HTTPS Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

echo "<h2>HTTPS Detection</h2>";

// Check HTTPS detection
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
          || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
          || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

echo "<p><strong>HTTPS Detected:</strong> " . ($isHttps ? "<span class='success'>✓ Yes</span>" : "<span class='error'>✗ No</span>") . "</p>";

echo "<h2>Server Variables</h2>";
echo "<p><strong>HTTPS:</strong> " . ($_SERVER['HTTPS'] ?? 'not set') . "</p>";
echo "<p><strong>HTTP_X_FORWARDED_PROTO:</strong> " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "</p>";
echo "<p><strong>HTTP_X_FORWARDED_SSL:</strong> " . ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'not set') . "</p>";

echo "<h2>Session Cookie Configuration</h2>";
$cookieParams = session_get_cookie_params();
echo "<p><strong>Secure:</strong> " . ($cookieParams['secure'] ? "<span class='success'>✓ Yes</span>" : "<span class='error'>✗ No</span>") . "</p>";
echo "<p><strong>HttpOnly:</strong> " . ($cookieParams['httponly'] ? "<span class='success'>✓ Yes</span>" : "<span class='error'>✗ No</span>") . "</p>";
echo "<p><strong>SameSite:</strong> " . ($cookieParams['samesite'] ?? 'not set') . "</p>";
echo "<p><strong>Path:</strong> " . $cookieParams['path'] . "</p>";
echo "<p><strong>Domain:</strong> " . ($cookieParams['domain'] ?: 'auto-detect') . "</p>";

echo "<h2>Session Test</h2>";

// Test session persistence
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    echo "<p class='info'>Session counter initialized: 1</p>";
} else {
    $_SESSION['test_counter']++;
    echo "<p class='success'>✓ Session persisting! Counter: " . $_SESSION['test_counter'] . "</p>";
}

// Test OAuth state generation
$oauthState = bin2hex(random_bytes(16));
$_SESSION['test_oauth_state'] = $oauthState;
echo "<p><strong>Test OAuth State:</strong> <code>{$oauthState}</code></p>";
echo "<p class='info'>State saved to session for testing</p>";

echo "<h2>Current Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><em>Refresh this page to test session persistence</em></p>";
?>
