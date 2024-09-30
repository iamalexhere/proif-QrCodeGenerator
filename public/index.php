<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>IF Unpar QR Code Generator</title>
    <meta name="title" content="IF Unpar QR Code Generator">
    <meta name="description" content="Website untuk membuat QR Code IF Unpar">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:image" content="https://qrcode.ifunpar.id/images/logoif.png">
    <!-- <meta property="og:image:width" content="width_in_pixels">
    <meta property="og:image:height" content="height_in_pixels"> -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo_small.png" type="image/x-icon">
</head>

<body>
    <header>
        <nav class="navbar">
            <img src='images/logoif.png' >
            <a class="nav-link">QR Code Generator</a>
        </nav>
    </header>
    <section>
        <div class="container">
            <div class="content">
                <div class="inputsection">
                    <form id="qrForm" method="post" action="generate.php">
                        <div class="url-input" for="url-input">URL</div>
                        <div>
                            <input type="url" class="form-control" placeholder="Tulis URL anda di sini">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" value="yes" checked>Gunakan logo IF UNPAR
                        </div>
                        <div class="form-submit"> 
                            <button type="submit" class="btn">Generate QR Code</button>
                        </div>
                    </form>
                </div>
                <div class="outputsection">
                    <h3>Output QR Code</h3>
                    <div>
                        <img id="qrImage">
                    </div>
                    <div>
                        <a id="download-link" class="btn">
                            Download PNG
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div>
            © 2024 Copyright:
            <a class="text-body">IF UNPAR</a>
        </div>
    </footer>

    <script>
    document.getElementById('qrForm').addEventListener('submit', function(event) {
    event.preventDefault(); // tidak boleh posisi default
    const formData = new FormData(this);

    // memvalidasi input URL
    if (document.getElementById('url-input').value === '') {
        alert('URL tidak boleh kosong!');
        return;
    }

    fetch('generate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                // mengupdate gambar qr code pada halaman
                const qrImage = document.getElementById('qrImage');
                qrImage.src = 'data:image/png;base64,' + data.image;
                
                // Membuat download link buat image
                const downloadLink = document.getElementById('download-link');
                downloadLink.href = 'data:image/png;base64,' + data.image;
                downloadLink.setAttribute('download', 'generated_qr_code.png');
                downloadLink.classList.remove('disabled');
                qrImage.style.opacity = 1;
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan saat mengirim data!');
            console.error('Error:', error);
        });
});

    </script>
</body>
</html>