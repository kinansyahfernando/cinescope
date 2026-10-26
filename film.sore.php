<?php
// File: film.sore.php
// Halaman film "Sore" (ID 1) - UI disamakan dengan "Dilan 1990.php" / "rest area.php"
// Layout: header film (Suka, Daftar Tonton, Lihat Semua Ulasan, rating box, Tonton Trailer)
//         -> form "Berikan Ulasan dan Rating Anda" -> daftar "Semua Ulasan"
// Backend: tetap memakai config.php (db(), session), login wajib untuk menulis review,
//          CSRF, dan Daftar Tonton berbasis database seperti versi sebelumnya.
// Film lain dari admin juga bisa dibuka lewat file ini dengan ?id=ID_FILM

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Izinkan 1 user mengulas film yang sama berkali-kali (hapus UNIQUE index user_id/movie_id di tabel reviews)
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
        if (array_diff($cols, array('user_id', 'movie_id'))) continue;
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

// Cari nama file halaman Daftar Tonton yang SEBENARNYA ada di folder ini
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

// Link trailer dari kolom movies.trailer_url (diisi lewat admin): URL penuh atau path file upload
function cs_trailerHref($trailer) {
    $trailer = trim((string)$trailer);
    if ($trailer === '') return '';
    if (preg_match('~^(https?:)?//~i', $trailer)) return $trailer;
    return str_replace(' ', '%20', ltrim(str_replace('\\', '/', $trailer), '/'));
}

