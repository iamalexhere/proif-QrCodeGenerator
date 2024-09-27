<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>IF Unpar QR Code Generator</title>
    <meta name="title" content="IF Unpar QR Code Generator">
    <meta name="description" content="Website ini untuk membuat QR Code IF Unpar">
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
            <a class="navbar-brand">
                <img src='images/logoif.png' >
            </a>
            <div>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link">QR Code Generator</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <section>
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-8">
                    <div class="container border pb-3">
                        <form id="qrForm" method="post" action="generate.php">
                            <div class="form-group mt-3">
                                <label class="url-input" for="url-input">URL</label>
                                <input type="url" name="url-input" class="form-control" id="url-input"
                                    placeholder="Tulis URL anda di sini">
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="use-logo" name="use-logo"
                                    value="yes" checked>
                                <label class="form-check-label" for="use-logo">Gunakan logo IF UNPAR</label>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">Generate QR Code</button>
                            </div>
                        </form>
                    </div>

                </div>
                <div class="col-12 col-md-4">
                    <h3 class="text-center">Output QR Code</h3>
                    <div class=" d-flex justify-content-center mt-3">
                        <img id="qrImage" src=''>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <a id="download-link" class="btn disabled d-flex">
                            <img src="images/download.svg" alt="Icon" class="mr-2">
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