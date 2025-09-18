<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IF Unpar QR Code Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logoif.png" type="image/x-icon">
</head>
<body>
<header>
    <nav class="navbar">
        <img src='/images/logoif.png' alt="logo">
        <a class="nav-link">QR Code Generator</a>
    </nav>
</header>
<section>
    <div class="container">
        <div class="content">
            <div class="inputsection">
                <!-- Form upload logo dan URL -->
                <form id="qrForm" method="post" action="generate.php" enctype="multipart/form-data">
                    <div>
                        <label class="url-input" for="url-input">URL</label>
                        <input type="url" name="url-input" id="url-input" class="form-control" placeholder="Tulis URL anda di sini" required>
                    </div>
                    <div>
                        <!-- Upload logo-->
                        <label for="logo-upload">Upload Logo (opsional):</label>
                        <input type="file" name="logo-upload" id="logo-upload" accept="image/*">
                    </div>
                    <div>
                        <!-- Memilih warna QRCode -->
                        <label for="color">Warna QR Code:</label>
                        <input type="color" name="color" id="color" value="#000000">
                    </div>
                    <div class="form-submit">
                        <button type="submit" class="btn">Generate QR Code</button>
                    </div>
                </form>
            </div>
            <div class="outputsection">
                <h3>Output QR Code</h3>
                <div>
                    <img id="qrImage" src='' style="max-width:300px; opacity:0;">
                </div>
                <p id="shortlink" style="word-wrap:break-word;"></p>
                <div>
                    <a id="download-link" class="btn disabled">
                        Download PNG
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<footer>
    <div>
        Copyright &copy; 2024
        <a class="text-body" href="https://informatika.unpar.ac.id/">Informatika UNPAR</a>
    </div>
</footer>

<script>
document.getElementById('qrForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);

    fetch('generate.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            const qrImage = document.getElementById('qrImage');
            qrImage.src = 'data:image/png;base64,' + data.image;
            qrImage.style.opacity = 1;

            if (data.short_link) {
                document.getElementById('shortlink').textContent = 'Short link: ' + data.short_link;
            }

            const downloadLink = document.getElementById('download-link');
            downloadLink.href = 'data:image/png;base64,' + data.image;
            downloadLink.setAttribute('download', 'generated_qr_code.png');
            downloadLink.classList.remove('disabled');
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
