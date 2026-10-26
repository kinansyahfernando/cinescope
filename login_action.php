<?php
// File: login_action.php (versi perbaikan - memakai config.php)
// Tambahan: setiap login BERHASIL dicatat ke tabel `login_logs` supaya admin bisa melihat
// siapa saja yang login (lihat admin.php?action=users).
require __DIR__ . '/config.php';

function back_with_error(string $msg): void {
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

// Catat login ke database. Kalau gagal mencatat, login TETAP berjalan normal.
function log_login(PDO $pdo, array $user): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `login_logs` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(100) NULL,
            email VARCHAR(150) NULL,
            role VARCHAR(20) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            logged_in_at DATETIME NOT NULL,
            KEY idx_login_user (user_id),
            KEY idx_login_time (logged_in_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->prepare("INSERT INTO login_logs (user_id, username, email, role, ip_address, user_agent, logged_in_at)
                       VALUES (?, ?, ?, ?, ?, ?, NOW())")
            ->execute([
                (int)$user['id'],
                $user['username'] ?? null,
                $user['email'] ?? null,
                $user['role'] ?? null,
                substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
    } catch (PDOException $e) {
        error_log('log_login: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
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
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['role']     = $user['role'];

        log_login($pdo, $user); // catat riwayat login

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