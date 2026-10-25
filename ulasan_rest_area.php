<?php
// File: ulasan_rest_area.php
// Halaman film "Rest Area" (ID 4) VERSI USER: bisa rating & review.
// - Tamu (belum login) : bisa membaca review, diarahkan login untuk menulis review
// - User (sudah login) : bisa memberi rating (1-5 bintang) & review BERKALI-KALI (tanpa batas jumlah / panjang karakter),
//                        mengedit & menghapus review miliknya langsung di daftar "Semua Ulasan", dan menyukai review user lain
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi + session dari config.php (database: movie_review_db)
require __DIR__ . '/config.php';
$pdo = db();
if (session_status() === PHP_SESSION_NONE) session_start();

$isLoggedIn  = !empty($_SESSION['user_id']);
$userId      = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$currentUser = $_SESSION['username'] ?? '';
$homeUrl     = $isLoggedIn ? 'user.php' : 'index.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// ---------- Helper ----------
function cs_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function cs_columnExists(PDO $pdo, $table, $column) {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function cs_getPosterPath($posterFromDB) {
    if (empty($posterFromDB)) return null;
    foreach (array(__DIR__ . '/' . $posterFromDB, $posterFromDB) as $path) {
        if (file_exists($path)) return $posterFromDB;
    }
    return null;
}

// Hitung ulang rata-rata rating film (kolom movies.average_rating dipakai halaman admin)
function cs_refreshMovieRating(PDO $pdo, $movieId) {
    try {
        $pdo->prepare("UPDATE movies SET average_rating = (SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM reviews WHERE movie_id = ?) WHERE id = ?")
            ->execute([$movieId, $movieId]);
    } catch (PDOException $e) { /* abaikan jika kolom tidak ada */ }
}

// Cari tabel daftar tonton yang sudah ada; jika belum ada, buat tabel `watchlist` otomatis
function cs_watchlistTable(PDO $pdo) {
    foreach (array('watchlist', 'daftar_tonton', 'watchlists') as $tbl) {
        if (cs_columnExists($pdo, $tbl, 'user_id') && cs_columnExists($pdo, $tbl, 'movie_id')) return $tbl;
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `watchlist` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_watch_user_movie (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return 'watchlist';
    } catch (PDOException $e) {
        return null;
    }
}

// Izinkan 1 user mengulas film yang sama berkali-kali:
// hapus UNIQUE index di tabel `reviews` yang hanya terdiri dari kolom user_id / movie_id.
// Sebelumnya dibuat index biasa (non-unique) sebagai pengganti, supaya foreign key tetap aman.
function cs_dropUniqueReviewIndex(PDO $pdo) {
    try {
        $rows = $pdo->query("SHOW INDEX FROM `reviews`")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
    $uniq = array();
    foreach ($rows as $r) {
        if ((int)$r['Non_unique'] === 1 || $r['Key_name'] === 'PRIMARY') continue;
        $uniq[$r['Key_name']][] = $r['Column_name'];
    }
    $dropped = false;
    foreach ($uniq as $name => $cols) {
        if (array_diff($cols, array('user_id', 'movie_id'))) continue;   // bukan index user/film, biarkan
        try {
            $newName = 'idx_reviews_' . implode('_', $cols);
            try { $pdo->exec("ALTER TABLE `reviews` ADD INDEX `$newName` (`" . implode('`,`', $cols) . "`)"); } catch (PDOException $e) { /* sudah ada */ }
            $pdo->exec("ALTER TABLE `reviews` DROP INDEX `$name`");
            $dropped = true;
        } catch (PDOException $e) {
            error_log('cs_dropUniqueReviewIndex: ' . $e->getMessage());
        }
    }
    return $dropped;
}

// Cari nama file halaman Daftar Tonton yang SEBENARNYA ada di folder ini (nama file bebas):
// 1) nama yang dicatat saat halaman Daftar Tonton dibuka, 2) nama umum, 3) file apa pun yang namanya mengandung "tonton"
function cs_findWatchlistPage() {
    $candidates = array();
    if (!empty($_SESSION['watchlist_page'])) $candidates[] = $_SESSION['watchlist_page'];
    foreach (array('daftar_tonton.php', 'daftar tonton.php', 'daftar-tonton.php', 'daftartonton.php') as $c) $candidates[] = $c;
    foreach ($candidates as $c) {
        if (basename($c) === $c && is_file(__DIR__ . '/' . $c)) return rawurlencode($c);
    }
    $found = glob(__DIR__ . '/*onton*.php');
    if ($found) return rawurlencode(basename($found[0]));
    return 'daftar_tonton.php';
}

// Tentukan link trailer dari kolom movies.trailer_url (diisi lewat admin): URL penuh atau path file upload
function cs_trailerHref($trailer) {
    $trailer = trim((string)$trailer);
    if ($trailer === '') return null;
    if (preg_match('~^(https?:)?//~i', $trailer)) return $trailer;
    return str_replace(' ', '%20', ltrim(str_replace('\\', '/', $trailer), '/'));
}

// ---------- Ambil film ----------
$movieId = 4;   // ID film Rest Area di tabel movies (sesuaikan jika ID di database Anda berbeda)
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    http_response_code(404);
    die("❌ Film tidak ditemukan!");
}

// Film Coming Soon belum bisa direview
if (($movie['status'] ?? 'released') === 'coming_soon') {
    header('Location: ' . $homeUrl);
    exit;
}

// ---------- Cek fitur opsional di database ----------
$hasUserName     = cs_columnExists($pdo, 'reviews', 'user_name');
$hasCreatedAt    = cs_columnExists($pdo, 'reviews', 'created_at');
$hasUpdatedAt    = cs_columnExists($pdo, 'reviews', 'updated_at');
$hasHelpfulCount = cs_columnExists($pdo, 'reviews', 'helpful_count');
$hasHelpful      = cs_columnExists($pdo, 'review_helpful', 'review_id') && cs_columnExists($pdo, 'review_helpful', 'user_id');

// ---------- Daftar Tonton & Trailer ----------
$watchlistUrl = cs_findWatchlistPage();   // halaman Daftar Tonton milik user (nama file dicari otomatis)
$wlTable      = cs_watchlistTable($pdo);
$trailerHref  = cs_trailerHref($movie['trailer_url'] ?? '');

$pageUrl = 'ulasan_rest_area.php';   // file ini sendiri
$sep = '?';

// ---------- Asal halaman (untuk tombol Kembali) ----------
// Dibuka dari Daftar Tonton  => URL berisi ?from=watchlist  => tombol Kembali ke Daftar Tonton.
// Dibuka dari user.php (biasa) => tanpa parameter           => tombol Kembali ke user.php.
$fromWatchlist = (($_GET['from'] ?? '') === 'watchlist');
$backUrl       = $fromWatchlist ? $watchlistUrl : $homeUrl;
if ($fromWatchlist) {
    // Pertahankan asal halaman di semua form & redirect
    $pageUrl .= (strpos($pageUrl, '?') === false ? '?' : '&') . 'from=watchlist';
    $sep = '&';
}

// ---------- Proses aksi (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isAjax = (($_POST['ajax'] ?? '') === '1');   // permintaan dari fetch() tanpa reload halaman

    if (!$isLoggedIn) {
        header('Location: login.php?error=' . urlencode('Silakan login dulu untuk memberi review'));
        exit;
    }
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Sesi tidak valid, silakan muat ulang halaman.'));
            exit;
        }
        header('Location: ' . $pageUrl . $sep . 'msg=csrf');
        exit;
    }

    try {
        // --- Kirim review BARU (satu user boleh mengirim berkali-kali, tanpa batas karakter) ---
        if ($action === 'add_review') {
            $rating = (int)($_POST['rating'] ?? 0);
            $text   = trim($_POST['review_text'] ?? '');

            if ($rating < 1 || $rating > 5) {
                header('Location: ' . $pageUrl . $sep . 'msg=no_rating#review-form'); exit;
            }

            $cols = array('movie_id', 'user_id', 'rating', 'review_text');
            $vals = array('?', '?', '?', '?');
            $args = array($movieId, $userId, $rating, $text);
            if ($hasUserName)  { $cols[] = 'user_name';  $vals[] = '?';     $args[] = $currentUser; }
            if ($hasCreatedAt) { $cols[] = 'created_at'; $vals[] = 'NOW()'; }
            if ($hasUpdatedAt) { $cols[] = 'updated_at'; $vals[] = 'NOW()'; }
            $insertSql = "INSERT INTO reviews (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
            try {
                $pdo->prepare($insertSql)->execute($args);
            } catch (PDOException $ie) {
                // Ditolak karena UNIQUE index (1 review per user per film)? Hapus index itu lalu coba lagi sekali.
                if ((string)$ie->getCode() === '23000' && strpos($ie->getMessage(), 'Duplicate') !== false && cs_dropUniqueReviewIndex($pdo)) {
                    $pdo->prepare($insertSql)->execute($args);
                } else {
                    throw $ie;
                }
            }

            cs_refreshMovieRating($pdo, $movieId);
            header('Location: ' . $pageUrl . $sep . 'msg=saved#reviews');
            exit;
        }

        // --- Edit review milik sendiri (dari daftar "Semua Ulasan") ---
        if ($action === 'edit_review') {
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $rating   = (int)($_POST['rating'] ?? 0);
            $text     = trim($_POST['review_text'] ?? '');

            if ($rating < 1 || $rating > 5) {
                header('Location: ' . $pageUrl . $sep . 'msg=no_rating#review-' . $reviewId); exit;
            }

            $set = "rating = ?, review_text = ?" . ($hasUpdatedAt ? ", updated_at = NOW()" : "");
            $pdo->prepare("UPDATE reviews SET $set WHERE id = ? AND user_id = ? AND movie_id = ?")
                ->execute([$rating, $text, $reviewId, $userId, $movieId]);

            cs_refreshMovieRating($pdo, $movieId);
            header('Location: ' . $pageUrl . $sep . 'msg=updated#review-' . $reviewId);
            exit;
        }

        // --- Hapus review milik sendiri ---
        if ($action === 'delete_review') {
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ? AND movie_id = ?")
                ->execute([$reviewId, $userId, $movieId]);
            if ($hasHelpful) {
                try { $pdo->prepare("DELETE FROM review_helpful WHERE review_id = ?")->execute([$reviewId]); } catch (PDOException $e) {}
            }
            cs_refreshMovieRating($pdo, $movieId);
            header('Location: ' . $pageUrl . $sep . 'msg=deleted#reviews');
            exit;
        }

        // --- Tambah / keluarkan film dari Daftar Tonton ---
        if ($action === 'toggle_watchlist') {
            if (!$wlTable) {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(array('ok' => false, 'message' => 'Daftar Tonton tidak dapat diakses.'));
                    exit;
                }
                header('Location: ' . $pageUrl . $sep . 'msg=error#watch-actions'); exit;
            }
            $ex = $pdo->prepare("SELECT 1 FROM `$wlTable` WHERE user_id = ? AND movie_id = ? LIMIT 1");
            $ex->execute([$userId, $movieId]);
            if ($ex->fetchColumn()) {
                $pdo->prepare("DELETE FROM `$wlTable` WHERE user_id = ? AND movie_id = ?")->execute([$userId, $movieId]);
                $msg = 'wl_removed';
            } else {
                $pdo->prepare("INSERT INTO `$wlTable` (user_id, movie_id) VALUES (?, ?)")->execute([$userId, $movieId]);
                $msg = 'wl_added';
            }
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(array(
                    'ok'      => true,
                    'in'      => ($msg === 'wl_added'),
                    'message' => ($msg === 'wl_added') ? 'Film ini sudah masuk ke Daftar Tonton Anda.' : 'Film ini dikeluarkan dari Daftar Tonton Anda.',
                ));
                exit;
            }
            header('Location: ' . $pageUrl . $sep . 'msg=' . $msg . '#watch-actions');
            exit;
        }

        // --- Like / batal like review user lain ---
        if ($action === 'toggle_like' && $hasHelpful) {
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $own = $pdo->prepare("SELECT user_id FROM reviews WHERE id = ? AND movie_id = ?");
            $own->execute([$reviewId, $movieId]);
            $ownerId = $own->fetchColumn();

            if ($ownerId !== false && (int)$ownerId !== $userId) {
                $ex = $pdo->prepare("SELECT 1 FROM review_helpful WHERE review_id = ? AND user_id = ?");
                $ex->execute([$reviewId, $userId]);
                if ($ex->fetchColumn()) {
                    $pdo->prepare("DELETE FROM review_helpful WHERE review_id = ? AND user_id = ?")->execute([$reviewId, $userId]);
                } else {
                    $pdo->prepare("INSERT INTO review_helpful (review_id, user_id) VALUES (?, ?)")->execute([$reviewId, $userId]);
                }
                if ($hasHelpfulCount) {
                    $pdo->prepare("UPDATE reviews SET helpful_count = (SELECT COUNT(*) FROM review_helpful WHERE review_id = ?) WHERE id = ?")
                        ->execute([$reviewId, $reviewId]);
                }
            }
            header('Location: ' . $pageUrl . '#review-' . $reviewId);
            exit;
        }
    } catch (PDOException $e) {
        error_log('ulasan_rest_area.php: ' . $e->getMessage());
        // SQLSTATE 23000 = pelanggaran UNIQUE index (database masih membatasi 1 review per user per film)
        if (!empty($isAjax)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Terjadi kesalahan, silakan coba lagi.'));
            exit;
        }
        $errMsg = (strpos($e->getMessage(), 'Duplicate') !== false) ? 'dup' : 'error';
        header('Location: ' . $pageUrl . $sep . 'msg=' . $errMsg . '#reviews');
        exit;
    }

    header('Location: ' . $pageUrl);
    exit;
}

