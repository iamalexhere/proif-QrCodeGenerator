<?php
/**
 * Debug OAuth Callback Handler
 * 
 * This file helps debug OAuth callback issues
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';

// Start session
Auth::startSession();

echo "<h1>OAuth Callback Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

echo "<h2>1. Request Parameters</h2>";
echo "<p><strong>GET Parameters:</strong></p>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>2. Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if we have an authorization code
if (!isset($_GET['code'])) {
    echo "<div class='error'>";
    echo "<h3>❌ No Authorization Code</h3>";
    if (isset($_GET['error'])) {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($_GET['error']) . "</p>";
        echo "<p><strong>Description:</strong> " . htmlspecialchars($_GET['error_description'] ?? 'No description') . "</p>";
    } else {
        echo "<p>No authorization code or error provided</p>";
    }
    echo "</div>";
    exit;
}

echo "<div class='success'>";
echo "<h3>✅ Authorization Code Received</h3>";
echo "<p><strong>Code:</strong> " . substr($_GET['code'], 0, 20) . "...</p>";
echo "</div>";

// Check OAuth configuration
echo "<h2>3. OAuth Configuration Check</h2>";
if (!Config::isGoogleOAuthEnabled()) {
    echo "<div class='error'>";
    echo "<p>❌ Google OAuth is not properly configured</p>";
    echo "</div>";
    exit;
} else {
    echo "<div class='success'>";
    echo "<p>✅ Google OAuth configuration is valid</p>";
    echo "</div>";
}

// Check state parameter
echo "<h2>4. State Parameter Validation</h2>";
if (isset($_GET['state']) && isset($_SESSION['oauth_state'])) {
    if ($_GET['state'] === $_SESSION['oauth_state']) {
        echo "<div class='success'>";
        echo "<p>✅ OAuth state matches</p>";
        echo "<p><strong>Expected:</strong> " . $_SESSION['oauth_state'] . "</p>";
        echo "<p><strong>Received:</strong> " . $_GET['state'] . "</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ OAuth state mismatch</p>";
        echo "<p><strong>Expected:</strong> " . $_SESSION['oauth_state'] . "</p>";
        echo "<p><strong>Received:</strong> " . $_GET['state'] . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='warning'>";
    echo "<p>⚠ State parameter missing</p>";
    echo "<p><strong>GET state:</strong> " . (isset($_GET['state']) ? $_GET['state'] : 'not set') . "</p>";
    echo "<p><strong>Session state:</strong> " . (isset($_SESSION['oauth_state']) ? $_SESSION['oauth_state'] : 'not set') . "</p>";
    echo "</div>";
}

// Test Google OAuth login
echo "<h2>5. Testing Google OAuth Login</h2>";

try {
    $authCode = $_GET['code'];
    
    // Create Auth instance and test login
    $auth = Auth::getInstance();
    
    echo "<div class='info'>";
    echo "<p>🔄 Attempting Google OAuth login...</p>";
    echo "</div>";
    
    $result = $auth->loginWithGoogle($authCode);
    
    if ($result['success']) {
        echo "<div class='success'>";
        echo "<h3>✅ OAuth Login Successful!</h3>";
        echo "<p><strong>User ID:</strong> " . $result['user']['id'] . "</p>";
        echo "<p><strong>Email:</strong> " . $result['user']['email'] . "</p>";
        echo "<p><strong>Name:</strong> " . $result['user']['name'] . "</p>";
        echo "<p><strong>Plan:</strong> " . $result['user']['plan'] . "</p>";
        echo "</div>";
        
        echo "<h3>Session After Login</h3>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<p><a href='dashboardAll.php'>Go to Dashboard</a></p>";
        
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ OAuth Login Failed</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>";
        echo "</div>";
        
        // Let's debug each step
        echo "<h3>Debug Steps:</h3>";
        
        // Test token exchange
        echo "<h4>Step 1: Token Exchange</h4>";
        try {
            $reflection = new ReflectionClass($auth);
            $method = $reflection->getMethod('exchangeCodeForToken');
            $method->setAccessible(true);
            $tokenData = $method->invoke($auth, $authCode);
            
            if ($tokenData) {
                echo "<div class='success'>";
                echo "<p>✅ Token exchange successful</p>";
                echo "<p><strong>Access Token:</strong> " . substr($tokenData['access_token'], 0, 20) . "...</p>";
                echo "</div>";
                
                // Test user info retrieval
                echo "<h4>Step 2: User Info Retrieval</h4>";
                $userInfoMethod = $reflection->getMethod('getGoogleUserInfo');
                $userInfoMethod->setAccessible(true);
                $userInfo = $userInfoMethod->invoke($auth, $tokenData['access_token']);
                
                if ($userInfo) {
                    echo "<div class='success'>";
                    echo "<p>✅ User info retrieval successful</p>";
                    echo "<pre>";
                    print_r($userInfo);
                    echo "</pre>";
                    echo "</div>";
                    
                    // Test user creation/update
                    echo "<h4>Step 3: User Creation/Update</h4>";
                    $createUserMethod = $reflection->getMethod('createOrUpdateGoogleUser');
                    $createUserMethod->setAccessible(true);
                    $user = $createUserMethod->invoke($auth, $userInfo);
                    
                    if ($user) {
                        echo "<div class='success'>";
                        echo "<p>✅ User creation/update successful</p>";
                        echo "<pre>";
                        print_r($user);
                        echo "</pre>";
                        echo "</div>";
                    } else {
                        echo "<div class='error'>";
                        echo "<p>❌ User creation/update failed</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error'>";
                    echo "<p>❌ User info retrieval failed</p>";
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>";
                echo "<p>❌ Token exchange failed</p>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<p>❌ Debug error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Unexpected Error</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
