<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IF Unpar QR Code Generator</title>
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</head>
<body>

<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand">
            <img src='images/Logo.jpg' width="50" height="50">
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
            <div class="col-8">
                <nav class="navbar">
                    <ul class="navbar-nav navbar-dark bg-light flex-row rounded">
                        <li class="nav-item px-3">
                            <a class="nav-link" href="#">URL</a>
                        </li>
                        
                    </ul>
                </nav>
                <div class="container border w-full">
                    <form id="qrForm" method="post" action="generate.php">
                        <div class="form-group mt-3">
                            <label for="url-input">URL</label>
                            <input type="text" name="url-input" class="form-control" id="url-input" placeholder="Tulis URL anda di sini">
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">Generate QR Code</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-4">
            <h3 class="text-center">Output QR Code</h3>

                <div class="d-flex justify-content-center mt-3">
                    <img id="qrImage" src='images/qr_placeholder.svg'>
                </div>
                
                
                <div class="form-check d-flex justify-content-center mt-3">
                  <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="" id="" value="checkedValue" checked>
                    Gunakan logo IF UNPAR
                  </label>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" class="btn btn-primary">Download PNG</button>
                </div>
            </div>
        </div>
    </div>
</section>


<footer class="bg-body-tertiary text-center text-lg-start">
  <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.05);">
    © 2024 Copyright:
    <a class="text-body" >IF UNPAR</a>
  </div>
</footer>

</body>
</html>
<script>
    document.getElementById('qrForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Mencegah pengiriman formulir secara default
        if(document.getElementById('url-input').value === '') {
            alert('URL tidak boleh kosong!');
            return;
        }

        const formData = new FormData(this);
        fetch('generate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('qrImage').src = 'data:image/png;base64,' + data;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
</script>