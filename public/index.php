<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>IF Unpar QR Code Generator</title>
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light p-0">
            <a class="navbar-brand">
                <img src='images/logo_small.png' width="50" height="50">
            </a>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="#">IF UNPAR QR Code Generator</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <section class="h-100">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-8">
                    <nav class="navbar">
                        <ul class="navbar-nav navbar-dark bg-light flex-row rounded">
                            <li class="nav-item px-3">
                                <a class="nav-link" href="#">URL</a>
                            </li>
                        </ul>
                    </nav>
                    <div class="container border w-full pb-3">
                        <form id="qrForm" method="post" action="generate.php">
                            <div class="form-group mt-3">
                                <label for="url-input">URL</label>
                                <input type="text" name="url-input" class="form-control" id="url-input"
                                    placeholder="Tulis URL anda di sini">
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="use-logo" name="use-logo"
                                    value="yes">
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

                    <div class=" d-flex justify-content-center mt-3  ">
                        <img id="qrImage" src='images/qr_placeholder.svg' style="opacity: 0.25">
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <a id="download-link" href="#" class="btn btn-primary disabled d-flex align-items-center justify-content-center">
                            Download PNG
                            <img src="images/apps.png" alt="Icon" width="20" height="20" class="mr-2">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-body-tertiary text-center text-lg-start vw-100">
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.05);">
            © 2024 Copyright:
            <a class="text-body">IF UNPAR</a>
        </div>
    </footer>

    <script>
        document.getElementById('qrForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah pengiriman formulir secara default
            const formData = new FormData(this);

            // Validasi input URL
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
                        // Update gambar QR Code
                        document.getElementById('qrImage').src = 'data:image/png;base64,' + data.image;

                        // Update link download
                        document.getElementById('download-link').href = data.downloadUrl;
                        document.getElementById('download-link').setAttribute('download', 'generated_qr_code.png');
                        document.getElementById('download-link').classList.remove('disabled');
                        document.getElementById('qrImage').style.opacity = 1;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    </script>
</body>

</html>