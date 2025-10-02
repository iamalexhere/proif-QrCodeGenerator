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
                                <input type="radio" name="default-logo" value="bitcoin.svg">
                                <img src="images/bitcoin.svg" alt="bitcoin">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="discord.svg">
                                <img src="images/discord.svg" alt="Discord">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="facebook.svg">
                                <img src="images/facebook.svg" alt="Facebook">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="gmail.svg">
                                <img src="images/gmail.svg" alt="Gmail">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="instagram.svg">
                                <img src="images/instagram.svg" alt="Instagram">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="line.svg">
                                <img src="images/line.svg" alt="LINE">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="linkedin.svg">
                                <img src="images/linkedin.svg" alt="LinkedIn">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="snapchat.svg">
                                <img src="images/snapchat.svg" alt="Snapchat">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="spotify.svg">
                                <img src="images/spotify.svg" alt="Spotify">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="telegram.svg">
                                <img src="images/telegram.svg" alt="Telegram">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="tiktok.svg">
                                <img src="images/tiktok.svg" alt="Tiktok">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="whatsapp.svg">
                                <img src="images/whatsapp.svg" alt="Whatsapp">
                            </label>
                            <label class="logo-option">
                                <input type="radio" name="default-logo" value="youtube.svg">
                                <img src="images/youtube.svg" alt="Youtube">
                            </label>
                        </div>
                        
                        <button type="button" id="reset-logo" class="btn" style="background-color: #6c757d;">Reset logo</button>

                        <div style="padding-top: 15px;">
                            <label class="url-input" for="custom-logo">Upload your own logo:</label>
                            <input type="file" name="custom-logo" id="custom-logo" class="form-control" accept="image/png, image/jpeg, image/svg+xml">
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
                        <div id="short-link-container">Short Link:<a href="http://qrcode.alexhere.me/r/bLVNjS" target="_blank">
    http://qrcode.alexhere.me/r/bLVNjS</a></div>
                        <div id=download-link-container>
                            <a id="download-link" class="btn">
                                <div>Download PNG</div>
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
                    if (data.image_type === 'svg') {
                        qrImage.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(data.image);
                        downloadLink.href = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(data.image);
                        downloadLink.setAttribute('download', 'qr_code.svg');
                    } else {
                        qrImage.src = 'data:image/png;base64,' + data.image;
                        downloadLink.href = 'data:image/png;base64,' + data.image;
                        downloadLink.setAttribute('download', 'qr_code.png');
                    }
                    downloadLink.classList.remove('disabled');
                    qrImage.style.opacity = 1;
                    if (data.short_link) {
                        shortLinkContainer.innerHTML = `Short Link:<a href="${data.short_link}" target="_blank">${data.short_link}</a>`;
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