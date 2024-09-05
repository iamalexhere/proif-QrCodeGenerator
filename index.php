<?php
    echo "Hello World!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QrCodeGenerator</title>
</head>
<body>

    <form action="generate.php" method="post">
        <input type="text" name="url" placeholder="Enter URL">
        <input type="submit" value="Generate">
    </form>

    <img src="" alt="QrCode">

    
</body>
</html>