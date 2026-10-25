<?php
// File: film_rangga_cinta.php
// Halaman film "Rangga & Cinta" (ID 6) VERSI TAMU (index.php): hanya melihat review & rating.
// - Tamu (belum login) : bisa membaca review, diarahkan login untuk menulis review
// - User (sudah login) : bisa memberi rating (1-5 bintang), menulis/mengedit/menghapus review, dan menyukai review user lain
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi + session dari config.php (database: movie_review_db)
require __DIR__ . '/config.php';
$pdo = db();
if (session_status() === PHP_SESSION_NONE) session_start();

// VERSI TAMU: hanya melihat review & rating, tidak ada form/aksi apa pun.
$isLoggedIn = false;
$userId     = 0;
$homeUrl    = 'index.php';

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

function cs_formatTanggalID($date) {
    $bulan = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
    $t = strtotime($date);
    return $t ? date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t) : '';
}

function cs_youtubeId($url) {
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', (string)$url, $m)) {
        return $m[1];
    }
    return null;
}

function cs_starsHtml($rating) {
    $rating = max(0, min(5, (float)$rating));
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i - 0.25)       $html .= '<i class="fas fa-star"></i>';
        elseif ($rating >= $i - 0.75)   $html .= '<i class="fas fa-star-half-alt"></i>';
        else                            $html .= '<i class="far fa-star"></i>';
    }
    return $html;
}

// ---------- Ambil film ----------
$movieId = 6;   // ID film Rangga & Cinta di tabel movies (sesuaikan jika ID di database Anda berbeda)
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    http_response_code(404);
    ?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Film tidak ditemukan</title>
    <style>body{background:#0a0a0a;color:#fff;font-family:Inter,Segoe UI,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}a{color:#e50914;font-weight:600}</style></head>
    <body><div><h1>🎬 Film tidak ditemukan</h1><p style="margin:12px 0 20px;color:#999">Film yang Anda cari tidak ada atau sudah dihapus.</p><a href="<?php echo cs_e($homeUrl); ?>">← Kembali ke Beranda</a></div></body></html><?php
    exit;
}

// Film Coming Soon belum bisa direview
if (($movie['status'] ?? 'released') === 'coming_soon') {
    header('Location: ' . $homeUrl);
    exit;
}

// ---------- Cek fitur opsional di database ----------
$hasHelpful      = cs_columnExists($pdo, 'review_helpful', 'review_id') && cs_columnExists($pdo, 'review_helpful', 'user_id');


// ---------- Ambil review ----------
$likeSelect = $hasHelpful
    ? ", (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.id) AS like_count,
         (SELECT COUNT(*) FROM review_helpful rh2 WHERE rh2.review_id = r.id AND rh2.user_id = :uid2) AS liked_by_me"
    : ", 0 AS like_count, 0 AS liked_by_me";
$sql = "SELECT r.*, u.username AS account_name $likeSelect
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE r.movie_id = :mid
        ORDER BY (r.user_id = :uid) DESC, r.id DESC";
$stmt = $pdo->prepare($sql);
$params = array(':mid' => $movieId, ':uid' => $userId);
if ($hasHelpful) $params[':uid2'] = $userId;
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reviewCount = count($reviews);
$sumRating = 0;
$dist = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
foreach ($reviews as $rv) {
    $sumRating += (int)$rv['rating'];
    $k = max(1, min(5, (int)$rv['rating']));
    $dist[$k]++;
}
$avgRating = $reviewCount ? $sumRating / $reviewCount : 0;

$posterPath = cs_getPosterPath($movie['poster'] ?? '');
$genres = array_filter(array_map('trim', explode(',', $movie['genre'] ?? '')));

// Trailer
$trailer = trim($movie['trailer_url'] ?? '');
$ytId = cs_youtubeId($trailer);
$localVideo = (!$ytId && $trailer !== '' && !preg_match('~^https?://~i', $trailer) && file_exists(__DIR__ . '/' . $trailer)) ? $trailer : null;


?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo cs_e($movie['title']); ?> - CineScope</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; line-height: 1.6; background-color: #0a0a0a; color: #fff; }
a { color: inherit; }
.container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }

