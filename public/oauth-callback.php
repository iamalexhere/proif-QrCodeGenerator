<?php
/**
 * Google OAuth Callback Handler
 * Processes the OAuth response and logs in the user
 */

session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    header('Location: login.php?error=no_code');
    exit;
}

$code = $_GET['code'];

try {
    // Exchange code for user info using simple cURL (no library needed)
    $clientId = Config::get('GOOGLE_CLIENT_ID');
    $clientSecret = Config::get('GOOGLE_CLIENT_SECRET');
    $redirectUri = Config::get('GOOGLE_REDIRECT_URI');
    
    // Exchange authorization code for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenResult = json_decode($tokenResponse, true);
    
    if (!isset($tokenResult['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    $accessToken = $tokenResult['access_token'];
    
    // Get user info
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!isset($userInfo['id'])) {
        throw new Exception('Failed to get user info');
    }
    
    // Login user
    $auth = Auth::getInstance();
    $auth->loginWithGoogle($userInfo);
    
    // Redirect to original page or dashboard
    $redirectTo = $_SESSION['redirect_after_login'] ?? 'dashboardAll.php';
    unset($_SESSION['redirect_after_login']);
    
    header('Location: ' . $redirectTo);
    exit;
    
} catch (Exception $e) {
    error_log('OAuth callback error: ' . $e->getMessage());
    header('Location: login.php?error=auth_failed');
    exit;
}
