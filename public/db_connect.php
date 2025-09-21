<?php

// 1. Definisikan Detail Koneksi
$hostname = "localhost";    // Alamat server database. Untuk XAMPP, selalu "localhost".
$username = "root";         // Username default untuk database di XAMPP.
$password = "";             // Password default untuk database di XAMPP adalah kosong.
$database = "qrcode_db";    // Nama database yang baru saja Anda buat.

// 2. Buat Koneksi Baru menggunakan MySQLi
$koneksi = new mysqli($hostname, $username, $password, $database);

// 3. Periksa Apakah Koneksi Berhasil
if ($koneksi->connect_error) {
    // Jika terjadi error, hentikan skrip dan tampilkan pesan kesalahan.
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
}

// Jika tidak ada error, tampilkan pesan sukses
echo "Berhasil terhubung ke database!";

// 4. (Opsional) Contoh Menjalankan Query (akan Anda gunakan nanti)
// $sql = "SELECT * FROM nama_tabel";
// $result = $koneksi->query($sql);

// 5. Selalu Tutup Koneksi Jika Sudah Selesai Digunakan
$koneksi->close();

?>