// ---------- Ambil review (semua review, terbaru di atas) ----------
$likeSelect = $hasHelpful
    ? ", (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id) AS like_count,
         (SELECT COUNT(*) FROM review_helpful rh2 WHERE rh2.review_id = r.id AND rh2.user_id = :uid2) AS liked_by_me"
    : ", 0 AS like_count, 0 AS liked_by_me";
$sql = "SELECT r.*, COALESCE(u.full_name, u.username, r.user_name, 'Pengguna CineScope') AS display_name $likeSelect
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE r.movie_id = :mid
        ORDER BY r.id DESC";
$params = array(':mid' => $movieId);
if ($hasHelpful) $params[':uid2'] = $userId;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    // Fallback jika kolom full_name / user_name tidak ada di database
    $sql = str_replace("COALESCE(u.full_name, u.username, r.user_name, 'Pengguna CineScope')", "COALESCE(u.username, 'Pengguna CineScope')", $sql);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalReviews = count($reviews);
$sumRating = 0;
foreach ($reviews as $rv) {
    $sumRating += (int)$rv['rating'];
}
$avgRating  = $totalReviews ? $sumRating / $totalReviews : 0;
$posterPath = cs_getPosterPath($movie['poster'] ?? '');

// Apakah film ini sudah ada di Daftar Tonton user?
$inWatchlist = false;
if ($isLoggedIn && $wlTable) {
    $w = $pdo->prepare("SELECT 1 FROM `$wlTable` WHERE user_id = ? AND movie_id = ? LIMIT 1");
    $w->execute([$userId, $movieId]);
    $inWatchlist = (bool)$w->fetchColumn();
}