// Render daftar ulasan (format sama seperti Dilan 1990.php)
function cs_reviewsHtml(array $reviews) {
    ob_start();
    if (count($reviews) > 0):
        foreach ($reviews as $review): ?>
            <div class="review-item">
                <div class="rating-display">
                    <?php
                    $ratingValue = (int)$review['rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        $color = $i <= $ratingValue ? '#ffc107' : '#444';
                        echo '<i class="fas fa-star" style="color: ' . $color . '; font-size: 1.1rem;"></i>';
                    }
                    ?>
                </div>
                <p><?= cs_e(($review['review_text'] !== '' && $review['review_text'] !== null) ? $review['review_text'] : '(Tidak ada ulasan tertulis)') ?></p>
                <div class="reviewer-info">
                    Oleh: <?= cs_e($review['display_name']) ?>
                    <?php if (!empty($review['created_at'])): ?>
                        - <?= date('d M Y, H:i', strtotime($review['created_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach;
    else: ?>
        <p style="color:#777; font-style: italic;">Belum ada ulasan untuk film ini.</p>
    <?php endif;
    return ob_get_clean();
}

function cs_json($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ---------- Ambil film ----------
// Default: film Sore (ID 1). Film lain dibuka lewat ?id=ID_FILM
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    http_response_code(404);
    die('<!DOCTYPE html><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Film tidak ditemukan - CineScope</title><body style="font-family:sans-serif;background:#0a0a0a;color:#fff;text-align:center;padding:80px 20px"><h1>Film tidak ditemukan</h1><p style="color:#aaa;margin:12px 0 20px">Film yang kamu cari tidak ada atau sudah dihapus.</p><a style="color:#e50914;font-weight:700" href="' . cs_e($homeUrl) . '">&larr; Kembali ke beranda</a></body>');
}

// Film Coming Soon belum bisa direview
if (($movie['status'] ?? 'released') === 'coming_soon') {
    header('Location: ' . $homeUrl);
    exit;
}

// ---------- Data tampilan ----------
$title       = $movie['title'] ?? '';
$posterPath  = cs_getPosterPath($movie['poster'] ?? '');
$releaseYear = $movie['release_year'] ?? '';
$genre       = $movie['genre'] ?? '';
$duration    = $movie['duration'] ?? '';
if (is_numeric($duration) && $duration !== '') $duration .= ' menit';
$director    = trim((string)($movie['director'] ?? ''));
$cast        = trim((string)($movie['cast'] ?? ''));
$synopsis    = trim((string)($movie['synopsis'] ?? ''));
$trailerHref = cs_trailerHref($movie['trailer_url'] ?? '');

$wlTable      = cs_watchlistTable($pdo);
$watchlistUrl = cs_findWatchlistPage();

// ---------- Asal halaman (tombol Kembali) ----------
$fromWatchlist = (($_GET['from'] ?? '') === 'watchlist');
$backUrl       = $fromWatchlist ? $watchlistUrl : $homeUrl;

$selfFile = basename($_SERVER['PHP_SELF']);
$selfUrl  = rawurlencode($selfFile) . '?id=' . $movieId . ($fromWatchlist ? '&from=watchlist' : '');

// Halaman "Lihat Semua Ulasan"
$allReviewsLink = 'ulasan_sore.php?id=' . $movieId . ($fromWatchlist ? '&from=watchlist' : '');

// ---------- Cek fitur opsional di database ----------
$hasUserName  = cs_columnExists($pdo, 'reviews', 'user_name');
$hasCreatedAt = cs_columnExists($pdo, 'reviews', 'created_at');
$hasUpdatedAt = cs_columnExists($pdo, 'reviews', 'updated_at');

// ---------- Proses aksi AJAX (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$isLoggedIn) {
        cs_json(array('success' => false, 'login' => true, 'message' => 'Silakan login dulu untuk memberi review atau menyukai film.'));
    }
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        cs_json(array('success' => false, 'message' => 'Sesi tidak valid, silakan muat ulang halaman.'));
    }

    try {
        // --- Kirim review BARU ---
        if ($action === 'add_review') {
            $rating = (int)($_POST['rating'] ?? 0);
            $text   = trim($_POST['reviewText'] ?? '');

            if ($rating < 1 || $rating > 5) {
                cs_json(array('success' => false, 'message' => 'Rating harus diisi antara 1 sampai 5 bintang!'));
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
                // Ditolak karena UNIQUE index? Hapus index itu lalu coba lagi sekali.
                if ((string)$ie->getCode() === '23000' && strpos($ie->getMessage(), 'Duplicate') !== false && cs_dropUniqueReviewIndex($pdo)) {
                    $pdo->prepare($insertSql)->execute($args);
                } else {
                    throw $ie;
                }
            }

            cs_refreshMovieRating($pdo, $movieId);
            cs_json(array('success' => true, 'message' => 'Ulasan Anda berhasil ditambahkan!'));
        }

        // --- Tambah / keluarkan film dari Daftar Tonton (tombol Suka) ---
        if ($action === 'toggle_watchlist') {
            if (!$wlTable) {
                cs_json(array('success' => false, 'message' => 'Daftar Tonton tidak dapat diakses.'));
            }
            $ex = $pdo->prepare("SELECT 1 FROM `$wlTable` WHERE user_id = ? AND movie_id = ? LIMIT 1");
            $ex->execute([$userId, $movieId]);
            if ($ex->fetchColumn()) {
                $pdo->prepare("DELETE FROM `$wlTable` WHERE user_id = ? AND movie_id = ?")->execute([$userId, $movieId]);
                $in = false;
            } else {
                $pdo->prepare("INSERT INTO `$wlTable` (user_id, movie_id) VALUES (?, ?)")->execute([$userId, $movieId]);
                $in = true;
            }
            cs_json(array(
                'success' => true,
                'in'      => $in,
                'message' => $in
                    ? 'Film "' . $title . '" ditambahkan ke Daftar Tonton!'
                    : 'Film "' . $title . '" dihapus dari Daftar Tonton.',
            ));
        }

        cs_json(array('success' => false, 'message' => 'Aksi tidak dikenal.'));

    } catch (PDOException $e) {
        error_log('film.sore.php: ' . $e->getMessage());
        cs_json(array('success' => false, 'message' => 'Terjadi kesalahan, silakan coba lagi.'));
    }
}

// ---------- Ambil semua ulasan (terbaru di atas) ----------
$sql = "SELECT r.*, COALESCE(u.full_name, u.username, r.user_name, 'Pengguna CineScope') AS display_name
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE r.movie_id = :mid
        ORDER BY r.id DESC";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':mid' => $movieId));
} catch (PDOException $e) {
    // Fallback jika kolom full_name / user_name tidak ada di database
    $sql = str_replace("COALESCE(u.full_name, u.username, r.user_name, 'Pengguna CineScope')", "COALESCE(u.username, 'Pengguna CineScope')", $sql);
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':mid' => $movieId));
}
$allReviews   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalReviews = count($allReviews);
$avgRating    = $totalReviews > 0 ? array_sum(array_column($allReviews, 'rating')) / $totalReviews : 0;
$formattedAvgRating = $avgRating > 0 ? number_format($avgRating, 1) : '0.0';

