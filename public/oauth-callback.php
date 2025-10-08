<?php
/**
 * Google OAuth Callback Handler
 * 
 * This file handles the callback from Google OAuth after user authorization.
 * It processes the authorization code and completes the login flow.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';

// Start session
Auth::startSession();

// Check if we have an authorization code
if (!isset($_GET['code'])) {
    // No code provided - check for error
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $errorDescription = $_GET['error_description'] ?? 'Unknown error occurred';
        
        error_log('Google OAuth error: ' . $error . ' - ' . $errorDescription);
        
        // Redirect to login with error message
        header('Location: login.php?error=oauth_failed&message=' . urlencode($errorDescription));
        exit;
    }
    
    // No code and no error - invalid callback
    header('Location: login.php?error=invalid_callback');
    exit;
}

// Check if Google OAuth is properly configured
if (!Config::isGoogleOAuthEnabled()) {
    error_log('Google OAuth callback called but OAuth is not properly configured');
    header('Location: login.php?error=oauth_not_configured');
    exit;
}

try {
    // Get the authorization code
    $authCode = $_GET['code'];
    
    // Verify state parameter if it was set (CSRF protection)
    if (isset($_GET['state']) && isset($_SESSION['oauth_state'])) {
        if ($_GET['state'] !== $_SESSION['oauth_state']) {
            error_log('OAuth state mismatch - possible CSRF attack');
            header('Location: login.php?error=invalid_state');
            exit;
        }
        // Clear the state
        unset($_SESSION['oauth_state']);
    }
    
    // Attempt to login with Google
    $auth = Auth::getInstance();
    $result = $auth->loginWithGoogle($authCode);
    
    if ($result['success']) {
        // Login successful
        $user = $result['user'];
        
        // Log successful login
        error_log('Successful Google OAuth login for user: ' . $user['email']);
        
        // Determine redirect URL
        $redirectUrl = 'dashboardAll.php';
        
        // Check if there was a redirect URL stored in session
        if (isset($_SESSION['redirect_after_login'])) {
            $redirectUrl = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
        }
        
        // Ensure redirect URL is safe (prevent open redirect attacks)
        $redirectUrl = sanitizeRedirectUrl($redirectUrl);
        
        // Redirect to dashboard or intended page
        header('Location: ' . $redirectUrl);
        exit;
        
    } else {
        // Login failed
        $error = $result['error'] ?? 'Unknown authentication error';
        error_log('Google OAuth login failed: ' . $error);
        
        // Redirect to login with error
        header('Location: login.php?error=auth_failed&message=' . urlencode($error));
        exit;
    }
    
} catch (Exception $e) {
    // Unexpected error occurred
    error_log('Exception in Google OAuth callback: ' . $e->getMessage());
    header('Location: login.php?error=unexpected_error&message=' . urlencode('An unexpected error occurred during authentication'));
    exit;
}

/**
 * Sanitize redirect URL to prevent open redirect attacks
 * @param string $url
 * @return string
 */
function sanitizeRedirectUrl($url) {
    // Default safe redirect
    $defaultRedirect = 'dashboardAll.php';
    
    // If URL is empty or not a string, use default
    if (empty($url) || !is_string($url)) {
        return $defaultRedirect;
    }
    
    // Parse the URL
    $parsedUrl = parse_url($url);
    
    // If parsing failed, use default
    if ($parsedUrl === false) {
        return $defaultRedirect;
    }
    
    // Check if it's an absolute URL with different host (potential open redirect)
    if (isset($parsedUrl['host'])) {
        // Get current host
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        
        // If hosts don't match, use default redirect
        if ($parsedUrl['host'] !== $currentHost) {
            return $defaultRedirect;
        }
    }
    
    // Check for dangerous schemes
    if (isset($parsedUrl['scheme'])) {
        $allowedSchemes = ['http', 'https'];
        if (!in_array(strtolower($parsedUrl['scheme']), $allowedSchemes)) {
            return $defaultRedirect;
        }
    }
    
    // If it's a relative URL, ensure it doesn't start with //
    if (strpos($url, '//') === 0) {
        return $defaultRedirect;
    }
    
    // List of allowed pages (whitelist approach for extra security)
    $allowedPages = [
        'dashboardAll.php',
        'dashboardActive.php',
        'dashboardPause.php',
        'index.php',
        'payment.php',
        'view_detail.php'
    ];
    
    // Extract just the filename from the path
    $path = $parsedUrl['path'] ?? $url;
    $filename = basename($path);
    
    // If it's in our whitelist, allow it
    if (in_array($filename, $allowedPages)) {
        return $url;
    }
    
    // Otherwise, use default
    return $defaultRedirect;
}
?>
