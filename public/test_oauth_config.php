<?php
/**
 * Google OAuth Configuration Test Script
 * 
 * This script helps diagnose Google OAuth configuration issues
 * Run this on your staging server to identify problems
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/Config.php';

echo "<h1>Google OAuth Configuration Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

echo "<div class='section'>";
echo "<h2>1. Environment Detection</h2>";

// Check environment
$environment = Config::getEnvironment();
echo "<p><strong>Current Environment:</strong> <span class='info'>{$environment}</span></p>";

// Check if .env file exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "<p class='success'>✓ .env file exists</p>";
    
    // Check if .env is readable
    if (is_readable($envFile)) {
        echo "<p class='success'>✓ .env file is readable</p>";
    } else {
        echo "<p class='error'>✗ .env file is not readable (check permissions)</p>";
    }
} else {
    echo "<p class='error'>✗ .env file does not exist</p>";
    echo "<p class='warning'>⚠ Create .env file from .env.example</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Google OAuth Configuration</h2>";

// Get OAuth configuration
$googleClientId = Config::getGoogleClientId();
$googleClientSecret = Config::getGoogleClientSecret();
$googleRedirectUri = Config::getGoogleRedirectUri();
$isOAuthEnabled = Config::isGoogleOAuthEnabled();

echo "<p><strong>OAuth Enabled:</strong> " . ($isOAuthEnabled ? "<span class='success'>✓ Yes</span>" : "<span class='error'>✗ No</span>") . "</p>";

// Check Client ID
if (!empty($googleClientId)) {
    echo "<p><strong>Client ID:</strong> <span class='success'>✓ Configured</span></p>";
    echo "<p class='info'>Client ID: " . substr($googleClientId, 0, 20) . "..." . substr($googleClientId, -20) . "</p>";
    
    // Validate Client ID format
    if (strpos($googleClientId, '.apps.googleusercontent.com') !== false) {
        echo "<p class='success'>✓ Client ID format looks correct</p>";
    } else {
        echo "<p class='warning'>⚠ Client ID format might be incorrect (should end with .apps.googleusercontent.com)</p>";
    }
} else {
    echo "<p><strong>Client ID:</strong> <span class='error'>✗ Not configured</span></p>";
}

// Check Client Secret
if (!empty($googleClientSecret)) {
    echo "<p><strong>Client Secret:</strong> <span class='success'>✓ Configured</span></p>";
    echo "<p class='info'>Client Secret: " . substr($googleClientSecret, 0, 10) . "..." . substr($googleClientSecret, -5) . "</p>";
} else {
    echo "<p><strong>Client Secret:</strong> <span class='error'>✗ Not configured</span></p>";
}

// Check Redirect URI
if (!empty($googleRedirectUri)) {
    echo "<p><strong>Redirect URI:</strong> <span class='success'>✓ Configured</span></p>";
    echo "<p class='info'>Redirect URI: <code>{$googleRedirectUri}</code></p>";
    
    // Validate Redirect URI
    if (filter_var($googleRedirectUri, FILTER_VALIDATE_URL)) {
        echo "<p class='success'>✓ Redirect URI is a valid URL</p>";
        
        // Check if it's HTTPS (required for production)
        if (strpos($googleRedirectUri, 'https://') === 0) {
            echo "<p class='success'>✓ Redirect URI uses HTTPS</p>";
        } else {
            echo "<p class='warning'>⚠ Redirect URI should use HTTPS for production</p>";
        }
        
        // Check if callback file exists
        $callbackPath = parse_url($googleRedirectUri, PHP_URL_PATH);
        $callbackFile = __DIR__ . '/public' . $callbackPath;
        
        // Try different possible callback file locations
        $possibleCallbacks = [
            __DIR__ . '/public/callback.php',
            __DIR__ . '/public/oauth-callback.php',
            __DIR__ . '/callback.php',
            __DIR__ . '/oauth-callback.php'
        ];
        
        $callbackExists = false;
        foreach ($possibleCallbacks as $file) {
            if (file_exists($file)) {
                echo "<p class='success'>✓ Callback file found: " . basename($file) . "</p>";
                $callbackExists = true;
                break;
            }
        }
        
        if (!$callbackExists) {
            echo "<p class='error'>✗ Callback file not found</p>";
            echo "<p class='warning'>⚠ Expected callback files: callback.php or oauth-callback.php</p>";
        }
        
    } else {
        echo "<p class='error'>✗ Redirect URI is not a valid URL</p>";
    }
} else {
    echo "<p><strong>Redirect URI:</strong> <span class='error'>✗ Not configured</span></p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Server Environment Check</h2>";

// Check PHP version
$phpVersion = PHP_VERSION;
echo "<p><strong>PHP Version:</strong> <span class='info'>{$phpVersion}</span></p>";

// Check cURL support
if (function_exists('curl_init')) {
    echo "<p class='success'>✓ cURL is available</p>";
    
    // Test cURL to Google
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/v2/auth');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result !== false && $httpCode == 200) {
        echo "<p class='success'>✓ Can connect to Google OAuth servers</p>";
    } else {
        echo "<p class='error'>✗ Cannot connect to Google OAuth servers</p>";
        if ($error) {
            echo "<p class='error'>cURL Error: {$error}</p>";
        }
        echo "<p class='error'>HTTP Code: {$httpCode}</p>";
    }
} else {
    echo "<p class='error'>✗ cURL is not available</p>";
}

// Check OpenSSL
if (extension_loaded('openssl')) {
    echo "<p class='success'>✓ OpenSSL extension is loaded</p>";
} else {
    echo "<p class='error'>✗ OpenSSL extension is not loaded</p>";
}

// Check JSON support
if (function_exists('json_decode')) {
    echo "<p class='success'>✓ JSON support is available</p>";
} else {
    echo "<p class='error'>✗ JSON support is not available</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Current Server Information</h2>";

echo "<p><strong>Server Name:</strong> <span class='info'>" . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "</span></p>";
echo "<p><strong>HTTP Host:</strong> <span class='info'>" . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</span></p>";
echo "<p><strong>Request URI:</strong> <span class='info'>" . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</span></p>";
echo "<p><strong>HTTPS:</strong> <span class='info'>" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Yes' : 'No') . "</span></p>";

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
              '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . 
              dirname($_SERVER['REQUEST_URI'] ?? '');

echo "<p><strong>Current Base URL:</strong> <span class='info'>{$currentUrl}</span></p>";

echo "</div>";

echo "<div class='section'>";
echo "<h2>5. Recommendations</h2>";

if (!$isOAuthEnabled) {
    echo "<div class='error'>";
    echo "<h3>❌ OAuth Not Configured</h3>";
    echo "<p>To fix Google OAuth issues:</p>";
    echo "<ol>";
    echo "<li>Create/update your <code>.env</code> file with proper Google OAuth credentials</li>";
    echo "<li>Get credentials from <a href='https://console.cloud.google.com/apis/credentials' target='_blank'>Google Cloud Console</a></li>";
    echo "<li>Set up OAuth 2.0 Client ID with correct redirect URI</li>";
    echo "<li>Update your <code>.env</code> file with:</li>";
    echo "</ol>";
    echo "<pre>";
    echo "GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com\n";
    echo "GOOGLE_CLIENT_SECRET=your-client-secret\n";
    echo "GOOGLE_REDIRECT_URI=https://yourdomain.com/oauth-callback.php";
    echo "</pre>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>✅ OAuth Configuration Looks Good</h3>";
    echo "<p>Your OAuth configuration appears to be set up correctly.</p>";
    echo "</div>";
}

// Check for common issues
if (!empty($googleRedirectUri) && strpos($googleRedirectUri, 'localhost') !== false) {
    echo "<div class='warning'>";
    echo "<h3>⚠ Development Configuration Detected</h3>";
    echo "<p>Your redirect URI contains 'localhost'. Make sure to:</p>";
    echo "<ul>";
    echo "<li>Update redirect URI for production environment</li>";
    echo "<li>Add production domain to Google OAuth settings</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>6. Test OAuth URL Generation</h2>";

if ($isOAuthEnabled) {
    $state = bin2hex(random_bytes(16));
    $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" .
               "client_id=" . urlencode($googleClientId) .
               "&redirect_uri=" . urlencode($googleRedirectUri) .
               "&response_type=code" .
               "&scope=email profile" .
               "&state=" . urlencode($state) .
               "&access_type=online";
    
    echo "<p class='success'>✓ OAuth URL can be generated</p>";
    echo "<p><strong>Test OAuth URL:</strong></p>";
    echo "<pre style='word-break: break-all;'>{$authUrl}</pre>";
    echo "<p><a href='{$authUrl}' target='_blank' class='info'>🔗 Test OAuth Login (opens in new tab)</a></p>";
} else {
    echo "<p class='error'>✗ Cannot generate OAuth URL - configuration incomplete</p>";
}

echo "</div>";

echo "<p><em>Generated at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