.navbar { background-color: #111; padding: 1rem 0; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #333; }
.nav-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.nav-brand { color: #e50914; font-size: 1.6rem; font-weight: 700; text-decoration: none; }
.back-link { background: #e50914; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; }
.back-link:hover { background: #b8070f; }

/* Hero film */
.movie-hero { padding: 2.5rem 0; background: linear-gradient(180deg, #1a0a0c 0%, #0a0a0a 100%); border-bottom: 1px solid #222; }
.movie-hero .container { display: grid; grid-template-columns: 280px 1fr; gap: 2.5rem; align-items: start; }
.poster-box { position: relative; padding-top: 150%; border-radius: 12px; overflow: hidden; background: #1a1a1a; box-shadow: 0 10px 30px rgba(229, 9, 20, 0.25); }
.poster-box img, .poster-box .no-poster { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.no-poster { display: flex; align-items: center; justify-content: center; font-size: 4rem; background: #0277bd; }
.movie-details h1 { font-size: 2.3rem; line-height: 1.2; margin-bottom: 0.3rem; }
.original-title { color: #999; font-style: italic; margin-bottom: 0.8rem; }
.meta { display: flex; flex-wrap: wrap; gap: 0.6rem 1.2rem; color: #bbb; font-size: 0.95rem; margin-bottom: 1rem; }
.meta i { color: #e50914; margin-right: 5px; }
.genres { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1.2rem; }
.genre-chip { background: rgba(229, 9, 20, 0.15); color: #ff6b73; border: 1px solid rgba(229, 9, 20, 0.4); padding: 3px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
.score-box { display: inline-flex; align-items: center; gap: 14px; background: #161616; border: 1px solid #2a2a2a; border-radius: 12px; padding: 12px 20px; margin-bottom: 1.2rem; }
.score-number { font-size: 2.4rem; font-weight: 700; color: #ffc107; line-height: 1; }
.score-box .stars { color: #ffc107; font-size: 1.05rem; }
.score-box small { color: #888; display: block; font-size: 0.82rem; }
.synopsis h3 { font-size: 1.1rem; margin-bottom: 0.4rem; color: #fff; }
.synopsis p { color: #ccc; white-space: pre-line; }

/* Section */
.section { padding: 2.5rem 0; }
.section h2 { font-size: 1.5rem; margin-bottom: 1.2rem; }
.section h2 i { color: #e50914; margin-right: 8px; }
.trailer-wrap { position: relative; padding-top: 56.25%; border-radius: 12px; overflow: hidden; background: #000; }
.trailer-wrap iframe, .trailer-wrap video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.trailer-link { display: inline-block; background: #e50914; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; }

/* Ringkasan rating */
.summary { display: grid; grid-template-columns: 200px 1fr; gap: 2rem; background: #141414; border: 1px solid #262626; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; align-items: center; }
.summary-score { text-align: center; }
.summary-score .big { font-size: 3.2rem; font-weight: 700; color: #ffc107; line-height: 1; }
.summary-score .stars { color: #ffc107; margin: 6px 0; }
.summary-score small { color: #888; }
.bar-row { display: flex; align-items: center; gap: 10px; margin: 5px 0; font-size: 0.85rem; color: #aaa; }
.bar-row .label { width: 38px; white-space: nowrap; }
.bar-row .label i { color: #ffc107; font-size: 0.75rem; }
.bar { flex: 1; height: 8px; background: #2a2a2a; border-radius: 4px; overflow: hidden; }
.bar span { display: block; height: 100%; background: #e50914; border-radius: 4px; }
.bar-row .count { width: 28px; text-align: right; }

/* Alert */
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1.2rem; font-weight: 500; }
.alert.success { background: #0b3a1d; color: #2ecc71; border: 1px solid #2ecc71; }
.alert.error { background: #3b0000; color: #ff6b6b; border: 1px solid #e74c3c; }

/* Form review */
.review-form, .login-box { background: #141414; border: 1px solid #262626; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
.review-form h3, .login-box h3 { margin-bottom: 1rem; font-size: 1.15rem; }
.star-input { display: inline-flex; flex-direction: row-reverse; gap: 6px; }
.star-input input { display: none; }
.star-input label { cursor: pointer; color: #444; font-size: 2.1rem; transition: color 0.15s, transform 0.15s; line-height: 1; }
.star-input input:checked ~ label,
.star-input label:hover,
.star-input label:hover ~ label { color: #ffc107; }
.star-input label:hover { transform: scale(1.15); }
.rating-hint { display: inline-block; margin-left: 14px; color: #ffc107; font-weight: 600; min-height: 1.5em; }
textarea { width: 100%; min-height: 130px; padding: 14px; margin-top: 14px; background: #1c1c1c; border: 1px solid #333; border-radius: 8px; color: #fff; font-family: inherit; font-size: 1rem; resize: vertical; outline: none; }
textarea:focus { border-color: #e50914; }
.form-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
.char-count { color: #777; font-size: 0.85rem; }
.btn { background: #e50914; color: #fff; border: none; padding: 12px 26px; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-family: inherit; }
.btn:hover { background: #b8070f; }
.btn-outline { background: transparent; border: 2px solid #e50914; color: #e50914; }
.btn-outline:hover { background: #e50914; color: #fff; }
.login-box { text-align: center; }
.login-box p { color: #aaa; margin-bottom: 1rem; }
.login-box .btn { margin: 4px; }

/* Daftar review */
.review-card { background: #141414; border: 1px solid #262626; border-radius: 12px; padding: 1.2rem 1.4rem; margin-bottom: 1rem; }
.review-card.mine { border-color: #e50914; }
.review-head { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #e50914, #8a0610); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
.review-user { font-weight: 600; }
.review-user .badge { background: #e50914; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-left: 6px; }
.review-date { color: #777; font-size: 0.82rem; }
.review-stars { color: #ffc107; margin-left: auto; white-space: nowrap; }
.review-text { color: #ddd; white-space: pre-line; word-break: break-word; margin: 6px 0 12px; }
.review-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.like-btn, .del-btn { background: #1f1f1f; color: #bbb; border: 1px solid #333; padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; font-family: inherit; }
.like-btn:hover { border-color: #ff4d6d; color: #ff4d6d; }
.like-btn.liked { background: rgba(255, 77, 109, 0.15); border-color: #ff4d6d; color: #ff4d6d; }
.like-btn[disabled] { cursor: default; opacity: 0.8; }
.del-btn:hover { border-color: #e74c3c; color: #e74c3c; }
.empty { text-align: center; color: #666; padding: 40px 20px; }
.empty i { font-size: 3rem; color: #333; margin-bottom: 10px; }

.footer { border-top: 1px solid #222; text-align: center; color: #777; padding: 2rem 0; margin-top: 2rem; font-size: 0.9rem; }

@media (max-width: 768px) {
    .movie-hero .container { grid-template-columns: 1fr; }
    .poster-box { max-width: 240px; margin: 0 auto; }
    .movie-details h1 { font-size: 1.7rem; }
    .summary { grid-template-columns: 1fr; gap: 1rem; }
    .review-stars { margin-left: 0; width: 100%; }
    .star-input label { font-size: 2.4rem; }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="<?php echo cs_e($homeUrl); ?>" class="nav-brand">CineScope</a>
        <a href="<?php echo cs_e($homeUrl); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</nav>

<header class="movie-hero">
    <div class="container">
        <div class="poster-box">
            <?php if ($posterPath): ?>
                <img src="<?php echo cs_e($posterPath); ?>" alt="<?php echo cs_e($movie['title']); ?>">
            <?php else: ?>
                <div class="no-poster">🎬</div>
            <?php endif; ?>
        </div>

        <div class="movie-details">
            <h1><?php echo cs_e($movie['title']); ?></h1>
            <?php if (!empty($movie['original_title'])): ?>
                <div class="original-title"><?php echo cs_e($movie['original_title']); ?></div>
            <?php endif; ?>

            <div class="meta">
                <?php if (!empty($movie['release_year'])): ?><span><i class="fas fa-calendar"></i><?php echo cs_e($movie['release_year']); ?></span><?php endif; ?>
                <?php if (!empty($movie['duration'])): ?><span><i class="fas fa-clock"></i><?php echo (int)$movie['duration']; ?> menit</span><?php endif; ?>
                <?php if (!empty($movie['director'])): ?><span><i class="fas fa-video"></i><?php echo cs_e($movie['director']); ?></span><?php endif; ?>
            </div>

            <?php if ($genres): ?>
            <div class="genres">
                <?php foreach ($genres as $g): ?><span class="genre-chip"><?php echo cs_e($g); ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="score-box">
                <div class="score-number"><?php echo number_format($avgRating, 1); ?></div>
                <div>
                    <div class="stars"><?php echo cs_starsHtml($avgRating); ?></div>
                    <small><?php echo $reviewCount; ?> review</small>
                </div>
            </div>

            <?php if (!empty($movie['synopsis'])): ?>
            <div class="synopsis">
                <h3>Sinopsis</h3>
                <p><?php echo cs_e($movie['synopsis']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($ytId || $localVideo || ($trailer !== '' && preg_match('~^https?://~i', $trailer))): ?>
<section class="section">
    <div class="container">
        <h2><i class="fas fa-play-circle"></i> Trailer</h2>
        <?php if ($ytId): ?>
            <div class="trailer-wrap">
                <iframe src="https://www.youtube.com/embed/<?php echo cs_e($ytId); ?>" allowfullscreen loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        <?php elseif ($localVideo): ?>
            <div class="trailer-wrap"><video src="<?php echo cs_e($localVideo); ?>" controls preload="metadata"></video></div>
        <?php else: ?>
            <a class="trailer-link" href="<?php echo cs_e($trailer); ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Tonton Trailer</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="section" id="reviews" style="padding-top:1rem;">
    <div class="container">
        <h2><i class="fas fa-star"></i> Rating & Review</h2>

        <?php if ($reviewCount > 0): ?>
        <div class="summary">
            <div class="summary-score">
                <div class="big"><?php echo number_format($avgRating, 1); ?></div>
                <div class="stars"><?php echo cs_starsHtml($avgRating); ?></div>
                <small>dari <?php echo $reviewCount; ?> review</small>
            </div>
            <div>
                <?php for ($s = 5; $s >= 1; $s--): $pct = $reviewCount ? round($dist[$s] / $reviewCount * 100) : 0; ?>
                <div class="bar-row">
                    <span class="label"><?php echo $s; ?> <i class="fas fa-star"></i></span>
                    <div class="bar"><span style="width:<?php echo $pct; ?>%"></span></div>
                    <span class="count"><?php echo $dist[$s]; ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="login-box">
            <h3><i class="fas fa-lock"></i> Login untuk memberi rating & review</h3>
            <p>Masuk atau daftar dulu supaya Anda bisa menulis review untuk film ini.</p>
            <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="bergabung_sekarang.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Daftar</a>
        </div>

        <?php if ($reviewCount === 0): ?>
            <div class="empty">
                <i class="far fa-comment-dots"></i>
                <p>Belum ada review untuk film ini. Jadilah yang pertama!</p>
            </div>
        <?php endif; ?>

        <?php foreach ($reviews as $rv):
            $name = $rv['account_name'] ?: (($rv['user_name'] ?? '') ?: 'Pengguna');
            $isMine = false;
            $date = $rv['created_at'] ?? '';
        ?>
        <div class="review-card <?php echo $isMine ? 'mine' : ''; ?>" id="review-<?php echo (int)$rv['id']; ?>">
            <div class="review-head">
                <div class="avatar"><?php echo cs_e(mb_strtoupper(mb_substr($name, 0, 1))); ?></div>
                <div>
                    <div class="review-user"><?php echo cs_e($name); ?><?php if ($isMine): ?><span class="badge">Review Anda</span><?php endif; ?></div>
                    <?php if ($date): ?><div class="review-date"><?php echo cs_e(cs_formatTanggalID($date)); ?></div><?php endif; ?>
                </div>
                <div class="review-stars"><?php echo cs_starsHtml($rv['rating']); ?> <strong><?php echo (int)$rv['rating']; ?>/5</strong></div>
            </div>
            <div class="review-text"><?php echo cs_e($rv['review_text']); ?></div>

            <?php if ($hasHelpful): ?>
            <div class="review-actions">
                <span class="like-btn" style="cursor:default;"><i class="far fa-heart"></i> <?php echo (int)$rv['like_count']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="footer">&copy; 2024 CineScope. All rights reserved.</footer>

</body>
</html>