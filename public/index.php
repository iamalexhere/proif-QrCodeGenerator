<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>IF Unpar QR Code Generator</title>
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
            <a class="nav-link">QR Code Generator</a>
        </nav>
    </header>
    <section>
        <div class="container">
            <div class="content">
                <div class="inputsection">
                    <form id="qrForm" method="post" enctype="multipart/form-data">
                        <div>
                            <label class="url-input" for="url-input">URL</label>
                            <input type="url" name="url-input" id="url-input" class="form-control" placeholder="Tulis URL anda di sini" required>
                        </div>

                        <p>Pilih Logo Bawaan:</p>
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
                        
                        <button type="button" id="reset-logo" class="btn" style="background-color: #6c757d;">Tanpa Logo / Reset Pilihan</button>

                        <div style="padding-top: 15px;">
                            <label class="url-input" for="custom-logo">Atau Upload Logo Sendiri (opsional):</label>
                            <input type="file" name="custom-logo" id="custom-logo" class="form-control" accept="image/png, image/jpeg">
                        </div>

                        <div style="padding-top: 15px;">
                            <label class="url-input" for="qr_color">Warna QR Code:</label>
                            <input type="color" name="qr_color" id="qr_color" value="#000000">
                        </div>
                        
                        <div class="form-submit" style="padding-top: 15px;"> 
                            <button type="submit" class="btn">Generate QR Code</button>
                        </div>
                    </form>
                </div>

                <div class="outputsection">
                    <h3>Output QR Code</h3>
                    <div>
                        <img id="qrImage" src=''>
                    </div>
                    <div id="short-link-container" style="margin-top: 15px; text-align: center; word-break: break-all;"></div>
                    <div>
                        <a id="download-link" class="btn disabled">
                            Download PNG
                        </a>
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

        qrForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            const qrImage = document.getElementById('qrImage');
            const downloadLink = document.getElementById('download-link');
            const shortLinkContainer = document.getElementById('short-link-container');
            
            qrImage.style.opacity = 0.5;
            downloadLink.classList.add('disabled');
            shortLinkContainer.innerHTML = 'Memproses...';

            fetch('generate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    shortLinkContainer.innerHTML = '';
                } else {
                    qrImage.src = 'data:image/png;base64,' + data.image;
                    downloadLink.href = 'data:image/png;base64,' + data.image;
                    downloadLink.setAttribute('download', 'qr_code.png');
                    downloadLink.classList.remove('disabled');
                    qrImage.style.opacity = 1;

                    if (data.short_link) {
                        shortLinkContainer.innerHTML = `Short Link: <a href="${data.short_link}" target="_blank">${data.short_link}</a>`;
                    }
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan saat mengirim data!');
                console.error('Error:', error);
                shortLinkContainer.innerHTML = 'Gagal memproses permintaan.';
            });
        });
    });
    </script>
</body>
</html>