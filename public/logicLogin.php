<?php
session_start();

// 1. Get email and password from the form
$email = $_POST['email'];
$password = $_POST['password'];

// 2. Check credentials against your database (this is a placeholder)
if ($email === 'user@example.com' && $password === '1234') {
    // 3. If correct, set a session variable
    $_SESSION['is_logged_in'] = true;
    $_SESSION['user_email'] = $email;

    // 4. Redirect to the generator page
    header('Location: index.php');
    exit();
} else {
    // 5. If incorrect, redirect back to the login page with an error
    header('Location: login.php?error=1');
    exit();
}
?>