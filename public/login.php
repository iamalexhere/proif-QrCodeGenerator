<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';

// Start session
Auth::startSession();

// If already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    header('Location: dashboardAll.php');
    exit;
}

// Store redirect URL for after login
$redirectUrl = $_GET['redirect'] ?? 'dashboardAll.php';
$_SESSION['redirect_after_login'] = $redirectUrl;

// Generate CSRF state for OAuth security
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

// Get Google OAuth configuration
$googleClientId = Config::getGoogleClientId();
$googleRedirectUri = Config::getGoogleRedirectUri();
$isGoogleOAuthEnabled = Config::isGoogleOAuthEnabled();

// Check for error messages
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'auth_failed':
            $errorMessage = 'Authentication failed. Please try again.';
            break;
        case 'oauth_failed':
            $errorMessage = 'Google OAuth failed: ' . ($_GET['message'] ?? 'Unknown error');
            break;
        case 'oauth_not_configured':
            $errorMessage = 'Google OAuth is not properly configured.';
            break;
        case 'invalid_callback':
            $errorMessage = 'Invalid OAuth callback.';
            break;
        case 'invalid_state':
            $errorMessage = 'Invalid OAuth state. Please try again.';
            break;
        case 'unexpected_error':
            $errorMessage = 'An unexpected error occurred: ' . ($_GET['message'] ?? 'Unknown error');
            break;
        default:
            $errorMessage = 'An error occurred during login.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AAARO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" href="images/logo-aaaro.png" type="image/x-icon">
</head>
<body>
    <Header>
        <div class="logo-container">
            <img src="images/logo-aaaro.png" alt="AAARO Logo" class="logo-image">
            <div class="brand-text">
                <div class="brand-title">AAARO</div>
                <div class="brand-subtitle">Complexity, simplified</div>
            </div>
        </div>
    </Header>
    <div class="main">
        <div class="section-login">
            <div class="head-login">
                <h2>Login and start making</h2>
                <?php if (!empty($errorMessage)): ?>
                    <div class="error-message" style="background:#ffebee;color:#c62828;padding:10px;border-radius:5px;margin:10px 0;border:1px solid #ef5350;">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="features">
                <h3>What you get:</h3>
                <div class="feature-item">Generate 10 QR codes for month</div>
                <div class="feature-item">Download your own</div>
                <h3>For Newcomers Get 30 Day Analytics Trial </h3>
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
                    By logging in with an account, you agree to AAARO's <a href="terms.php">Terms and Conditions</a>.
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
            
            <?php if (!$isGoogleOAuthEnabled): ?>
                alert('Google OAuth is not properly configured. Please check your .env settings.');
                return;
            <?php endif; ?>
            
            const clientId = '<?php echo htmlspecialchars($googleClientId); ?>';
            const redirectUri = '<?php echo htmlspecialchars($googleRedirectUri); ?>';
            const state = '<?php echo htmlspecialchars($_SESSION['oauth_state']); ?>';
            
            if (!clientId || !redirectUri) {
                alert('Google OAuth configuration is incomplete.');
                return;
            }
            
            const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
                `client_id=${encodeURIComponent(clientId)}` +
                `&redirect_uri=${encodeURIComponent(redirectUri)}` +
                `&response_type=code` +
                `&scope=email profile` +
                `&state=${encodeURIComponent(state)}` +
                `&access_type=online`;
            
            window.location.href = authUrl;
        });
    </script>
</body>
</html>