// Permintaan refresh data ulasan tanpa reload halaman
if (isset($_GET['fetch_data']) && $_GET['fetch_data'] == 1) {
    cs_json(array(
        'averageRating' => $formattedAvgRating,
        'totalReviews'  => $totalReviews,
        'myReviewsHtml' => cs_reviewsHtml($allReviews),
    ));
}

// Apakah film ini sudah ada di Daftar Tonton user?
$inWatchlist = false;
if ($isLoggedIn && $wlTable) {
    $w = $pdo->prepare("SELECT 1 FROM `$wlTable` WHERE user_id = ? AND movie_id = ? LIMIT 1");
    $w->execute([$userId, $movieId]);
    $inWatchlist = (bool)$w->fetchColumn();
}

$metaLine = implode(' • ', array_filter(array((string)$releaseYear, (string)$genre, (string)$duration), function ($v) { return trim($v) !== ''; }));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Beri Review Film <?= cs_e($title) ?> - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e50914;
            --background-dark: #0a0a0a;
            --card-dark: #1a1a1a;
            --text-light: #ffffff;
            --star-gold: #ffc107;
            --input-bg: #2d2d2d;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background-color: var(--background-dark); color: var(--text-light); line-height: 1.6; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; background-color: var(--card-dark); border-radius: 12px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.5); }
        .back-to-home { display: inline-block; background-color: #333; color: var(--text-light); padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-bottom: 20px; transition: background-color 0.3s; cursor: pointer; border: none; font-weight: 600; font-family: inherit; font-size: 1em; }
        .back-to-home:hover { background-color: #555; }
        .movie-header { display: flex; gap: 20px; margin-bottom: 30px; align-items: flex-start; }
        .movie-poster { width: 150px; height: 225px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .movie-info h1 { color: var(--primary-color); margin-bottom: 5px; font-size: 2em; }
        .movie-info p { color: #ccc; margin-bottom: 10px; }
        .rating-summary { display: flex; align-items: center; gap: 15px; margin-top: 15px; padding: 10px 0; border-top: 1px solid #333; }
        .rating-box { background-color: #333; padding: 8px 15px; border-radius: 5px; text-align: center; }
        .rating-box .score { font-size: 1.8em; font-weight: 700; color: var(--star-gold); line-height: 1; }
        .rating-box .count { font-size: 0.8em; color: #aaa; margin-top: 2px; }
        .review-form-section { margin-bottom: 40px; padding: 20px; border: 1px solid #333; border-radius: 8px; }
        .review-form-section h2 { font-size: 1.8em; color: var(--primary-color); margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-light); }
        textarea { width: 100%; min-height: 120px; padding: 10px; border-radius: 4px; border: 1px solid #444; background-color: var(--input-bg); color: var(--text-light); resize: vertical; font-family: inherit; font-size: 1em; }
        .submit-btn { background-color: var(--primary-color); color: var(--text-light); padding: 10px 25px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; font-family: inherit; font-size: 1em; }
        .submit-btn:hover { background-color: #f00; }
        .submit-btn:disabled { opacity: .7; cursor: not-allowed; }
        .like-btn { background-color: #3498db; transition: background-color 0.3s; border: none; padding: 10px 15px; }
        .like-btn:hover { background-color: #2980b9; }
        .liked { background-color: var(--primary-color); }
        .liked:hover { background-color: #c0392b; }
        .review-link { background-color: #2ecc71; }
        .review-link:hover { background-color: #27ae60; }
        .trailer-btn { background-color: #9b59b6; }
        .trailer-btn:hover { background-color: #8e44ad; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5em; color: #444; cursor: pointer; padding: 0 5px; transition: color 0.2s; }
        .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: var(--star-gold); }
        .my-reviews-section { margin-bottom: 40px; }
        .my-reviews-section h2 { font-size: 1.8em; color: var(--text-light); margin-bottom: 25px; border-bottom: 2px solid var(--star-gold); padding-bottom: 5px; }
        .review-item { background-color: #333; padding: 15px 20px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid var(--primary-color); }
        .rating-display { margin-bottom: 10px; }
        .review-item p { margin-bottom: 10px; font-size: 1em; color: #e0e0e0; white-space: pre-line; word-break: break-word; }
        .reviewer-info { font-size: 0.85em; color: #aaa; text-align: right; border-top: 1px dashed #555; padding-top: 5px; }
        .login-prompt { background: #333; padding: 20px; border-radius: 6px; border-left: 5px solid var(--star-gold); color: #ccc; }
        .login-prompt a { color: var(--primary-color); font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-to-home" href="<?= cs_e($backUrl) ?>">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <section class="movie-header">
            <?php if ($posterPath): ?>
                <img src="<?= cs_e($posterPath) ?>" alt="Poster Film <?= cs_e($title) ?>" class="movie-poster">
            <?php else: ?>
                <div class="movie-poster" style="background: linear-gradient(135deg, #333 0%, #555 100%); display:flex; align-items:center; justify-content:center; font-size:3em; color:#888;"><i class="fas fa-film"></i></div>
            <?php endif; ?>

            <div class="movie-info">
                <h1><?= cs_e($title) ?></h1>
                <p><?= cs_e($metaLine) ?></p>

                <?php if ($director !== '' || $cast !== ''): ?>
                    <p><?php
                        $parts = array();
                        if ($director !== '') $parts[] = 'Sutradara: ' . cs_e($director);
                        if ($cast !== '')     $parts[] = 'Pemain: ' . cs_e($cast);
                        echo implode(' • ', $parts);
                    ?></p>
                <?php endif; ?>

                <?php if ($synopsis !== ''): ?><p><?= cs_e($synopsis) ?></p><?php endif; ?>

                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <button id="likeButton" class="submit-btn like-btn<?= $inWatchlist ? ' liked' : '' ?>">
                        <i class="<?= $inWatchlist ? 'fas fa-heart' : 'fas fa-thumbs-up' ?>"></i> <span id="likeText"><?= $inWatchlist ? 'Disukai' : 'Suka' ?></span>
                    </button>

                    <a href="#semua-ulasan" id="scrollToReviews" class="submit-btn review-link" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-eye"></i> Lihat Semua Ulasan
                    </a>
                </div>

                <div class="rating-summary" style="margin-top: 25px;">
                    <div class="rating-box">
                        <div class="score" id="averageRating"><?= $formattedAvgRating ?>/5</div>
                        <div class="count">Rata-Rata</div>
                    </div>
                    <div class="rating-box">
                        <div class="score" id="reviewCount"><?= $totalReviews ?></div>
                        <div class="count">Total Ulasan</div>
                    </div>
                    <button id="trailerButton" class="submit-btn trailer-btn" style="padding: 15px 20px; background-color: #9b59b6;">
                        <i class="fas fa-play-circle"></i> Tonton Trailer
                    </button>
                </div>
            </div>
        </section>

        <section class="review-form-section">
            <h2><i class="fas fa-edit"></i> Berikan Ulasan dan Rating Anda</h2>
            <?php if ($isLoggedIn): ?>
                <form id="reviewForm">
                    <div class="form-group">
                        <label>Rating Anda (1-5 Bintang)</label>
                        <div class="star-rating" id="ratingInput">
                            <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Sangat Suka"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Bagus"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Lumayan"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Kurang"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Buruk"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reviewText">Ulasan Anda:</label>
                        <textarea id="reviewText" name="reviewText" placeholder="Tuliskan ulasan Anda tentang <?= cs_e($title) ?> di sini..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn" id="submitButton">
                        <i class="fas fa-paper-plane"></i> Kirim Ulasan
                    </button>
                </form>
            <?php else: ?>
                <div class="login-prompt">
                    <i class="fas fa-lock"></i> Anda harus <a href="login.php">login</a> terlebih dahulu untuk memberi rating dan menulis ulasan.
                </div>
            <?php endif; ?>
        </section>

        <section class="my-reviews-section" id="semua-ulasan">
            <h2><i class="fas fa-comments"></i> Semua Ulasan</h2>
            <div id="my-reviews-display">
                <?= cs_reviewsHtml($allReviews) ?>
            </div>
        </section>
    </div>

    <script>
        const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
        const CSRF = <?= json_encode($csrf) ?>;
        const MOVIE_TITLE = <?= json_encode($title, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const TRAILER_URL = <?= json_encode($trailerHref, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const SELF_URL = <?= json_encode($selfUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const LOGIN_URL = 'login.php';

        const reviewForm        = document.getElementById('reviewForm');
        const submitButton      = document.getElementById('submitButton');
        const averageRatingSpan = document.getElementById('averageRating');
        const reviewCountSpan   = document.getElementById('reviewCount');
        const likeButton        = document.getElementById('likeButton');
        const likeText          = document.getElementById('likeText');
        const trailerButton     = document.getElementById('trailerButton');

        function setSubmit(disabled, html) {
            if (!submitButton) return;
            submitButton.disabled = disabled;
            submitButton.innerHTML = html;
        }
        const SUBMIT_IDLE = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';

        function postAction(params) {
            const body = new URLSearchParams();
            body.append('csrf', CSRF);
            Object.keys(params).forEach(function (k) { body.append(k, params[k]); });
            return fetch(SELF_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            }).then(function (r) { return r.json(); });
        }

        function loadReviewsAndStats() {
            setSubmit(true, '<i class="fas fa-spinner fa-spin"></i> Memuat...');
            fetch(SELF_URL + '&fetch_data=1', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    averageRatingSpan.textContent = data.averageRating + '/5';
                    reviewCountSpan.textContent = data.totalReviews;
                    document.getElementById('my-reviews-display').innerHTML = data.myReviewsHtml;
                    setSubmit(false, SUBMIT_IDLE);
                })
                .catch(function (error) {
                    console.error('❌ Error:', error);
                    setSubmit(false, SUBMIT_IDLE);
                });
        }

        // Tombol Suka = masuk / keluar Daftar Tonton
        likeButton.addEventListener('click', function () {
            if (!IS_LOGGED_IN) { window.location.href = LOGIN_URL; return; }
            likeButton.disabled = true;
            postAction({ action: 'toggle_watchlist' })
                .then(function (res) {
                    likeButton.disabled = false;
                    if (res.login) { window.location.href = LOGIN_URL; return; }
                    if (!res.success) { alert('❌ ' + res.message); return; }
                    if (res.in) {
                        likeButton.classList.add('liked');
                        likeText.textContent = 'Disukai';
                        likeButton.querySelector('i').className = 'fas fa-heart';
                    } else {
                        likeButton.classList.remove('liked');
                        likeText.textContent = 'Suka';
                        likeButton.querySelector('i').className = 'fas fa-thumbs-up';
                    }
                    alert(res.message);
                })
                .catch(function (error) {
                    console.error('❌ Error:', error);
                    likeButton.disabled = false;
                    alert('❌ Terjadi kesalahan jaringan. Cek console log.');
                });
        });

        // Kirim ulasan
        if (reviewForm) {
            reviewForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const selectedRating = document.querySelector('input[name="rating"]:checked');
                if (!selectedRating) {
                    alert('Harap berikan rating (bintang) sebelum mengirim ulasan!');
                    return;
                }

                setSubmit(true, '<i class="fas fa-spinner fa-spin"></i> Mengirim...');

                postAction({
                    action: 'add_review',
                    rating: selectedRating.value,
                    reviewText: document.getElementById('reviewText').value
                })
                .then(function (data) {
                    if (data.login) { window.location.href = LOGIN_URL; return; }
                    if (data.success) {
                        alert('✅ ' + data.message);
                        document.getElementById('reviewText').value = '';
                        document.querySelectorAll('input[name="rating"]').forEach(function (input) { input.checked = false; });
                        loadReviewsAndStats();
                    } else {
                        alert('❌ ' + data.message);
                        setSubmit(false, SUBMIT_IDLE);
                    }
                })
                .catch(function (error) {
                    console.error('❌ Error:', error);
                    alert('❌ Terjadi kesalahan jaringan. Cek console log.');
                    setSubmit(false, SUBMIT_IDLE);
                });
            });
        }

        // Lihat Semua Ulasan: scroll ke bagian "Semua Ulasan" di halaman ini
        document.getElementById('scrollToReviews').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('semua-ulasan').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Tonton trailer
        trailerButton.addEventListener('click', function () {
            if (!TRAILER_URL) { alert('Trailer untuk film ini belum tersedia.'); return; }
            window.open(TRAILER_URL, '_blank');
        });
    </script>
</body>
</html>