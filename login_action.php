<?php
// File: login_action.php - WITH SUCCESS POPUP
// Perbaikan: "Email atau password salah" padahal sudah benar.
// - Bisa login dengan EMAIL atau USERNAME (huruf besar/kecil tidak berpengaruh)
// - Mengenali password hash modern (bcrypt/argon2) DAN format lama (teks biasa, MD5, SHA1, SHA256);
//   kalau cocok dengan format lama, password otomatis di-upgrade ke hash yang aman
// - Kolom password di database otomatis dilebarkan ke VARCHAR(255) bila terlalu pendek (hash terpotong)
session_start();

// ============ PENGATURAN DATABASE HOSTING ============
// LOCALHOST (Laragon/XAMPP): nilai di bawah sudah sesuai, tidak perlu diubah.
// HOSTING: ganti dengan host/db/user/password MySQL dari hosting (sama seperti admin.php di hosting).
$host        = '127.0.0.1';          // hosting contoh: sql123.infinityfree.com
$dbname      = 'movie_review_db';    // hosting contoh: if0_12345678_movie_review_db
$username_db = 'root';                // hosting contoh: if0_12345678
$password_db = '';                    // Laragon default kosong
$DEBUG       = false;                // ubah ke true sementara untuk melihat alasan login gagal
// =====================================================

if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', 1); }

function cs_fail($msg) {
    header('Location: login.php?error=' . urlencode($msg));
    exit();
}

// Cek password terhadap nilai yang tersimpan. $legacy = true jika formatnya format lama (perlu di-upgrade).
function cs_checkPassword($plain, $stored, &$legacy) {
    $legacy = false;
    $stored = trim((string)$stored);          // spasi/enter tak sengaja di database
    if ($stored === '') return false;

    $isHash = (bool)preg_match('/^\$(2[abxy]|argon2id?)\$/', $stored);
    foreach (array_unique(array($plain, trim($plain))) as $p) {
        if ($isHash) {
            if (password_verify($p, $stored)) return true;
            continue;
        }
        $s = strtolower($stored);
        $ok = hash_equals($stored, $p)                                  // password tersimpan sebagai teks biasa
           || (strlen($s) === 32 && hash_equals($s, md5($p)))
           || (strlen($s) === 40 && hash_equals($s, sha1($p)))
           || (strlen($s) === 64 && hash_equals($s, hash('sha256', $p)));
        if ($ok) { $legacy = true; return true; }
    }
    return false;
}

// Pastikan kolom password cukup panjang untuk menyimpan hash (60+ karakter)
function cs_widenPasswordColumn(PDO $pdo, $col) {
    try {
        $st = $pdo->prepare("SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
        $st->execute(array($col));
        $len = $st->fetchColumn();
        if ($len !== false && $len !== null && (int)$len < 255) {
            $pdo->exec("ALTER TABLE `users` MODIFY `$col` VARCHAR(255) NOT NULL");
            return true;
        }
    } catch (PDOException $e) { /* abaikan */ }
    return false;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    cs_fail($DEBUG ? 'Koneksi database gagal: ' . $e->getMessage() : 'Koneksi database gagal');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$identifier = trim((string)($_POST['email'] ?? ''));      // boleh email atau username
$password   = (string)($_POST['password'] ?? '');

if ($identifier === '' || $password === '') {
    cs_fail('Email dan password harus diisi!');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?) LIMIT 1");
    $stmt->execute(array($identifier, $identifier));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        cs_fail($DEBUG ? 'DEBUG: email/username tidak ditemukan di tabel users' : 'Email atau password salah!');
    }

    // Cari kolom yang menyimpan password (umumnya "password")
    $col = null; $stored = '';
    foreach (array('password', 'password_hash', 'passwd', 'pass') as $k) {
        if (array_key_exists($k, $user) && trim((string)$user[$k]) !== '') { $col = $k; $stored = (string)$user[$k]; break; }
    }
    if ($col === null) {
        cs_fail($DEBUG ? 'DEBUG: kolom password kosong / tidak ditemukan' : 'Email atau password salah!');
    }

    $legacy = false;
    if (!cs_checkPassword($password, $stored, $legacy)) {
        // Hash bcrypt terpotong (kolom terlalu pendek saat daftar)? Lebarkan kolom agar pendaftaran berikutnya aman.
        $truncated = (strpos(trim($stored), '$2y$') === 0 && strlen(trim($stored)) < 60);
        cs_widenPasswordColumn($pdo, $col);
        if ($truncated) {
            cs_fail('Data password akun ini rusak (terpotong di database). Silakan daftar ulang atau minta admin mereset password.');
        }
        cs_fail($DEBUG ? 'DEBUG: password tidak cocok dengan data di kolom "' . $col . '" (panjang ' . strlen($stored) . ')' : 'Email atau password salah!');
    }

    // Upgrade format lama / hash lama ke hash terbaru
    $needsUpgrade = $legacy || (preg_match('/^\$2[abxy]\$/', trim($stored)) && password_needs_rehash(trim($stored), PASSWORD_DEFAULT));
    if ($needsUpgrade) {
        try {
            cs_widenPasswordColumn($pdo, $col);
            $pdo->prepare("UPDATE `users` SET `$col` = ? WHERE id = ?")
                ->execute(array(password_hash($password, PASSWORD_DEFAULT), $user['id']));
        } catch (PDOException $e) { /* login tetap lanjut */ }
    }

    // Login berhasil
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'] ?? 'user';

    // Redirect ke user.php dengan username untuk popup
    header('Location: user.php?login_success=' . urlencode($user['username']));
    exit();

} catch (PDOException $e) {
    cs_fail($DEBUG ? $e->getMessage() : 'Terjadi kesalahan, coba lagi');
}
?>