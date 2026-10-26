<?php
// register_action.php (versi perbaikan: cek duplikasi username & email)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function back_with_error(string $msg): void {
    header('Location: bergabung_sekarang.php?error=' . urlencode($msg));
    exit;
}

try {
    require __DIR__ . '/config.php';

    if (!function_exists('db')) {
        throw new Exception('config.php di hosting BUKAN versi terbaru (fungsi db() tidak ada). Upload ulang config.php yang benar.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: bergabung_sekarang.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') back_with_error('Semua field harus diisi!');
    if (strlen($password) < 6) back_with_error('Password minimal 6 karakter!');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back_with_error('Format email tidak valid!');

    $pdo = db();

    // 1. Cek duplikasi Email
    $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $checkEmail->execute([$email]);
    if ($checkEmail->fetch()) back_with_error('Email sudah digunakan!');

    // 2. Cek duplikasi Username (INI YANG DITAMBAHKAN)
    $checkUser = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $checkUser->execute([$username]);
    if ($checkUser->fetch()) back_with_error('Username sudah digunakan! Silakan pilih username lain.');

    // 3. Proses Insert
    $insert = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
    $insert->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);

    // Redirect ke login dengan pesan sukses
    header('Location: login.php?registered=success');
    exit;

} catch (Throwable $e) {
    http_response_code(200);
    echo '<pre style="font:16px monospace;padding:20px;color:#b00">';
    echo "ERROR: " . htmlspecialchars($e->getMessage()) . "\n\n";
    echo "File : " . htmlspecialchars(basename($e->getFile())) . " baris " . $e->getLine() . "\n";
    echo "PHP  : " . PHP_VERSION . "\n";
    echo '</pre>';
}