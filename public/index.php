<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QR Code Generator</title>
    <meta name="title" content="IF Unpar QR Code Generator">
    <meta name="description" content="Website untuk membuat QR Code dari URL">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logoif.png" type="image/x-icon">
</head>

<body>
    <header>
        <nav class="navbar">
            <img src='images/logoif.png'>
            <a class="nav-link">
                <div>QR Code Generator</div>
            </a>
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
                        
                        <div class="form-submit"> 
                            <button type="submit" class="btn">Generate QR Code</button>
                        </div>
                    </form>
                </div>

                <div class="outputsection">
                    <h3>Output QR Code</h3>
                    <div>
                        <img id="qrImage" src='images/base.png'>
                    </div>
                    <div class=link-container>
                        <div id="short-link-container">Short Link:<a href="" target="_blank"></a></div>
                        <div id="download-links-container">
                            <a id="download-png" class="btn" style="margin-right: 10px;">
                                <div>Download PNG</div>
                            </a>
                            <a id="download-svg" class="btn" style="margin-right: 10px; background-color: #28a745;">
                                <div>Download SVG</div>
                            </a>
                            <a id="download-pdf" class="btn" style="background-color: #dc3545;">
                                <div>Download PDF</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--footer>
        <div>
        Copyright &copy; 2024
            <a class="text-body" href="https://informatika.unpar.ac.id/" >Informatika UNPAR</a>
        </div>
    </footer!-->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrForm = document.getElementById('qrForm');
        const customLogoInput = document.getElementById('custom-logo');
        const defaultLogoRadios = document.querySelectorAll('input[name="default-logo"]');
        const resetLogoButton = document.getElementById('reset-logo');

        // Variabel untuk menyimpan data QR code dalam berbagai format
        let qrData = {
            png: null,
            svg: null,
            pdf: null
        };

        // Fungsi untuk mereset pilihan radio logo bawaan
        function deselectDefaultLogos() {
            defaultLogoRadios.forEach(radio => radio.checked = false);
        }

        // Jika pengguna memilih file kustom, reset pilihan logo bawaan
        customLogoInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                deselectDefaultLogos();
            }
        });

        // Jika pengguna memilih logo bawaan, reset pilihan file kustom
        defaultLogoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                customLogoInput.value = ''; // Mengosongkan input file
            });
        });

        // Fungsi tombol reset
        resetLogoButton.addEventListener('click', function() {
            deselectDefaultLogos();
            customLogoInput.value = '';
        });

        // Fungsi untuk generate QR code dalam format tertentu
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

        // Fungsi untuk download file
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

        // Event listener untuk form submit (generate PNG)
        qrForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const qrImage = document.getElementById('qrImage');
            const downloadPng = document.getElementById('download-png');
            const downloadSvg = document.getElementById('download-svg');
            const downloadPdf = document.getElementById('download-pdf');
            const shortLinkContainer = document.getElementById('short-link-container');
            
            // Reset UI
            qrImage.style.opacity = 0.5;
            [downloadPng, downloadSvg, downloadPdf].forEach(btn => btn.classList.add('disabled'));
            shortLinkContainer.innerHTML = 'Memproses...';

            try {
                // Generate PNG first (untuk preview)
                const pngData = await generateQRCode('png');
                
                // Update UI dengan PNG
                qrImage.src = 'data:image/png;base64,' + pngData.image;
                qrImage.style.opacity = 1;
                
                // Update short link
                if (pngData.short_link) {
                    shortLinkContainer.innerHTML = `Short Link:<a href="${pngData.short_link}" target="_blank">${pngData.short_link}</a>`;
                }
                
                // Store PNG data
                qrData.png = pngData;
                
                // Enable download buttons
                [downloadPng, downloadSvg, downloadPdf].forEach(btn => btn.classList.remove('disabled'));
                
            } catch (error) {
                alert('Terjadi kesalahan saat mengirim data!');
                console.error('Error:', error);
                shortLinkContainer.innerHTML = 'Gagal memproses permintaan.';
            }
        });

        // Event listener untuk download PNG
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
                alert('Gagal mengunduh file PNG!');
            }
        });

        // Event listener untuk download SVG
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
                alert('Gagal mengunduh file SVG!');
                this.innerHTML = '<div>Download SVG</div>';
            }
        });

        // Event listener untuk download PDF
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
                alert('Gagal mengunduh file PDF: ' + error.message);
                this.innerHTML = '<div>Download PDF</div>';
            }
        });
    });
    </script>
</body>
</html>