<?php
/**
 * Logout Handler
 * 
 * Handles user logout by destroying the session and redirecting to login page
 */

require_once __DIR__ . '/../classes/Auth.php';

// Start session
Auth::startSession();

// Destroy session and redirect
session_destroy();
header('Location: login.php');
exit;
?>
