<?php
// File: login_action.php - WITH SUCCESS POPUP
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
    header("Location: login.php?error=" . urlencode($msg));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=Email dan password harus diisi!");
        exit();
    }

    // Cek user di database
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Redirect ke user.php dengan username untuk popup
            header("Location: user.php?login_success=" . urlencode($user['username']));
            exit();

        } else {
            // Login gagal
            header("Location: login.php?error=Email atau password salah!");
            exit();
        }

    } catch(PDOException $e) {
        header("Location: login.php?error=" . urlencode($DEBUG ? $e->getMessage() : "Terjadi kesalahan, coba lagi"));
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>