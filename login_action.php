<?php
// File: login_action.php - WITH SUCCESS POPUP
session_start();

// Database Configuration
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header("Location: login.php?error=Koneksi database gagal");
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
        header("Location: login.php?error=Terjadi kesalahan: " . $e->getMessage());
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>