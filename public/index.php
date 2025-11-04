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
    <link rel="stylesheet" href="css/test.css">

    <link rel="icon" href="images/logo-aaaro.png" type="image/x-icon">

</head>



<body>

    <header>

        <nav class="navbar">

            <div class="navbar-left">

                <a href="<?php echo $isLoggedIn ? 'dashboardAll.php' : 'login.php'; ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">

                    <img src='images/logo-aaaro.png' alt="AAARO Logo">

                    <div class="brand-text">

                        <div class="brand-title">AAARO</div>

                        <div class="brand-subtitle">Complexity, simplified</div>

                    </div>

                </a>

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



                        <div class="customization-container">

                           

                            <div class="accordion-item">

                                <button type="button" class="accordion-header" data-target="logo-content">

                                    🖼️ Add Logo / Image Upload

                                    <span class="icon">▼</span>

                                </button>

                                <div class="accordion-content" id="logo-content">

                                    <p style="font-weight: 500;">Select a pre-set logo:</p>

                                    <div class="logo-options">

                                        <label class="logo-option"><input type="radio" name="default-logo" value="instagram.webp"><img src="images/instagram.webp" alt="Instagram"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="tiktok.png"><img src="images/tiktok.png" alt="TikTok"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="line.png"><img src="images/line.png" alt="Line"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="spotify.webp"><img src="images/spotify.webp" alt="Spotify"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="youtube.png"><img src="images/youtube.png" alt="YouTube"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="twitter.png"><img src="images/twitter.png" alt="Twitter"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="facebook.png"><img src="images/facebook.png" alt="Facebook"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="snapchat.png"><img src="images/snapchat.png" alt="Snapchat"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="LinkedIn.png"><img src="images/LinkedIn.png" alt="LinkedIn"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="whatsApp.png"><img src="images/whatsApp.png" alt="WhatsApp"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="gmail.png"><img src="images/gmail.png" alt="Gmail"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="telegram.png"><img src="images/telegram.png" alt="Telegram"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="discord.png"><img src="images/discord.png" alt="Discord"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="bitcoin.png"><img src="images/bitcoin.png" alt="Bitcoin"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="gopay.png"><img src="images/gopay.png" alt="Gopay"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="ovo.webp"><img src="images/ovo.webp" alt="OVO"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="dana.png"><img src="images/dana.png" alt="Dana"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="wifi.png"><img src="images/wifi.png" alt="Wifi"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="drive.png"><img src="images/drive.png" alt="Google Drive"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="tokopedia.png"><img src="images/tokopedia.png" alt="Tokopedia"></label>

                                        <label class="logo-option"><input type="radio" name="default-logo" value="shopee.png"><img src="images/shopee.png" alt="Shopee"></label>

                                    </div>

                                   

                                    <button type="button" id="reset-logo" class="btn" style="background-color: #6c757d; font-size: 14px; padding: 6px 10px; margin-top: 10px;">Reset logo</button>



                                    <div style="padding-top: 15px;">

                                        <label class="url-input" for="custom-logo">Upload your own logo:</label>

                                        <input type="file" name="custom-logo" id="custom-logo" class="form-control" accept="image/png, image/jpeg">

                                    </div>

                                </div>

                            </div>



                            <div class="accordion-item">

                                <button type="button" class="accordion-header" data-target="color-content">

                                    🌈 QR Code Colors

                                    <span class="icon">▼</span>

                                </button>

                                <div class="accordion-content" id="color-content">

                                    <div style="padding-top: 5px;">

                                        <label class="url-input" for="qr_color">QR Code Foreground Color:</label>

                                        <div class="color-picker-row" style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">

                                            <input type="color" name="qr_color" id="qr_color" value="#000000"

                                                    style="width: 60px; height: 40px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;">

                                           

                                            <input type="text" id="qr_color_hex" value="#000000"

                                                    placeholder="#000000" maxlength="7" class="hex-input"

                                                    style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace;">

                                           

                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">

                                                <button type="button" class="preset-color preset-fg" data-color="#000000" style="width: 30px; height: 30px; border-radius: 5px; border: 3px solid #007bff; cursor: pointer; background: #000000;" title="Black"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#FF0000" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #FF0000;" title="Red"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#0000FF" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #0000FF;" title="Blue"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#008000" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #008000;" title="Green"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#800080" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #800080;" title="Purple"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#FF6600" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #FF6600;" title="Orange"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#FF1493" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #FF1493;" title="Pink"></button>

                                                <button type="button" class="preset-color preset-fg" data-color="#00CED1" style="width: 30px; height: 30px; border-radius: 5px; border: 2px solid #ddd; cursor: pointer; background: #00CED1;" title="Cyan"></button>

                                            </div>

                                        </div>

                                        <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">

                                            Choose a color for your QR code. Avoid very light colors for better scanning.

                                        </small>

                                    </div>



                                    <div style="padding-top: 15px;">

                                        <label class="url-input" for="qr_bg_color">QR Code Background Color:</label>

                                        <div class="color-picker-row" style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">

                                            <input type="color" name="qr_bg_color" id="qr_bg_color" value="#FFFFFF"

                                                    style="width: 60px; height: 40px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;">

                                           

                                            <input type="text" id="qr_bg_color_hex" value="#FFFFFF"

                                                    placeholder="#FFFFFF" maxlength="7" class="hex-input"

                                                    style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace;">

                                           

                                            <button type="button" id="reset-bg-color" class="btn"

                                                    style="background-color: #6c757d; padding: 8px 15px; font-size: 12px;">

                                                Reset to White

                                            </button>

                                        </div>

                                        <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">

                                            Background should have good contrast with foreground color.

                                        </small>

                                    </div>

                                </div>

                            </div>

                           

                            <div class="accordion-item">

                                <button type="button" class="accordion-header" data-target="preview-content">

                                    👁️ Color Contrast Preview

                                    <span class="icon">▼</span>

                                </button>

                                <div class="accordion-content" id="preview-content">

                                    <div style="padding-top: 5px;">

                                        <label class="url-input">Current Colors:</label>

                                        <div id="color-preview" style="

                                            margin-top: 10px;

                                            padding: 20px;

                                            border: 2px solid #ddd;

                                            border-radius: 8px;

                                            text-align: center;

                                            transition: all 0.3s ease;

                                        ">

                                            <div style="

                                                width: 100px; height: 100px; margin: 0 auto;

                                                border-radius: 8px; display: flex; align-items: center;

                                                justify-content: center; font-size: 48px;

                                                transition: all 0.3s ease; background: #FFFFFF; color: #000000;

                                            " id="preview-box">

                                                ▀▄▀▄

                                            </div>

                                            <div style="margin-top: 10px; font-size: 12px; color: #666;">

                                                <span id="preview-text">Black on White</span>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                           

                        </div>

                        <?php if ($isLoggedIn): ?>

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

       

        // New: Accordion elements

        const accordionHeaders = document.querySelectorAll('.accordion-header');



        // Color picker elements

        const qrColorPicker = document.getElementById('qr_color');

        const qrColorHex = document.getElementById('qr_color_hex');

        const qrBgColorPicker = document.getElementById('qr_bg_color');

        const qrBgColorHex = document.getElementById('qr_bg_color_hex');

        const previewBox = document.getElementById('preview-box');

        const previewText = document.getElementById('preview-text');

        const resetBgBtn = document.getElementById('reset-bg-color');

        const presetFgButtons = document.querySelectorAll('.preset-fg');



        // Variable to store QR code data in various formats

        let qrData = {

            png: null,

            svg: null,

            pdf: null

        };

       

        // ===== ACCORDION FUNCTION =====

        accordionHeaders.forEach(header => {

            header.addEventListener('click', function() {

                const targetId = this.getAttribute('data-target');

                const targetContent = document.getElementById(targetId);

               

                // Toggle current header and content

                this.classList.toggle('active');

                targetContent.classList.toggle('active');

            });

        });





        // ===== LOGO SELECTION FUNCTIONS (Unchanged) =====

       

        function deselectDefaultLogos() {

            defaultLogoRadios.forEach(radio => radio.checked = false);

        }



        customLogoInput.addEventListener('change', function() {

            if (this.files.length > 0) { deselectDefaultLogos(); }

        });



        defaultLogoRadios.forEach(radio => {

            radio.addEventListener('change', function() {

                customLogoInput.value = '';

            });

        });



        resetLogoButton.addEventListener('click', function() {

            deselectDefaultLogos();

            customLogoInput.value = '';

        });



        // ===== COLOR PICKER & PREVIEW FUNCTIONS (Unchanged) =====

       

        function isValidHex(hex) {

            return /^#[0-9A-F]{6}$/i.test(hex);

        }



        function calculateContrast(color1, color2) {

            const getLuminance = (hex) => {

                const rgb = parseInt(hex.slice(1), 16);

                const r = (rgb >> 16) & 0xff;

                const g = (rgb >> 8) & 0xff;

                const b = (rgb >> 0) & 0xff;

                const lum = [r, g, b].map(v => {

                    v /= 255;

                    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);

                });

                return 0.2126 * lum[0] + 0.7152 * lum[1] + 0.0722 * lum[2];

            };

           

            const lum1 = getLuminance(color1);

            const lum2 = getLuminance(color2);

            const brightest = Math.max(lum1, lum2);

            const darkest = Math.min(lum1, lum2);

            return (brightest + 0.05) / (darkest + 0.05);

        }



        function updatePreview() {

            const fgColor = qrColorPicker.value;

            const bgColor = qrBgColorPicker.value;

            previewBox.style.background = bgColor;

            previewBox.style.color = fgColor;

            previewText.textContent = `${fgColor.toUpperCase()} on ${bgColor.toUpperCase()}`;

           

            const contrast = calculateContrast(fgColor, bgColor);

            if (contrast < 3) {

                previewText.style.color = '#dc3545';

                previewText.innerHTML = `${fgColor.toUpperCase()} on ${bgColor.toUpperCase()} <br>⚠️ Low contrast! May be hard to scan.`;

            } else {

                previewText.style.color = '#666';

            }

        }



        qrColorPicker.addEventListener('input', function() {

            qrColorHex.value = this.value.toUpperCase();

            updatePreview();

        });



        qrColorHex.addEventListener('input', function() {

            let value = this.value.trim();

            if (!value.startsWith('#')) { value = '#' + value; }

            if (isValidHex(value)) {

                qrColorPicker.value = value;

                this.style.borderColor = '#ddd';

                updatePreview();

            } else { this.style.borderColor = '#dc3545'; }

        });



        qrBgColorPicker.addEventListener('input', function() {

            qrBgColorHex.value = this.value.toUpperCase();

            updatePreview();

        });



        qrBgColorHex.addEventListener('input', function() {

            let value = this.value.trim();

            if (!value.startsWith('#')) { value = '#' + value; }

            if (isValidHex(value)) {

                qrBgColorPicker.value = value;

                this.style.borderColor = '#ddd';

                updatePreview();

            } else { this.style.borderColor = '#dc3545'; }

        });



        presetFgButtons.forEach(button => {

            button.addEventListener('click', function(e) {

                e.preventDefault();

                const color = this.getAttribute('data-color');

                qrColorPicker.value = color;

                qrColorHex.value = color;

               

                presetFgButtons.forEach(btn => btn.style.border = '2px solid #ddd');

                this.style.border = '3px solid #007bff';

               

                updatePreview();

            });

        });



        resetBgBtn.addEventListener('click', function(e) {

            e.preventDefault();

            qrBgColorPicker.value = '#FFFFFF';

            qrBgColorHex.value = '#FFFFFF';

            updatePreview();

        });



        updatePreview();



        // ===== QR CODE GENERATION FUNCTIONS (Unchanged) =====

       

        async function generateQRCode(format) {

            const formData = new FormData(qrForm);

            formData.set('format', format);

            try {

                const response = await fetch('generate.php', { method: 'POST', body: formData });

                if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }

                const data = await response.json();

                if (data.error) { throw new Error(data.error); }

                return data;

            } catch (error) {

                console.error('Error generating QR code:', error);

                throw error;

            }

        }



        function showSuccessNotification(title, message) {

            const existingNotifications = document.querySelectorAll('.success-notification');

            existingNotifications.forEach(notification => notification.remove());

           

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

                        background: #28a745; color: white; border: none; padding: 6px 12px;

                        border-radius: 4px; cursor: pointer; font-size: 12px;">Got it!</button>

                </div>

            `;

           

            notification.style.cssText = `

                position: fixed; top: 20px; right: 20px; background: #d4edda; border: 1px solid #c3e6cb;

                border-radius: 8px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);

                z-index: 1000; max-width: 350px; opacity: 0; transform: translateX(100%);

                transition: all 0.3s ease;

            `;

           

            document.body.appendChild(notification);

           

            setTimeout(() => {

                notification.style.opacity = '1';

                notification.style.transform = 'translateX(0)';

            }, 100);

           

            setTimeout(() => {

                if (notification.parentElement) {

                    notification.style.opacity = '0';

                    notification.style.transform = 'translateX(100%)';

                    setTimeout(() => notification.remove(), 300);

                }

            }, 5000);

        }



        function downloadFile(dataUrl, filename, mimeType) {

            try {

                if (mimeType === 'application/pdf') {

                    const base64Data = dataUrl.split(',')[1];

                    const byteCharacters = atob(base64Data);

                    const byteNumbers = new Array(byteCharacters.length);

                    for (let i = 0; i < byteCharacters.length; i++) { byteNumbers[i] = byteCharacters.charCodeAt(i); }

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

           

            <?php if (!$isLoggedIn): ?>

                alert('Please log in to create QR codes.');

                window.location.href = 'login.php';

                return;

            <?php endif; ?>

           

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

            emptyState.style.display = 'flex';

            qrResult.style.display = 'none';

            emptyState.innerHTML = `

                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">⏳</div>

                <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">Generating QR Code...</div>

                <div style="font-size: 14px; opacity: 0.7;">Please wait</div>

            `;



            try {

                const pngData = await generateQRCode('png');

               

                emptyState.style.display = 'none';

                qrResult.style.display = 'block';

                qrImage.src = 'data:image/png;base64,' + pngData.image;

               

                if (pngData.short_link) {

                    shortLinkContainer.style.display = 'block';

                    shortLink.href = pngData.short_link;

                    shortLink.textContent = pngData.short_link;

                }

               

                qrData.png = pngData;

                qrData.svg = null;

                qrData.pdf = null;

               

                [downloadPng, downloadSvg, downloadPdf].forEach(btn => {

                    btn.classList.remove('disabled');

                    btn.style.opacity = '1';

                    btn.style.cursor = 'pointer';

                    btn.style.pointerEvents = 'auto';

                });

               

                showSuccessNotification('QR Code generated successfully!', 'Your QR code is ready for download.');

               

            } catch (error) {

                emptyState.style.display = 'flex';

                qrResult.style.display = 'none';

                emptyState.innerHTML = `

                    <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">❌</div>

                    <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px; color: #dc3545;">Generation Failed</div>

                    <div style="font-size: 14px; opacity: 0.7;">${error.message || 'Please try again'}</div>

                `;

                console.error('Error:', error);

                alert('Failed to generate QR code: ' + (error.message || 'Please try again.'));

            }

        });



        // Event listeners for downloads (Unchanged)

        document.getElementById('download-png').addEventListener('click', async function(e) {

            e.preventDefault();

            if (this.classList.contains('disabled')) return;

            try {

                if (!qrData.png) { qrData.png = await generateQRCode('png'); }

                const dataUrl = `data:${qrData.png.mime_type};base64,${qrData.png.image}`;

                downloadFile(dataUrl, `qr_code.${qrData.png.file_extension}`, qrData.png.mime_type);

            } catch (error) { alert('Failed to download PNG file!'); }

        });



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



        document.getElementById('download-pdf').addEventListener('click', async function(e) {

            e.preventDefault();

            if (this.classList.contains('disabled')) return;

            try {

                if (!qrData.pdf) {

                    this.innerHTML = '<div>Generating PDF...</div>';

                    qrData.pdf = await generateQRCode('pdf');

                    this.innerHTML = '<div>Download PDF</div>';

                }

                if (!qrData.pdf.image) { throw new Error('PDF data is empty'); }

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