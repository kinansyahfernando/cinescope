<?php
// File: register_action.php - WITH USERNAME REDIRECT
session_start();

// ============ PENGATURAN DATABASE HOSTING ============
// LOCALHOST (Laragon/XAMPP): nilai di bawah sudah sesuai, tidak perlu diubah.
// HOSTING: ganti dengan host/db/user/password MySQL dari hosting (sama seperti admin.php di hosting).
$host        = '127.0.0.1';          // hosting contoh: sql123.infinityfree.com
$dbname      = 'movie_review_db';    // hosting contoh: if0_12345678_movie_review_db
$username_db = 'root';                // hosting contoh: if0_12345678
$password_db = '';                    // Laragon default kosong
$DEBUG       = false;                // ubah ke true sementara untuk melihat pesan error asli
// =====================================================

if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', 1); }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $msg = $DEBUG ? "Koneksi database gagal: " . $e->getMessage() : "Koneksi database gagal";
    header("Location: bergabung_sekarang.php?error=" . urlencode($msg));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        header("Location: bergabung_sekarang.php?error=Semua field harus diisi!");
        exit();
    }

    if (strlen($password) < 6) {
        header("Location: bergabung_sekarang.php?error=Password minimal 6 karakter!");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: bergabung_sekarang.php?error=Format email tidak valid!");
        exit();
    }

    // Cek apakah email sudah ada (username boleh sama)
    $checkStmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->rowCount() > 0) {
        header("Location: bergabung_sekarang.php?error=Email sudah digunakan!");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user baru (role default: 'user')
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, created_at) 
            VALUES (?, ?, ?, 'user', NOW())
        ");
        $insertStmt->execute([$username, $email, $hashedPassword]);

        // Redirect ke login TANPA username (tidak ada popup)
        header("Location: login.php?registered=success");
        exit();

    } catch(PDOException $e) {
        header("Location: bergabung_sekarang.php?error=" . urlencode($DEBUG ? "Gagal mendaftar: " . $e->getMessage() : "Gagal mendaftar, coba lagi"));
        exit();
    }
} else {
    header("Location: bergabung_sekarang.php");
    exit();
}
?>