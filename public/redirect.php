<?php
/**
 * URL Redirect Handler with Advertisement Page
 * Handles redirection from short URLs with optional ad display
 */

require_once __DIR__ . '/../shorturl/UrlShortener.php';

// Get the short code from URL
$shortCode = '';
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $shortCode = trim($_GET['code']);
} else {
    // Try to get from REQUEST_URI for clean URLs
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $shortCode = end($pathParts);
}

if (empty($shortCode)) {
    header('Location: /');
    exit;
}

// Check rate limiting
$clientIp = Utils::getClientIp();
if (!RateLimit::check($clientIp)) {
    http_response_code(429);
    echo '<h1>Too Many Requests</h1><p>Please try again later.</p>';
    exit;
}

try {
    $urlShortener = new UrlShortener();
    $originalUrl = $urlShortener->recordClick($shortCode);
    
    if (!$originalUrl) {
        // Short code not found, redirect to homepage
        header('Location: /');
        exit;
    }
    
    // Check if we should show ads (you can add logic here to skip ads for certain conditions)
    $showAds = true;
    $adDisplayTime = (int) Config::get('AD_DISPLAY_TIME', 5);
    
    if (isset($_GET['skip_ads']) || !$showAds) {
        // Direct redirect without ads
        header('Location: ' . $originalUrl);
        exit;
    }
    
} catch (Exception $e) {
    Utils::logError("Redirect error: " . $e->getMessage());
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting...</title>
    <meta http-equiv="refresh" content="<?php echo $adDisplayTime; ?>;url=<?php echo htmlspecialchars($originalUrl); ?>">
    
    <!-- SEO meta tags -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="You are being redirected to your destination.">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000000;
            padding: 2rem;
        }
        
        .container {
            background: #ffffff;
            border: 4px solid #000000;
            padding: 3rem;
            text-align: left;
            max-width: 700px;
            width: 100%;
            box-shadow: 8px 8px 0px #000000;
        }
        
        .logo {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .logo img {
            max-height: 80px;
            width: auto;
            border: 2px solid #000000;
        }
        
        h1 {
            color: #000000;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 4px solid #000000;
            padding-bottom: 1rem;
        }
        
        .redirect-info {
            margin-bottom: 2rem;
            color: #000000;
            line-height: 1.4;
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .countdown {
            font-size: 4rem;
            font-weight: 900;
            color: #000000;
            margin: 2rem 0;
            text-align: center;
            border: 4px solid #000000;
            padding: 1rem;
            background: #ffffff;
            letter-spacing: 4px;
        }
        
        .ad-container {
            margin: 2rem 0;
            min-height: 280px;
            background: #ffffff;
            border: 4px solid #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            position: relative;
        }
        
        .ad-placeholder {
            color: #000000;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #ffffff;
            border: 3px solid #000000;
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #000000;
            transition: width 0.1s ease;
        }
        
        .skip-button {
            background: #000000;
            color: #ffffff;
            border: 3px solid #000000;
            padding: 1rem 2rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            transition: all 0.1s ease;
        }
        
        .skip-button:hover {
            background: #ffffff;
            color: #000000;
            box-shadow: 4px 4px 0px #000000;
            transform: translate(-2px, -2px);
        }
        
        .destination-url {
            background: #ffffff;
            padding: 1.5rem;
            border: 3px solid #000000;
            border-left: 8px solid #000000;
            margin: 2rem 0;
            word-break: break-all;
            font-weight: 700;
        }
        
        .destination-url strong {
            color: #000000;
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 2rem;
                box-shadow: 4px 4px 0px #000000;
            }
            
            h1 {
                font-size: 2rem;
                letter-spacing: 1px;
            }
            
            .countdown {
                font-size: 2.5rem;
                letter-spacing: 2px;
            }
            
            .skip-button {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (file_exists(__DIR__ . '/images/logoif.png')): ?>
        <div class="logo">
            <img src="/images/logoif.png" alt="Logo">
        </div>
        <?php endif; ?>
        
        <h1>🚀 Redirecting You...</h1>
        
        <div class="redirect-info">
            <p>You will be automatically redirected to your destination in:</p>
        </div>
        
        <div class="countdown" id="countdown"><?php echo $adDisplayTime; ?></div>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progress"></div>
        </div>
        
        <!-- Advertisement Container -->
        <div class="ad-container" id="ad-container">
            <div class="ad-placeholder">
                <h3>Placeholder</h3>
            </div>
        </div>
        
        <div class="destination-url">
            <strong>Destination:</strong> <?php echo htmlspecialchars($originalUrl); ?>
        </div>
        
        <a href="<?php echo htmlspecialchars($originalUrl); ?>" class="skip-button" id="skip-button">
            Skip & Continue →
        </a>
    </div>

    <script>
        (function() {
            const totalTime = <?php echo $adDisplayTime; ?>;
            let currentTime = totalTime;
            const countdownElement = document.getElementById('countdown');
            const progressElement = document.getElementById('progress');
            const skipButton = document.getElementById('skip-button');
            const destination = <?php echo json_encode($originalUrl); ?>;
            
            // Update progress bar
            function updateProgress() {
                const percentage = ((totalTime - currentTime) / totalTime) * 100;
                progressElement.style.width = percentage + '%';
            }
            
            // Countdown timer
            const timer = setInterval(function() {
                currentTime--;
                
                if (countdownElement) {
                    countdownElement.textContent = currentTime;
                }
                
                updateProgress();
                
                if (currentTime <= 0) {
                    clearInterval(timer);
                    window.location.href = destination;
                }
            }, 1000);
            
            // Handle skip button
            if (skipButton) {
                skipButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearInterval(timer);
                    window.location.href = destination;
                });
            }
            
            // Handle keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Press Space or Enter to skip
                if (e.code === 'Space' || e.code === 'Enter') {
                    e.preventDefault();
                    clearInterval(timer);
                    window.location.href = destination;
                }
            });
            
            // Initialize progress
            updateProgress();
            
            // Preload destination (optional optimization)
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = destination;
            document.head.appendChild(link);
        })();
    </script>
</body>
</html>