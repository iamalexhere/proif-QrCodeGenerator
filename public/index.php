<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';

// Check if user is logged in
$isLoggedIn = Auth::isLoggedIn();
$currentUser = null;
$quotaInfo = null;

if ($isLoggedIn) {
    $currentUser = Auth::getCurrentUser();
    $quotaInfo = Auth::canCreateQRCode($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AAARO - Link Shortener and QR Code Generator</title>
    <meta name="title" content="Complexity, simplified">
    <meta name="description" content="We are AAARO, the team that simplifies digital interactions to create instant, effortless connections">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo-aaaro.png" type="image/x-icon">
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="navbar-left">
                <img src='images/logo-aaaro.png' alt="AAARO Logo">
                <div class="brand-text">
                    <div class="brand-title">AAARO</div>
                    <div class="brand-subtitle">Complexity, simplified</div>
                </div>
            </div>
            
            <div class="navbar-right">
                <?php if ($isLoggedIn): ?>
                    <span style="color:#666; font-size: 14px; margin-right: 15px;">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <a href="dashboardAll.php" class="btn-pro">Dashboard</a>
                    <a href="logout.php" style="color: #dc3545; text-decoration: none; font-size: 12px;" title="Logout">
                        <img class="img_logout" src="images/logout_icon.png" alt="logout_icon">
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-pro">Login to Create QR Codes</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <section>
        <div class="container">
            <div class="content">
                <div class="inputsection">
                    <form id="qrForm" method="post" enctype="multipart/form-data">
                        <div>
                            <label class="url-input" for="url-input">Website URL</label>
                            <input type="url" name="url-input" id="url-input" class="form-control" placeholder="https://www.your-website.com" required>
                        </div>

                        <p>Add logo on image:</p>
                        <div class="logo-options">
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="instagram.webp">
                                <img src="images/instagram.webp" alt="Instagram">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="tiktok.png">
                                <img src="images/tiktok.png" alt="TikTok">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="line.png">
                                <img src="images/line.png" alt="Line">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="spotify.webp">
                                <img src="images/spotify.webp" alt="Spotify">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="youtube.png">
                                <img src="images/youtube.png" alt="YouTube">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="twitter.png">
                                <img src="images/twitter.png" alt="Twitter">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="facebook.png">
                                <img src="images/facebook.png" alt="Facebook">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="snapchat.png">
                                <img src="images/snapchat.png" alt="Snapchat">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="LinkedIn.png">
                                <img src="images/LinkedIn.png" alt="LinkedIn">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="whatsApp.png">
                                <img src="images/whatsApp.png" alt="WhatsApp">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="gmail.png">
                                <img src="images/gmail.png" alt="Gmail">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="telegram.png">
                                <img src="images/telegram.png" alt="Telegram">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="discord.png">
                                <img src="images/discord.png" alt="Discord">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="bitcoin.png">
                                <img src="images/bitcoin.png" alt="Bitcoin">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="gopay.png">
                                <img src="images/gopay.png" alt="Gopay">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="ovo.webp">
                                <img src="images/ovo.webp" alt="OVO">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="dana.png">
                                <img src="images/dana.png" alt="Dana">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="wifi.png">
                                <img src="images/wifi.png" alt="Wifi">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="drive.png">
                                <img src="images/drive.png" alt="Google Drive">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="tokopedia.png">
                                <img src="images/tokopedia.png" alt="Tokopedia">
                            </label>
                             <label class="logo-option">
                                <input type="radio" name="default-logo" value="shopee.png">
                                <img src="images/shopee.png" alt="Shopee">
                            </label>
                        </div>
                        
                        <button type="button" id="reset-logo" class="btn" style="background-color: #6c757d;">Reset logo</button>

                        <div style="padding-top: 15px;">
                            <label class="url-input" for="custom-logo">Upload your own logo:</label>
                            <input type="file" name="custom-logo" id="custom-logo" class="form-control" accept="image/png, image/jpeg">
                        </div>

                        <div style="padding-top: 15px;">
                            <label class="url-input" for="qr_color">QR Code color:</label>
                            <input type="color" name="qr_color" id="qr_color" value="#000000">
                        </div>
                        
                        <?php if ($isLoggedIn): ?>
                            <!-- Quota Display -->
                            <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; margin: 15px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span><strong>Monthly Quota:</strong></span>
                                    <span><?php echo $quotaInfo['used']; ?> / <?php echo $quotaInfo['limit']; ?> QR Codes</span>
                                </div>
                                <div style="background: #e9ecef; border-radius: 10px; height: 8px; overflow: hidden;">
                                    <div style="background: <?php echo $quotaInfo['used'] >= $quotaInfo['limit'] ? '#dc3545' : '#28a745'; ?>; height: 100%; width: <?php echo ($quotaInfo['used'] / $quotaInfo['limit']) * 100; ?>%;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 12px; color: #666;">
                                    <span style="display: flex; align-items: center;">
                                        <span style="width: 8px; height: 8px; background: #28a745; border-radius: 50%; margin-right: 4px;"></span>
                                        Available
                                    </span>
                                    <span style="display: flex; align-items: center;">
                                        <span style="width: 8px; height: 8px; background: #dc3545; border-radius: 50%; margin-right: 4px;"></span>
                                        Limit Reached
                                    </span>
                                </div>
                                <?php if (!$quotaInfo['canCreate']): ?>
                                    <p style="color: #dc3545; margin-top: 10px; font-size: 14px;">⚠️ Monthly limit reached. <a href="payment.php">Upgrade your plan</a> to create more QR codes.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-submit"> 
                                <button type="submit" class="btn" <?php echo !$quotaInfo['canCreate'] ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''; ?>>
                                    <?php echo $quotaInfo['canCreate'] ? 'Generate QR Code' : 'Quota Exceeded'; ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Login Required Message -->
                            <div style="padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; margin: 15px 0; text-align: center;">
                                <h4 style="color: #856404; margin-bottom: 10px;">🔐 Login Required</h4>
                                <p style="color: #856404; margin-bottom: 15px;">Please log in with your Google account to create QR codes.</p>
                                <a href="login.php" class="btn" style="background: #007bff; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;">
                                    Login with Google
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="outputsection">
                    <h3>Output QR Code</h3>
                    <div id="qr-output-container">
                        <!-- Empty state - shown initially -->
                        <div id="empty-state" style="
                            width: 300px; 
                            height: 300px; 
                            border: 2px dashed #ccc; 
                            border-radius: 12px; 
                            display: flex; 
                            flex-direction: column; 
                            align-items: center; 
                            justify-content: center; 
                            background: #f9f9f9;
                            color: #666;
                            text-align: center;
                            margin: 0 auto;
                        ">
                            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📱</div>
                            <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No QR Code Generated</div>
                            <div style="font-size: 14px; opacity: 0.7;">Enter a URL and click "Generate QR Code"</div>
                        </div>
                        
                        <!-- QR Code result - hidden initially -->
                        <div id="qr-result" style="display: none; text-align: center;">
                            <img id="qrImage" style="max-width: 300px; border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div class="link-container">
                        <div id="short-link-container" style="display: none;">
                            Short Link: <a href="" target="_blank" id="short-link"></a>
                        </div>
                        <div id="download-links-container">
                            <a id="download-png" class="btn disabled" style="margin-right: 10px; opacity: 0.5; cursor: not-allowed; pointer-events: none;" title="Generate a QR code first to enable downloads">
                                <div>Download PNG</div>
                            </a>
                            <a id="download-svg" class="btn disabled" style="margin-right: 10px; background-color: #28a745; opacity: 0.5; cursor: not-allowed; pointer-events: none;" title="Generate a QR code first to enable downloads">
                                <div>Download SVG</div>
                            </a>
                            <a id="download-pdf" class="btn disabled" style="background-color: #dc3545; opacity: 0.5; cursor: not-allowed; pointer-events: none;" title="Generate a QR code first to enable downloads">
                                <div>Download PDF</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div>
        Copyright &copy; 2025
            <a class="text-body" href="https://aaaro.app/" >AAARO</a>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrForm = document.getElementById('qrForm');
        const customLogoInput = document.getElementById('custom-logo');
        const defaultLogoRadios = document.querySelectorAll('input[name="default-logo"]');
        const resetLogoButton = document.getElementById('reset-logo');

        // Variable to store QR code data in various formats
        let qrData = {
            png: null,
            svg: null,
            pdf: null
        };

        // Function to reset default logo radio selections
        function deselectDefaultLogos() {
            defaultLogoRadios.forEach(radio => radio.checked = false);
        }

        // If user selects custom file, reset default logo selections
        customLogoInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                deselectDefaultLogos();
            }
        });

        // If user selects default logo, reset custom file selection
        defaultLogoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                customLogoInput.value = ''; // Clear file input
            });
        });

        // Reset button function
        resetLogoButton.addEventListener('click', function() {
            deselectDefaultLogos();
            customLogoInput.value = '';
        });

        // Function to generate QR code in specific format
        async function generateQRCode(format) {
            const formData = new FormData(qrForm);
            formData.set('format', format);

            try {
                const response = await fetch('generate.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                console.log(`Generated ${format} successfully:`, {
                    format: data.format,
                    size: data.image ? data.image.length : 0,
                    mimeType: data.mime_type
                });
                
                return data;
            } catch (error) {
                console.error('Error generating QR code:', error);
                throw error;
            }
        }

        // Function to show success notification
        function showSuccessNotification(title, message) {
            // Remove any existing notifications
            const existingNotifications = document.querySelectorAll('.success-notification');
            existingNotifications.forEach(notification => notification.remove());
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'success-notification';
            notification.innerHTML = `
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <div style="font-size: 24px; margin-right: 12px;">✅</div>
                    <div style="font-weight: 600; color: #155724;">${title}</div>
                </div>
                <div style="color: #155724; font-size: 14px; margin-bottom: 12px;">${message}</div>
                <div style="display: flex; gap: 8px;">
                    <button onclick="this.parentElement.parentElement.remove()" style="
                        background: #28a745; 
                        color: white; 
                        border: none; 
                        padding: 6px 12px; 
                        border-radius: 4px; 
                        cursor: pointer;
                        font-size: 12px;
                    ">Got it!</button>
                </div>
            `;
            
            // Style the notification
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                padding: 16px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                max-width: 350px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }

        // Function to download file
        function downloadFile(dataUrl, filename, mimeType) {
            try {
                // For PDF files, use blob approach for better compatibility
                if (mimeType === 'application/pdf') {
                    // Extract base64 data from data URL
                    const base64Data = dataUrl.split(',')[1];
                    const byteCharacters = atob(base64Data);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    const blob = new Blob([byteArray], { type: mimeType });
                    
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                } else {
                    // For other file types, use the simple approach
                    const link = document.createElement('a');
                    link.href = dataUrl;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            } catch (error) {
                console.error('Download error:', error);
                throw new Error('Failed to download file: ' + error.message);
            }
        }

        // Event listener for form submit (generate PNG)
        qrForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Check if user is logged in
            <?php if (!$isLoggedIn): ?>
                alert('Please log in to create QR codes.');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            // Check quota
            <?php if ($isLoggedIn && !$quotaInfo['canCreate']): ?>
                alert('You have reached your monthly QR code limit. Please upgrade your plan.');
                window.location.href = 'payment.php';
                return;
            <?php endif; ?>
            
            const qrImage = document.getElementById('qrImage');
            const downloadPng = document.getElementById('download-png');
            const downloadSvg = document.getElementById('download-svg');
            const downloadPdf = document.getElementById('download-pdf');
            const shortLinkContainer = document.getElementById('short-link-container');
            const shortLink = document.getElementById('short-link');
            const emptyState = document.getElementById('empty-state');
            const qrResult = document.getElementById('qr-result');
            
            // Show processing state
            emptyState.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">⏳</div>
                <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">Generating QR Code...</div>
                <div style="font-size: 14px; opacity: 0.7;">Please wait</div>
            `;

            try {
                // Generate PNG first (for preview)
                const pngData = await generateQRCode('png');
                
                // Hide empty state and show QR result
                emptyState.style.display = 'none';
                qrResult.style.display = 'block';
                
                // Update QR image
                qrImage.src = 'data:image/png;base64,' + pngData.image;
                
                // Show and update short link
                if (pngData.short_link) {
                    shortLinkContainer.style.display = 'block';
                    shortLink.href = pngData.short_link;
                    shortLink.textContent = pngData.short_link;
                }
                
                // Store PNG data
                qrData.png = pngData;
                
                // Enable download buttons
                [downloadPng, downloadSvg, downloadPdf].forEach(btn => {
                    btn.classList.remove('disabled');
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    btn.style.pointerEvents = 'auto';
                });
                
                // Show success notification
                showSuccessNotification('QR Code generated successfully!', 'Your QR code is ready for download.');
                
            } catch (error) {
                // Show error state
                emptyState.innerHTML = `
                    <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">❌</div>
                    <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px; color: #dc3545;">Generation Failed</div>
                    <div style="font-size: 14px; opacity: 0.7;">Please try again</div>
                `;
                console.error('Error:', error);
                alert('Failed to generate QR code. Please try again.');
            }
        });

        // Event listener for download PNG
        document.getElementById('download-png').addEventListener('click', async function(e) {
            e.preventDefault();
            if (this.classList.contains('disabled')) return;
            
            try {
                if (!qrData.png) {
                    qrData.png = await generateQRCode('png');
                }
                const dataUrl = `data:${qrData.png.mime_type};base64,${qrData.png.image}`;
                downloadFile(dataUrl, `qr_code.${qrData.png.file_extension}`, qrData.png.mime_type);
            } catch (error) {
                alert('Failed to download PNG file!');
            }
        });

        // Event listener for download SVG
        document.getElementById('download-svg').addEventListener('click', async function(e) {
            e.preventDefault();
            if (this.classList.contains('disabled')) return;
            
            try {
                if (!qrData.svg) {
                    this.innerHTML = '<div>Generating SVG...</div>';
                    qrData.svg = await generateQRCode('svg');
                    this.innerHTML = '<div>Download SVG</div>';
                }
                const dataUrl = `data:${qrData.svg.mime_type};base64,${qrData.svg.image}`;
                downloadFile(dataUrl, `qr_code.${qrData.svg.file_extension}`, qrData.svg.mime_type);
            } catch (error) {
                alert('Failed to download SVG file!');
                this.innerHTML = '<div>Download SVG</div>';
            }
        });

        // Event listener for download PDF
        document.getElementById('download-pdf').addEventListener('click', async function(e) {
            e.preventDefault();
            if (this.classList.contains('disabled')) return;
            
            try {
                if (!qrData.pdf) {
                    this.innerHTML = '<div>Generating PDF...</div>';
                    qrData.pdf = await generateQRCode('pdf');
                    this.innerHTML = '<div>Download PDF</div>';
                }
                
                // Check if PDF data is valid
                if (!qrData.pdf.image) {
                    throw new Error('PDF data is empty');
                }
                
                console.log('PDF size:', qrData.pdf.image.length, 'chars');
                const dataUrl = `data:${qrData.pdf.mime_type};base64,${qrData.pdf.image}`;
                await downloadFile(dataUrl, `qr_code.${qrData.pdf.file_extension}`, qrData.pdf.mime_type);
                
            } catch (error) {
                console.error('PDF download error:', error);
                alert('Failed to download PDF file: ' + error.message);
                this.innerHTML = '<div>Download PDF</div>';
            }
        });
    });
    </script>
</body>
</html>