// Pesan notifikasi
$messages = array(
    'saved'     => array('success', 'Terima kasih! Ulasan dan rating Anda berhasil dikirim.'),
    'updated'   => array('success', 'Ulasan dan rating Anda berhasil diperbarui.'),
    'deleted'   => array('success', 'Ulasan Anda sudah dihapus.'),
    'wl_added'  => array('success', 'Film ini sudah masuk ke Daftar Tonton Anda.'),
    'wl_removed'=> array('removed', 'Film ini dikeluarkan dari Daftar Tonton Anda.'),
    'no_rating' => array('error',   'Pilih rating bintang 1 sampai 5 terlebih dahulu.'),
    'csrf'      => array('error',   'Sesi tidak valid, silakan coba lagi.'),
    'dup'       => array('error',   'Database masih membatasi 1 ulasan per user per film dan index-nya tidak bisa dihapus otomatis. Hapus UNIQUE index (user_id, movie_id) di tabel reviews lewat phpMyAdmin (tab Structure > Indexes).'),
    'error'     => array('error',   'Terjadi kesalahan, silakan coba lagi.'),
);
$flash = $messages[$_GET['msg'] ?? ''] ?? null;

$metaParts = array_filter(array(
    $movie['release_year'] ?? '',
    $movie['genre'] ?? '',
    $movie['duration'] ?? '',
), function ($v) { return trim((string)$v) !== ''; });
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Ulasan Saya - <?= cs_e($movie['title']) ?> | CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #e50914; --background-dark: #0a0a0a; --card-dark: #1a1a1a; --text-light: #ffffff; --star-gold: #ffc107; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--background-dark); color: var(--text-light); line-height: 1.6; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; background-color: var(--card-dark); border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .back-to-home { display: inline-block; background-color: #333; color: var(--text-light); padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-bottom: 20px; transition: background-color 0.3s; cursor: pointer; border: none; font-weight: 600; font-family: inherit; font-size: 1em; }
        .back-to-home:hover { background-color: #555; }
        .movie-header { display: flex; gap: 20px; margin-bottom: 30px; align-items: flex-start; }
        .movie-poster { width: 150px; height: 225px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .poster-placeholder { width: 150px; height: 225px; background: linear-gradient(135deg, #333 0%, #555 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 3em; color: #888; }
        .movie-info h1 { color: var(--primary-color); margin-bottom: 5px; font-size: 2em; }
        .movie-info p { color: #ccc; margin-bottom: 10px; }
        .rating-summary { display: flex; align-items: center; gap: 15px; margin-top: 15px; padding: 10px 0; border-top: 1px solid #333; }
        .rating-box { background-color: #333; padding: 8px 15px; border-radius: 5px; text-align: center; }
        .rating-box .score { font-size: 1.8em; font-weight: 700; color: var(--star-gold); line-height: 1; }
        .rating-box .count { font-size: 0.8em; color: #aaa; margin-top: 2px; }
        .my-reviews-section { margin-bottom: 40px; }
        .my-reviews-section h2 { font-size: 1.8em; color: var(--text-light); margin-bottom: 25px; border-bottom: 2px solid var(--star-gold); padding-bottom: 5px; }
        .review-item { background-color: #333; padding: 15px 20px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid var(--primary-color); }
        .review-item.mine { border-left-color: var(--star-gold); }
        .rating-display { margin-bottom: 10px; }
        .review-item p { margin-bottom: 10px; font-size: 1em; color: #e0e0e0; white-space: pre-line; word-break: break-word; }
        .reviewer-info { font-size: 0.85em; color: #aaa; text-align: right; border-top: 1px dashed #555; padding-top: 5px; }
        .badge-mine { background: var(--star-gold); color: #000; font-size: 0.7em; font-weight: 700; padding: 1px 8px; border-radius: 10px; margin-left: 6px; }

        /* ---- Form rating & review ---- */
        .flash { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .flash.success { background: rgba(40,167,69,.15); border: 1px solid #28a745; color: #6fdc8c; }
        .flash.error   { background: rgba(229,9,20,.15);  border: 1px solid var(--primary-color); color: #ff6b73; }
        .flash.removed { background: rgba(229,9,20,.15);  border: 1px solid var(--primary-color); color: #ff6b73; }
        .review-form { background-color: #333; padding: 20px; border-radius: 6px; border-left: 5px solid var(--star-gold); margin-bottom: 15px; }
        .review-form label.title { display: block; font-weight: 600; margin-bottom: 8px; }
        .form-hint { color: #aaa; font-size: 0.85em; margin-bottom: 15px; }
        .star-input { display: inline-flex; flex-direction: row-reverse; justify-content: flex-end; margin-bottom: 15px; }
        .star-input input { display: none; }
        .star-input label { font-size: 2.2rem; color: #555; cursor: pointer; padding: 0 3px; transition: color .15s, transform .15s; line-height: 1; }
        .star-input label:hover, .star-input label:hover ~ label, .star-input input:checked ~ label { color: var(--star-gold); }
        .star-input label:hover { transform: scale(1.15); }
        .review-form textarea, .edit-form textarea { width: 100%; min-height: 120px; padding: 12px; background: #1a1a1a; border: 1px solid #555; border-radius: 6px; color: var(--text-light); font-family: inherit; font-size: 1em; resize: vertical; }
        .review-form textarea:focus, .edit-form textarea:focus { outline: none; border-color: var(--star-gold); }
        .form-actions { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 8px 18px; border-radius: 5px; border: none; font-weight: 600; font-family: inherit; font-size: 0.95em; cursor: pointer; text-decoration: none; color: #fff; transition: background-color .3s; }
        .btn-primary { background: var(--primary-color); }
        .btn-primary:hover { background: #b20710; }
        .btn-secondary { background: #555; }
        .btn-secondary:hover { background: #777; }
        .btn-danger { background: #555; }
        .btn-danger:hover { background: #8a1c1c; }
        .btn-edit { background: #555; }
        .btn-edit:hover { background: var(--star-gold); color: #000; }
        .btn-like { background: #444; padding: 4px 12px; font-size: 0.85em; }
        .btn-like.liked { background: var(--primary-color); }
        .btn-like:hover { background: #666; }
        .btn-like.liked:hover { background: #b20710; }
        .login-prompt { background: #333; padding: 20px; border-radius: 6px; border-left: 5px solid var(--star-gold); margin-bottom: 15px; color: #ccc; }
        .login-prompt a { color: var(--primary-color); font-weight: 700; }
        .review-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; border-top: 1px dashed #555; padding-top: 8px; }
        .review-footer .reviewer-info { border-top: none; padding-top: 0; text-align: right; flex: 1; }
        .review-footer form { display: inline; }
        .review-actions { display: flex; gap: 8px; align-items: center; }
        .movie-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        .movie-actions form { display: inline; }
        .btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 1em; border-radius: 6px; }
        .btn-trailer { background: var(--primary-color); }
        .btn-trailer:hover { background: #b20710; }
        .btn-trailer.disabled { background: #444; color: #888; cursor: not-allowed; pointer-events: none; }
        .btn-watch { background: transparent; border: 2px solid var(--star-gold); color: var(--star-gold); padding: 8px 18px; }
        .btn-watch:hover { background: var(--star-gold); color: #000; }
        .btn-watch.active { background: var(--star-gold); color: #000; }
        .btn-watch.active:hover { background: #e0a800; }
        .toast { position: fixed; top: 20px; right: 20px; color: #fff; padding: 14px 22px; border-radius: 10px; z-index: 1000; font-weight: 600; transition: opacity .5s ease; }
        .toast.success { background: linear-gradient(135deg, #27ae60, #229954); box-shadow: 0 5px 20px rgba(39,174,96,.4); }
        .toast.error   { background: linear-gradient(135deg, #e50914, #8b0000); box-shadow: 0 5px 20px rgba(229,9,20,.4); }
        .toast.removed { background: linear-gradient(135deg, #e50914, #8b0000); box-shadow: 0 5px 20px rgba(229,9,20,.4); }
        .toast i { margin-right: 8px; }
        .edit-form { display: none; }
        .edit-form .edit-title { font-weight: 600; margin-bottom: 8px; color: var(--star-gold); }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-to-home" href="<?= cs_e($backUrl) ?>"><i class="fas fa-arrow-left"></i> Kembali</a>

        <section class="movie-header">
            <?php if ($posterPath): ?>
                <img src="<?= cs_e($posterPath) ?>" alt="Poster" class="movie-poster">
            <?php else: ?>
                <div class="poster-placeholder"><i class="fas fa-film"></i></div>
            <?php endif; ?>
            <div class="movie-info">
                <h1><?= cs_e($movie['title']) ?></h1>
                <p><?= cs_e(implode(' • ', $metaParts)) ?></p>
                <?php if (!empty($movie['synopsis'])): ?><p><?= cs_e($movie['synopsis']) ?></p><?php endif; ?>
                <div class="rating-summary">
                    <div class="rating-box"><div class="score"><?= $totalReviews > 0 ? number_format($avgRating, 1) . '/5' : '-' ?></div><div class="count">Rata-Rata</div></div>
                    <div class="rating-box"><div class="score"><?= $totalReviews ?></div><div class="count">Total Ulasan</div></div>
                </div>

                <!-- Tombol Trailer & Daftar Tonton -->
                <div class="movie-actions" id="watch-actions">
                    <?php if ($trailerHref): ?>
                        <a class="btn btn-trailer btn-action" href="<?= cs_e($trailerHref) ?>" target="_blank" rel="noopener"><i class="fas fa-play"></i> Trailer</a>
                    <?php else: ?>
                        <span class="btn btn-trailer btn-action disabled" title="Trailer belum diunggah oleh admin"><i class="fas fa-play"></i> Trailer</span>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <form method="post" action="<?= cs_e($pageUrl) ?>" id="watchForm">
                            <input type="hidden" name="action" value="toggle_watchlist">
                            <input type="hidden" name="csrf" value="<?= cs_e($csrf) ?>">
                            <button type="submit" id="watchBtn" class="btn btn-watch btn-action<?= $inWatchlist ? ' active' : '' ?>">
                                <?php if ($inWatchlist): ?><i class="fas fa-check"></i> Di Daftar Tonton<?php else: ?><i class="fas fa-plus"></i> Daftar Tonton<?php endif; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <a class="btn btn-watch btn-action" href="login.php"><i class="fas fa-plus"></i> Daftar Tonton</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="flash <?= $flash[0] ?>"><?= cs_e($flash[1]) ?></div>
        <?php endif; ?>

        <!-- ===== Form rating & review (selalu kosong, untuk ulasan baru) ===== -->
        <section class="my-reviews-section" id="review-form">
            <h2><i class="fas fa-user-check"></i> Ulasan Saya</h2>
            <?php if ($isLoggedIn): ?>
                <form class="review-form" method="post" action="<?= cs_e($pageUrl) ?>">
                    <input type="hidden" name="action" value="add_review">
                    <input type="hidden" name="csrf" value="<?= cs_e($csrf) ?>">

                    <p class="form-hint">Anda boleh mengirim ulasan dan rating lebih dari satu kali untuk film ini. Untuk mengubah ulasan yang sudah ada, gunakan tombol Edit di bagian "Semua Ulasan".</p>

                    <label class="title">Rating Anda</label>
                    <div class="star-input">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
                            <label for="star<?= $i ?>" title="<?= $i ?> bintang"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>

                    <label class="title" for="review_text">Ulasan Anda</label>
                    <textarea id="review_text" name="review_text" placeholder="Ceritakan pendapat Anda tentang film ini..."></textarea>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Ulasan</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="login-prompt">
                    <i class="fas fa-lock"></i> Anda harus <a href="login.php">login</a> terlebih dahulu untuk memberi rating dan menulis ulasan.
                </div>
            <?php endif; ?>
        </section>

        <!-- ===== Daftar ulasan ===== -->
        <section class="my-reviews-section" id="reviews">
            <h2><i class="fas fa-comments"></i> Semua Ulasan</h2>
            <div id="my-reviews-display">
                <?php if ($totalReviews > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php $isMine = $isLoggedIn && (int)$review['user_id'] === $userId; $rid = (int)$review['id']; ?>
                        <div class="review-item<?= $isMine ? ' mine' : '' ?>" id="review-<?= $rid ?>">

                            <!-- Tampilan normal -->
                            <div id="view-<?= $rid ?>">
                                <div class="rating-display">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?= $i <= (int)$review['rating'] ? 'var(--star-gold)' : '#444' ?>; font-size: 1.1rem;"></i>
                                    <?php endfor; ?>
                                </div>
                                <p><?= cs_e(($review['review_text'] !== '' && $review['review_text'] !== null) ? $review['review_text'] : '(Tidak ada ulasan tertulis)') ?></p>
                                <div class="review-footer">
                                    <div class="review-actions">
                                        <?php if ($isMine): ?>
                                            <button type="button" class="btn btn-edit btn-like" onclick="toggleEdit(<?= $rid ?>, true)"><i class="fas fa-pen"></i> Edit</button>
                                            <form method="post" action="<?= cs_e($pageUrl) ?>" onsubmit="return confirm('Hapus ulasan ini?');">
                                                <input type="hidden" name="action" value="delete_review">
                                                <input type="hidden" name="csrf" value="<?= cs_e($csrf) ?>">
                                                <input type="hidden" name="review_id" value="<?= $rid ?>">
                                                <button type="submit" class="btn btn-danger btn-like"><i class="fas fa-trash"></i> Hapus</button>
                                            </form>
                                        <?php elseif ($hasHelpful): ?>
                                            <?php if ($isLoggedIn): ?>
                                                <form method="post" action="<?= cs_e($pageUrl) ?>">
                                                    <input type="hidden" name="action" value="toggle_like">
                                                    <input type="hidden" name="csrf" value="<?= cs_e($csrf) ?>">
                                                    <input type="hidden" name="review_id" value="<?= $rid ?>">
                                                    <button type="submit" class="btn btn-like<?= !empty($review['liked_by_me']) ? ' liked' : '' ?>"><i class="fas fa-thumbs-up"></i> <?= (int)$review['like_count'] ?></button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:#aaa; font-size:.85em;"><i class="fas fa-thumbs-up"></i> <?= (int)$review['like_count'] ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reviewer-info">
                                        Oleh: <?= cs_e($review['display_name']) ?><?php if ($isMine): ?><span class="badge-mine">Anda</span><?php endif; ?>
                                        <?php if (!empty($review['created_at'])): ?> - <?= date('d M Y, H:i', strtotime($review['created_at'])) ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Form edit (hanya untuk review milik sendiri, tersembunyi sampai tombol Edit ditekan) -->
                            <?php if ($isMine): ?>
                            <form class="edit-form" id="edit-<?= $rid ?>" method="post" action="<?= cs_e($pageUrl) ?>">
                                <input type="hidden" name="action" value="edit_review">
                                <input type="hidden" name="csrf" value="<?= cs_e($csrf) ?>">
                                <input type="hidden" name="review_id" value="<?= $rid ?>">

                                <div class="edit-title"><i class="fas fa-pen"></i> Edit Ulasan</div>
                                <div class="star-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" id="e<?= $rid ?>s<?= $i ?>" value="<?= $i ?>" <?= (int)$review['rating'] === $i ? 'checked' : '' ?>>
                                        <label for="e<?= $rid ?>s<?= $i ?>" title="<?= $i ?> bintang"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                                <textarea name="review_text" placeholder="Tulis ulasan Anda..."><?= cs_e($review['review_text']) ?></textarea>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Simpan Perubahan</button>
                                    <button type="button" class="btn btn-secondary" onclick="toggleEdit(<?= $rid ?>, false)">Batal</button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#777; font-style: italic;">Belum ada ulasan untuk film ini. Jadilah yang pertama!</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        // Notifikasi kecil di pojok layar (tidak menggeser tampilan halaman)
        function showToast(type, message) {
            var old = document.getElementById('toast');
            if (old) old.remove();
            var el = document.createElement('div');
            el.id = 'toast';
            el.className = 'toast ' + type;
            var icon = document.createElement('i');
            icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : (type === 'removed' ? 'fa-trash-alt' : 'fa-exclamation-circle'));
            el.appendChild(icon);
            el.appendChild(document.createTextNode(message));
            document.body.appendChild(el);
            setTimeout(function () {
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 500);
            }, 3500);
        }

        // Tombol Daftar Tonton: kirim lewat fetch() supaya halaman TIDAK reload / scroll ke atas
        (function () {
            var form = document.getElementById('watchForm');
            if (!form || !window.fetch) return;
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('watchBtn');
                var data = new FormData(form);
                data.append('ajax', '1');
                btn.disabled = true;
                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btn.disabled = false;
                    if (!res.ok) { showToast('error', res.message || 'Terjadi kesalahan.'); return; }
                    if (res.in) {
                        btn.classList.add('active');
                        btn.innerHTML = '<i class="fas fa-check"></i> Di Daftar Tonton';
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = '<i class="fas fa-plus"></i> Daftar Tonton';
                    }
                    showToast(res.in ? 'success' : 'removed', res.message);   // hijau = ditambahkan, merah = dikeluarkan
                })
                .catch(function () {
                    // Jika fetch gagal, kirim form biasa (fallback)
                    form.submit();
                });
            });
        })();

        // Tampilkan / sembunyikan form edit pada satu ulasan
        function toggleEdit(id, show) {
            var view = document.getElementById('view-' + id);
            var form = document.getElementById('edit-' + id);
            if (!view || !form) return;
            view.style.display = show ? 'none' : '';
            form.style.display = show ? 'block' : 'none';
            if (show) {
                var ta = form.querySelector('textarea');
                if (ta) { ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); }
            }
        }
    </script>
</body>
</html>