<?php
session_start();
require_once __DIR__ . '/../config/Config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboardAll.php');
    // header('Location: index.php');
    exit;
}

$redirectUrl = $_GET['redirect'] ?? 'dashboardAll.php';
$_SESSION['redirect_after_login'] = $redirectUrl;

// Google OAuth URL (will be set via JavaScript)
$googleClientId = Config::get('GOOGLE_CLIENT_ID', '');
$googleRedirectUri = Config::get('GOOGLE_REDIRECT_URI', 'http://qr.local/oauth-callback.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to QR Code Generator</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <Header>
        <div class="logo">🔗</div>
        <h1>QR Code Generator</h1>
    </Header>
    <div class="main">
        <div class="section-login">
            <div class="head-login">
                <h2>Login and start making</h2>
            </div>
            
            <div class="features">
                <h3>What you get:</h3>
                <div class="feature-item">Generate 10 QR codes for month</div>
                <div class="feature-item">Download your own</div>
                <h3>For Newcomer Get 7 Day Trial Feature Premium </h3>
                <div class="feature-item">You can edit your own QR URL</div>
                <div class="feature-item">Track scans with analytics</div>
                <div class="feature-item">Download </div>
                <div class="feature-item">Custom QR (logos & colors)</div>
            </div>
            
            <div class="login-google">
                <a id="google-login-btn" href="#">
                    <span>
                        <!-- logo google -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none">
                            <path fill="#2A5BD7" d="M15.078 15.625c1.758-1.64 2.54-4.375 2.07-6.992h-6.992v2.89h3.985c-.157.938-.703 1.72-1.485 2.227z"></path>
                            <path fill="#34A853" d="M3.516 13.32a7.5 7.5 0 0 0 11.562 2.305l-2.422-1.875c-2.07 1.367-5.508.86-6.68-2.344z"></path>
                            <path fill="#FBBC02" d="M5.975 11.406a4.45 4.45 0 0 1 0-2.851L3.515 6.64c-.9 1.797-1.173 4.336 0 6.68z"></path>
                            <path fill="#EA4335" d="M5.977 8.555c.859-2.696 4.53-4.258 6.992-1.954l2.148-2.109C12.07 1.562 6.133 1.68 3.516 6.641z"></path>
                        </svg>
                        Continue with google
                    </span>
                </a>
                <div class="divider"></div>
            </div>

            <!-- <div class="form-login">
                <form action="">
                    <div>
                        <div>Email</div>
                        <input type="email">
                    </div>

                    <div>
                        <div>Password</div>
                        <input type="password">
                    </div>

                    <div>
                        <a href="">Forgot password?</a>
                    </div>
                    <button type="submit">Log in</button>
                </form>
            </div> -->
            <div class="term-and-condition">
                <span>
                    By logging in with an account, you agree to AARO's <a href="">Terms of Service</a>, <a href="">Privacy Policy</a> and <a href="">Acceptable Use Policy</a>.
                </span>
            </div>
            
        </div>
    </div>
    <div class="side-panel">
        
    </div>
      <script>
        // Google OAuth login
        document.getElementById('google-login-btn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const clientId = '<?php echo htmlspecialchars($googleClientId); ?>';
            const redirectUri = '<?php echo htmlspecialchars($googleRedirectUri); ?>';
            
            if (!clientId) {
                alert('Google OAuth is not configured. Please set GOOGLE_CLIENT_ID in .env file');
                return;
            }
            
            const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
                `client_id=${encodeURIComponent(clientId)}` +
                `&redirect_uri=${encodeURIComponent(redirectUri)}` +
                `&response_type=code` +
                `&scope=email profile` +
                `&access_type=online`;
            
            window.location.href = authUrl;
        });
    </script>
</body>
</html>