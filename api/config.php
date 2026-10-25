<?php
// config.php - File Konfigurasi Database
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'movie_review_db');

// Membuat koneksi
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if($conn === false){
    die("ERROR: Tidak dapat terhubung ke database. " . mysqli_connect_error());
}

// Set charset untuk mendukung karakter unicode
mysqli_set_charset($conn, "utf8mb4");
?>