<?php
// config.php - SATU-SATUNYA konfigurasi + helper (Laragon: C:\laragon\www\movie-review\config.php)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'movie_review_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_DEBUG', true); // WAJIB false kalau website sudah online

error_reporting(E_ALL);
ini_set('display_errors', (APP_DEBUG && !defined('IS_API')) ? '1' : '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('DB connect: ' . $e->getMessage());
        $msg = 'Koneksi database gagal.' . (APP_DEBUG ? ' ' . $e->getMessage() : '');
        if (defined('IS_API')) json_out(['success' => false, 'message' => $msg], 500);
        http_response_code(500);
        exit(htmlspecialchars($msg));
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function is_admin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }

function require_login(): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}
function require_admin(): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
    if (!is_admin()) { http_response_code(403); exit('Akses ditolak: hanya admin.'); }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_verify(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
}

function base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (str_ends_with($dir, '/api')) $dir = substr($dir, 0, -4);
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir;
}

// Normalisasi path poster: "sore.jpg" -> "uploads/posters/sore.jpg"
function poster_rel(?string $p): ?string {
    $p = trim((string)$p);
    if ($p === '') return null;
    if (preg_match('#^https?://#i', $p)) return $p;
    if (strpos($p, '/') === false) $p = 'uploads/posters/' . $p;
    return $p;
}
// Kembalikan path poster hanya jika filenya benar-benar ada
function poster_if_exists(?string $p): ?string {
    $p = poster_rel($p);
    if ($p === null) return null;
    if (preg_match('#^https?://#i', $p)) return $p;
    return file_exists(__DIR__ . '/' . $p) ? $p : null;
}

// Hitung ulang rata-rata + jumlah review (skala 1-5)
function recalc_movie_rating(PDO $pdo, int $movieId): array {
    $st = $pdo->prepare('SELECT COALESCE(ROUND(AVG(rating),2),0) AS avg_rating, COUNT(*) AS total FROM reviews WHERE movie_id = ?');
    $st->execute([$movieId]);
    $s = $st->fetch();
    $up = $pdo->prepare('UPDATE movies SET average_rating = ?, total_reviews = ?, updated_at = NOW() WHERE id = ?');
    $up->execute([$s['avg_rating'], $s['total'], $movieId]);
    return ['average_rating' => (float)$s['avg_rating'], 'total_reviews' => (int)$s['total']];
}