<?php
// File: login_action.php (versi perbaikan - memakai config.php)
require __DIR__ . '/config.php';

function back_with_error(string $msg): void {
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    back_with_error('Email dan password harus diisi!');
}

try {
    $pdo = db(); // koneksi dari config.php
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        // REVISI: catat waktu login terakhir & jumlah login (dipakai menu Users di admin.php)
        try {
            if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'")->fetch()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
            }
            if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'login_count'")->fetch()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN login_count INT NOT NULL DEFAULT 0");
            }
            $pdo->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?")->execute([$user['id']]);
        } catch (PDOException $e) {
            error_log('track login: ' . $e->getMessage());
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        if (($user['role'] ?? '') === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: user.php?login_success=' . urlencode($user['username']));
        }
        exit;
    }

    back_with_error('Email atau password salah!');
} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    back_with_error('Terjadi kesalahan.' . (APP_DEBUG ? ' ' . $e->getMessage() : ''));
}