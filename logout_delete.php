<?php
// WAJIB: Matikan output buffering untuk menghindari header gagal
ob_start();
session_start();

// Jika tidak login, kembali ke index
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Koneksi database

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong


try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Hindari menampilkan error karena bisa merusak header
    header("Location: index.php?deleted=0");
    exit;
}

$userId = $_SESSION['user_id'];

// Hapus akun dari database
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Clear session
session_unset();
session_destroy();

// Bersihkan output buffer
ob_end_clean();

// Redirect ke index.php
header("Location: index.php?deleted=1");
exit;
