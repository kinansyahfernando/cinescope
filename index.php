<?php
// File: index.php (GUEST VERSION - Lihat film; review & rating setelah login)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi + session dari config.php (database: movie_review_db)
require __DIR__ . '/config.php';
$pdo = db();
if (session_status() === PHP_SESSION_NONE) session_start();

function getPosterPath($posterFromDB) {
    if (empty($posterFromDB)) return null;
    $possiblePaths = array(__DIR__ . '/' . $posterFromDB, $posterFromDB);
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) return $posterFromDB;
    }
    return null;
}

// Nama file bisa mengandung spasi / tanda & => ubah jadi URL yang aman
function cs_href($file) {
    $parts = explode('?', $file, 2);
    return rawurlencode($parts[0]) . (isset($parts[1]) ? '?' . $parts[1] : '');
}
// Pilih nama file yang BENAR-BENAR ada di folder ini (urutan = prioritas)
function cs_pickFile($candidates) {
    foreach ($candidates as $c) {
        if (is_file(__DIR__ . '/' . $c)) return $c;
    }
    return $candidates[0];
}

// Setiap film punya file sendiri untuk tamu: hanya bisa melihat review & rating (tidak bisa menulis).
// Film baru yang diupload lewat admin.php otomatis dibuka lewat film_sore.php?id=ID (tanpa file tambahan)
function generateMovieCardLink($movie) {
    $files = array(
        1 => array('film_sore.php', 'film.sore.php', 'film sore.php'),
        2 => array('film_petaka_gunung_gede.php', 'film petaka gunung gede.php'),
        3 => array('film_dilan1990.php', 'film dilan1990.php'),
        4 => array('film_rest_area.php', 'film rest area.php'),
        5 => array('film_tukartakdir.php', 'film_tukar_takdir.php', 'film tukartakdir.php'),
        6 => array('film_rangga_cinta.php', 'film_rangga&cinta.php', 'film rangga & cinta.php'),
    );
    $id = (int)$movie['id'];
    if (isset($files[$id])) return cs_href(cs_pickFile($files[$id]));
    return cs_href(cs_pickFile($files[1])) . '?id=' . $id;
}

// ==========================================
// 🔥 RANKING FILM (semua film berstatus 'released' / Daftar Film)
// Skor = (jumlah komentar x 2 + jumlah like) x rata-rata rating
// ==========================================
function fetchRankedMovies($pdo) {
    $withLikes = "
        SELECT m.*,
               COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.movie_id = m.id), 0) AS avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.id) AS review_count,
               GREATEST(
                   COALESCE((SELECT SUM(r.helpful_count) FROM reviews r WHERE r.movie_id = m.id), 0),
                   (SELECT COUNT(*) FROM review_helpful rh
                        JOIN reviews r ON r.id = rh.review_id
                        WHERE r.movie_id = m.id)
               ) AS like_count
        FROM movies m
        WHERE m.status = 'released'";
    $withoutLikes = "
        SELECT m.*,
               COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.movie_id = m.id), 0) AS avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.id) AS review_count,
               0 AS like_count
        FROM movies m
        WHERE m.status = 'released'";
    try {
        $rows = $pdo->query($withLikes)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $rows = $pdo->query($withoutLikes)->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($rows as &$row) {
        $row['avg_rating']   = (float)$row['avg_rating'];
        $row['review_count'] = (int)$row['review_count'];
        $row['like_count']   = (int)$row['like_count'];
        $row['score'] = ($row['review_count'] * 2 + $row['like_count']) * $row['avg_rating'];
    }
    unset($row);

    usort($rows, function ($a, $b) {
        return [$b['score'], $b['avg_rating'], $b['review_count'], $a['id']]
           <=> [$a['score'], $a['avg_rating'], $a['review_count'], $b['id']];
    });
    return $rows;
}

$rankedMovies   = fetchRankedMovies($pdo);
$trendingMovies = array_slice($rankedMovies, 0, 3);   // Top 3 Trending
$topRank = array();                                    // id film => peringkat (1..3)
foreach ($trendingMovies as $i => $m) { $topRank[$m['id']] = $i + 1; }

// ==========================================
// 🎞️ DAFTAR FILM = SEMUA film rilis (termasuk yang masuk Top 3)
// Urutan: film terbaru masuk -> paling depan (baris 1, urutan 1)
// ==========================================
$allMovies = $rankedMovies;
usort($allMovies, function ($a, $b) {
    $ta = strtotime($a['released_at'] ?? ($a['created_at'] ?? '')) ?: 0;
    $tb = strtotime($b['released_at'] ?? ($b['created_at'] ?? '')) ?: 0;
    return [$tb, $b['id']] <=> [$ta, $a['id']];
});

// Bagi film ke beberapa baris secara merata:
//  - maksimal 15 film per baris (batas keras)
//  - ideal sekitar 10 film per baris, dan baris pertama tidak dibiarkan penuh sendirian
//  - sisa pembagian masuk ke baris paling atas
//  contoh: 6 film => 3+3 | 10 => 5+5 | 20 => 10+10 | 21 => 7+7+7
function splitIntoRows($items, $maxPerRow = 15, $idealPerRow = 10) {
    $n = count($items);
    if ($n === 0) return array();
    $rows = ($n === 1) ? 1 : max(2, (int)ceil($n / $idealPerRow));
    $rows = max($rows, (int)ceil($n / $maxPerRow));
    $base = intdiv($n, $rows);
    $extra = $n % $rows;
    $result = array();
    $offset = 0;
    for ($r = 0; $r < $rows; $r++) {
        $size = $base + ($r < $extra ? 1 : 0);
        $result[] = array_slice($items, $offset, $size);
        $offset += $size;
    }
    return $result;
}

// ==========================================
// 🎬 COMING SOON - hanya film yang diupload admin dengan status "Coming Soon".
// Kalau kosong, section Coming Soon tidak ditampilkan sama sekali.
// ==========================================
$comingSoonMovies = $pdo->query("
    SELECT m.* FROM movies m
    WHERE m.status = 'coming_soon'
    ORDER BY m.release_date IS NULL, m.release_date ASC, m.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

function formatTanggalID($date) {
    $bulan = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
    $t = strtotime($date);
    return $t ? date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t) : '';
}

// ==========================================
// Komponen kartu film
// ==========================================
function renderMovieCard($movie, $badgeHtml, $bg, $link) {
    $posterPath = getPosterPath($movie['poster'] ?? '');
    ob_start(); ?>
    <article class="movie-card" data-id="<?php echo (int)$movie['id']; ?>">
        <a class="poster-link" href="<?php echo htmlspecialchars($link); ?>" draggable="false" aria-label="Buka <?php echo htmlspecialchars($movie['title']); ?>">
            <?php if ($posterPath): ?>
                <img src="<?php echo htmlspecialchars($posterPath); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster" draggable="false" loading="lazy">
            <?php else: ?>
                <div class="movie-poster poster-fallback" style="background:<?php echo $bg; ?>;">🎬</div>
            <?php endif; ?>
            <?php echo $badgeHtml; ?>
        </a>
        <div class="movie-info">
            <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
            <div class="movie-genre"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></div>
            <div class="movie-meta">
                <span class="movie-rating" title="Rating rata-rata"><i class="fas fa-star"></i> <?php echo number_format($movie['avg_rating'], 1); ?></span>
                <span class="comments" title="Jumlah komentar"><i class="fas fa-comment"></i> <?php echo (int)$movie['review_count']; ?></span>
                <span class="likes" title="Jumlah like"><i class="fas fa-heart"></i> <?php echo (int)$movie['like_count']; ?></span>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function renderComingSoonCard($movie) {
    $posterPath = getPosterPath($movie['poster'] ?? '');
    ob_start(); ?>
    <article class="movie-card" data-id="<?php echo (int)$movie['id']; ?>">
        <a class="poster-link" href="#" onclick="showComingSoonAlert(event)" draggable="false" aria-label="<?php echo htmlspecialchars($movie['title']); ?> (Coming Soon)">
            <?php if ($posterPath): ?>
                <img src="<?php echo htmlspecialchars($posterPath); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster" draggable="false" loading="lazy">
            <?php else: ?>
                <div class="movie-poster poster-fallback" style="background:#558b2f;">🎬</div>
            <?php endif; ?>
            <div class="new-badge"><i class="fas fa-clock"></i> Coming Soon</div>
        </a>
        <div class="movie-info">
            <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
            <div class="movie-genre"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></div>
            <div class="movie-release"><?php echo !empty($movie['release_date']) ? '📅 Rilis ' . formatTanggalID($movie['release_date']) : '⏳ Segera Hadir'; ?></div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

// Satu baris poster yang bisa digeser ke samping (swipe di HP, drag di laptop)
function renderMovieRow($cardsHtml) {
    ob_start(); ?>
    <div class="row-wrap">
        <div class="movie-row"><?php echo implode('', $cardsHtml); ?></div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0a0a0a">
<title>CineScope - Movie Review Community</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { --red:#e50914; --red-dark:#b8070f; --bg:#0a0a0a; --surface:#141414; --surface-2:#1e1e1e; --border:#262626; --text:#fff; --muted:#a3a3a3; --gold:#ffc107; --radius:12px; }
* { margin:0; padding:0; box-sizing:border-box; }
html { -webkit-text-size-adjust:100%; }
body { font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif; line-height:1.6; background:var(--bg); color:var(--text); }
a { color:inherit; }
img { max-width:100%; }
.container { width:100%; max-width:1200px; margin:0 auto; padding:0 clamp(14px,4vw,24px); }

/* ===== Navbar ===== */
.navbar { position:sticky; top:0; z-index:1000; background:rgba(10,10,10,.92); -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px); border-bottom:1px solid var(--border); }
.nav-container { max-width:1200px; margin:0 auto; padding:.7rem clamp(14px,4vw,24px); display:flex; align-items:center; gap:.8rem 1rem; flex-wrap:wrap; }
.nav-brand { text-decoration:none; }
.nav-brand h1 { color:var(--red); font-size:clamp(1.3rem,4vw,1.7rem); font-weight:800; letter-spacing:-.5px; line-height:1.2; }
.nav-search { display:flex; flex:1 1 auto; max-width:420px; margin-left:1rem; min-width:0; }
.search-input { flex:1; min-width:0; padding:.65rem 1rem; border:1px solid var(--border); border-right:none; border-radius:999px 0 0 999px; background:var(--surface-2); color:#fff; font-size:1rem; outline:none; font-family:inherit; }
.search-input:focus { border-color:var(--red); }
.search-input::placeholder { color:#777; }
.search-btn { padding:.65rem 1.1rem; background:var(--red); border:none; border-radius:0 999px 999px 0; color:#fff; cursor:pointer; transition:background .2s; }
.search-btn:hover { background:var(--red-dark); }
.nav-menu { display:flex; align-items:center; gap:.4rem; margin-left:auto; }
.nav-link { color:#ccc; text-decoration:none; font-weight:500; font-size:.95rem; padding:.45rem .85rem; border-radius:999px; white-space:nowrap; transition:color .2s, background .2s; }
.nav-link:hover { color:#fff; background:var(--surface-2); }
.nav-cta { background:var(--red); color:#fff; text-decoration:none; font-weight:600; font-size:.95rem; padding:.45rem 1.1rem; border-radius:999px; white-space:nowrap; transition:background .2s; }
.nav-cta:hover { background:var(--red-dark); }

/* ===== Hero (tamu) & sapaan (user) ===== */
.hero { background:linear-gradient(rgba(0,0,0,.72),rgba(0,0,0,.72)), url('banner.jpg') center/cover no-repeat, #111; min-height:clamp(320px,60vh,500px); display:flex; align-items:center; justify-content:center; text-align:center; border-bottom:2px solid var(--red); padding:2.5rem 0; }
.hero-content { max-width:800px; padding:0 20px; }
.hero-content h2 { font-size:clamp(1.6rem,5.5vw,3rem); line-height:1.2; margin-bottom:1rem; font-weight:800; text-shadow:2px 2px 6px rgba(0,0,0,.8); }
.hero-content p { font-size:clamp(.95rem,2.6vw,1.25rem); color:#ccc; margin-bottom:1.8rem; }
.hero-buttons { display:flex; gap:.9rem; justify-content:center; flex-wrap:wrap; }
.btn { padding:.85rem 1.8rem; border-radius:8px; font-weight:600; text-decoration:none; transition:all .25s; font-size:1rem; display:inline-flex; align-items:center; justify-content:center; gap:.5rem; }
.btn-primary { background:var(--red); color:#fff; }
.btn-primary:hover { background:var(--red-dark); transform:translateY(-2px); }
.btn-secondary { background:rgba(255,255,255,.1); color:#fff; border:2px solid #fff; }
.btn-secondary:hover { background:rgba(255,255,255,.2); transform:translateY(-2px); }
.intro { padding:clamp(1.4rem,4vw,2.2rem) 0 .2rem; background:linear-gradient(180deg,#1a0a0b 0%,var(--bg) 100%); }
.intro-hi { color:var(--muted); font-size:.95rem; }
.intro-hi strong { color:#fff; }
.intro h2 { font-size:clamp(1.4rem,4.5vw,2.1rem); font-weight:800; line-height:1.25; }

/* ===== Section ===== */
.section { padding:clamp(1.6rem,5vw,3rem) 0 clamp(.6rem,2vw,1rem); }
.section-head { margin-bottom:1rem; }
.section-title { font-size:clamp(1.2rem,3.6vw,1.75rem); font-weight:700; display:flex; align-items:center; gap:.6rem; line-height:1.3; }
.section-title i { color:var(--red); }
.section-sub { color:var(--muted); font-size:.88rem; margin-top:.15rem; }
.movies-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:1rem; }
.trending-grid { grid-template-columns:repeat(3,minmax(0,1fr)); gap:clamp(.7rem,2vw,1.5rem); }

/* ===== Kartu film ===== */
.movie-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; display:flex; flex-direction:column; transition:transform .25s, box-shadow .25s, border-color .25s; }
.movie-card:hover { transform:translateY(-4px); border-color:rgba(229,9,20,.6); box-shadow:0 12px 28px rgba(229,9,20,.18); }
.poster-link { display:block; position:relative; aspect-ratio:2/3; overflow:hidden; background:#222; }
.movie-poster { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .4s ease; }
.movie-card:hover .movie-poster { transform:scale(1.05); }
.poster-fallback { display:flex; align-items:center; justify-content:center; font-size:2.6rem; color:#fff; }
.trending-badge, .new-badge { position:absolute; top:8px; left:8px; background:rgba(229,9,20,.95); color:#fff; padding:3px 9px; border-radius:6px; font-size:.75rem; font-weight:700; z-index:2; box-shadow:0 2px 8px rgba(0,0,0,.5); }
.movie-info { padding:.75rem .8rem .9rem; display:flex; flex-direction:column; gap:.2rem; flex:1; }
.movie-title { font-size:clamp(.92rem,2.6vw,1.05rem); font-weight:600; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.6em; }
.movie-genre { color:var(--muted); font-size:.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.movie-meta { display:flex; flex-wrap:wrap; align-items:center; gap:.2rem .85rem; font-size:.82rem; margin-top:auto; padding-top:.4rem; color:var(--muted); }
.movie-rating { color:var(--gold); font-weight:700; }
.movie-meta .comments { color:#8fb4d9; }
.movie-meta .likes { color:#ff6b86; }
.movie-release { color:var(--gold); font-size:.82rem; font-weight:600; margin-top:auto; padding-top:.4rem; }

/* ===== Baris film: geser horizontal (swipe di HP, drag/trackpad di laptop) ===== */
.row-wrap { position:relative; margin-bottom:.4rem; }
.movie-row { display:flex; gap:clamp(.7rem,2vw,1.1rem); overflow-x:auto; overflow-y:hidden; padding:6px 2px 14px; scroll-snap-type:x proximity; -webkit-overflow-scrolling:touch; overscroll-behavior-x:contain; cursor:grab; scrollbar-width:thin; scrollbar-color:var(--red) var(--surface-2); }
.movie-row.dragging { cursor:grabbing; scroll-snap-type:none; user-select:none; }
.movie-row::-webkit-scrollbar { height:6px; }
.movie-row::-webkit-scrollbar-track { background:var(--surface-2); border-radius:4px; }
.movie-row::-webkit-scrollbar-thumb { background:var(--red); border-radius:4px; }
.movie-row .movie-card { flex:0 0 clamp(138px,40vw,200px); scroll-snap-align:start; }
/* bayangan di tepi sebagai petunjuk masih ada film di samping (bukan tombol) */
.row-wrap::before, .row-wrap::after { content:''; position:absolute; top:0; bottom:14px; width:34px; pointer-events:none; z-index:3; opacity:0; transition:opacity .2s; }
.row-wrap::before { left:0; background:linear-gradient(to right,var(--bg),transparent); }
.row-wrap::after { right:0; background:linear-gradient(to left,var(--bg),transparent); }
.row-wrap.can-left::before, .row-wrap.can-right::after { opacity:1; }

.empty-section { text-align:center; padding:60px 20px; color:#666; }
.empty-section i { font-size:4em; margin-bottom:20px; color:#333; }
.empty-section h3 { font-size:1.5em; margin-bottom:10px; color:#999; }

/* ===== Footer ===== */
.footer { background:#0f0f0f; padding:2.5rem 0 1rem; margin-top:2.5rem; border-top:1px solid var(--border); }
.footer-content { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem 2rem; margin-bottom:1.8rem; }
.footer-section h4 { color:var(--red); margin-bottom:.8rem; font-size:1.1rem; }
.footer-section p { color:#ccc; font-size:.92rem; }
.footer-section a { display:block; color:#ccc; text-decoration:none; margin-bottom:.45rem; font-size:.92rem; transition:color .2s; }
.footer-section a:hover { color:var(--red); }
.social-links { display:flex; gap:1rem; }
.social-links a { font-size:1.5rem; display:inline-block; margin-bottom:0; }
.footer-bottom { border-top:1px solid var(--border); padding-top:1rem; text-align:center; color:#888; font-size:.85rem; }

/* ===== Modal & popup ===== */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.8); z-index:9999; justify-content:center; align-items:center; padding:16px; }
.modal-content { background:linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 100%); padding:32px 28px; border-radius:15px; text-align:center; width:min(100%,400px); box-shadow:0 10px 50px rgba(229,9,20,.5); animation:modalSlideIn .3s ease; }
@keyframes modalSlideIn { from { transform:translateY(-50px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.modal-content i { font-size:3.4em; color:var(--red); margin-bottom:16px; }
.modal-content h3 { font-size:1.5em; margin-bottom:10px; }
.modal-content p { color:#ccc; margin-bottom:22px; }
.modal-close-btn { background:var(--red); color:#fff; border:none; padding:11px 30px; border-radius:6px; font-size:1em; font-weight:600; cursor:pointer; transition:background .2s; }
.modal-close-btn:hover { background:var(--red-dark); }
.login-success-popup { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 100%); padding:34px 28px; width:min(90%,420px); border-radius:15px; box-shadow:0 10px 50px rgba(229,9,20,.5); z-index:9999; text-align:center; border:2px solid var(--red); display:none; }
.login-success-popup i { font-size:3.6em; color:#00ff40; margin-bottom:16px; }
.login-success-popup h2 { font-size:1.7em; margin-bottom:8px; color:var(--red); }
.login-success-popup p { font-size:1.15em; color:#ccc; }
.popup-overlay { position:fixed; inset:0; background:rgba(0,0,0,.8); z-index:9998; display:none; }

/* ===== Responsif ===== */
@media (max-width:720px) {
    .nav-brand { order:1; }
    /* tamu: baris 1 = logo + Masuk/Daftar, baris 2 = pencarian */
    .nav-guest .nav-menu { order:2; }
    .nav-guest .nav-search { order:3; flex:1 1 100%; max-width:none; margin-left:0; }
    /* user: baris 1 = logo + pencarian, baris 2 = menu (bisa digeser) */
    .nav-user .nav-search { order:2; flex:1 1 0; max-width:none; margin-left:0; }
    .nav-user .nav-menu { order:3; flex:1 1 100%; margin-left:0; overflow-x:auto; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
    .nav-user .nav-menu::-webkit-scrollbar { display:none; }
    .hero-buttons { flex-direction:column; align-items:center; }
    .hero-buttons .btn { width:100%; max-width:320px; }
    /* Top 3 jadi baris yang bisa digeser di layar kecil */
    .trending-grid { display:flex; overflow-x:auto; scroll-snap-type:x mandatory; gap:.9rem; padding-bottom:.6rem; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
    .trending-grid::-webkit-scrollbar { display:none; }
    .trending-grid .movie-card { flex:0 0 62%; scroll-snap-align:start; }
    .footer-content { text-align:center; }
    .social-links { justify-content:center; }
}
@media (max-width:400px) {
    .nav-link { padding:.4rem .65rem; font-size:.9rem; }
}
</style>
</head>
<body>
<nav class="navbar nav-guest">
    <div class="nav-container">
        <a class="nav-brand" href="index.php"><h1>CineScope</h1></a>
        <div class="nav-search">
            <input type="search" id="searchInput" placeholder="Cari film atau genre..." class="search-input" autocomplete="off">
            <button class="search-btn" id="searchBtn" aria-label="Cari"><i class="fas fa-search"></i></button>
        </div>
        <div class="nav-menu">
            <a href="login.php" class="nav-link">Masuk</a>
            <a href="bergabung_sekarang.php" class="nav-cta">Daftar</a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="hero-content">
        <h2>Jelajahi, Review, dan Bagikan Pengalaman Film Anda</h2>
        <p>Bergabunglah dengan komunitas pencinta film terbesar di Indonesia</p>
        <div class="hero-buttons">
            <a href="bergabung_sekarang.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Bergabung Sekarang</a>
            <a href="login.php" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Login</a>
        </div>
    </div>
</section>

<?php if (count($trendingMovies) > 0): ?>
<section class="section trending-section">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-fire"></i> Top 3 Film Trending</h2>
            <p class="section-sub">Film dengan komentar, like, dan rating tertinggi</p>
        </div>
        <div class="movies-grid trending-grid">
            <?php foreach ($trendingMovies as $i => $movie):
                echo renderMovieCard($movie, '<div class="trending-badge">🔥 Top ' . ($i + 1) . '</div>', '#666', generateMovieCardLink($movie));
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (count($allMovies) > 0): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-list"></i> Daftar Film</h2>
            <p class="section-sub">Geser ke samping untuk melihat film lainnya</p>
        </div>
        <?php foreach (splitIntoRows($allMovies) as $rowMovies):
            $cards = array();
            foreach ($rowMovies as $movie) {
                $badge = isset($topRank[$movie['id']]) ? '<div class="trending-badge">🔥 Top ' . $topRank[$movie['id']] . '</div>' : '';
                $cards[] = renderMovieCard($movie, $badge, '#0277bd', generateMovieCardLink($movie));
            }
            echo renderMovieRow($cards);
        endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (count($comingSoonMovies) > 0): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Coming Soon!</h2>
            <p class="section-sub">Film yang akan segera hadir</p>
        </div>
        <?php
            $cards = array();
            foreach ($comingSoonMovies as $movie) { $cards[] = renderComingSoonCard($movie); }
            echo renderMovieRow($cards);
        ?>
    </div>
</section>
<?php endif; ?>

<div class="modal-overlay" id="comingSoonModal">
    <div class="modal-content">
        <i class="fas fa-calendar-times"></i>
        <h3>Film Belum Dirilis</h3>
        <p>Maaf, film ini belum dirilis. Nantikan segera!</p>
        <button class="modal-close-btn" onclick="closeComingSoonModal()">Tutup</button>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>CineScope</h4>
                <p>Platform review film terbaik untuk komunitas pencinta sinema Indonesia</p>
            </div>
            <div class="footer-section">
                <h4>Tautan</h4>
                <a href="<?php echo cs_href('tentang kami.php'); ?>">Tentang Kami</a>
                <a href="<?php echo cs_href('kebijakan privasi.php'); ?>">Kebijakan Privasi</a>
                <a href="<?php echo cs_href('syarat & ketentuan.php'); ?>">Syarat &amp; Ketentuan</a>
            </div>
            <div class="footer-section">
                <h4>Ikuti Kami</h4>
                <div class="social-links">
                    <a href="https://x.com/piesekkn" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.facebook.com/share/17aUUywEem/" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/fernandokinansyah_" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 CineScope. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
function showComingSoonAlert(event) {
    event.preventDefault();
    document.getElementById('comingSoonModal').style.display = 'flex';
}
function closeComingSoonModal() {
    document.getElementById('comingSoonModal').style.display = 'none';
}
window.addEventListener('click', function (event) {
    var modal = document.getElementById('comingSoonModal');
    if (event.target === modal) modal.style.display = 'none';
});
function esc(s) { return s.replace(/[&<>"']/g, function (c) { return ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"})[c]; }); }

// ===== Baris film: geser dengan jari (HP) / drag mouse atau trackpad (laptop) =====
document.querySelectorAll('.row-wrap').forEach(function (wrap) {
    var row = wrap.querySelector('.movie-row');

    function updateEdges() {
        var max = row.scrollWidth - row.clientWidth;
        wrap.classList.toggle('can-left', row.scrollLeft > 4);
        wrap.classList.toggle('can-right', row.scrollLeft < max - 4);
    }
    row.addEventListener('scroll', updateEdges, { passive: true });
    window.addEventListener('resize', updateEdges);
    window.addEventListener('load', updateEdges);
    updateEdges();

    var down = false, moved = false, startX = 0, startScroll = 0;
    row.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        down = true; moved = false; startX = e.pageX; startScroll = row.scrollLeft;
    });
    window.addEventListener('mousemove', function (e) {
        if (!down) return;
        var dx = e.pageX - startX;
        if (Math.abs(dx) > 5) { moved = true; row.classList.add('dragging'); }
        if (moved) row.scrollLeft = startScroll - dx;
    });
    window.addEventListener('mouseup', function () { down = false; row.classList.remove('dragging'); });
    // setelah drag, jangan ikut membuka link film
    row.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
    row.addEventListener('dragstart', function (e) { e.preventDefault(); });
});

// ===== Pencarian =====
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('searchInput');
    var searchBtn = document.getElementById('searchBtn');
    var movieCards = document.querySelectorAll('.movie-card');
    var sections = document.querySelectorAll('.section, .hero, .intro');

    function performSearch() {
        var query = searchInput.value.toLowerCase().trim();
        var foundCount = 0;
        var seen = new Set();   // film yang sama (Top 3 + Daftar Film) hanya ditampilkan sekali
        var old = document.getElementById('searchResultsContainer');
        if (old) old.remove();

        if (query === '') {
            sections.forEach(function (s) { s.style.display = ''; });
            return;
        }
        sections.forEach(function (s) { s.style.display = 'none'; });

        var box = document.createElement('section');
        box.id = 'searchResultsContainer';
        box.className = 'section';
        box.style.scrollMarginTop = '90px';

        var container = document.createElement('div');
        container.className = 'container';
        var head = document.createElement('div');
        head.className = 'section-head';
        head.innerHTML = '<h2 class="section-title"><i class="fas fa-search"></i> Hasil Pencarian: "' + esc(query) + '"</h2>';
        container.appendChild(head);

        var grid = document.createElement('div');
        grid.className = 'movies-grid';

        movieCards.forEach(function (card) {
            var id = card.dataset.id;
            if (seen.has(id)) return;
            var t = card.querySelector('.movie-title');
            var g = card.querySelector('.movie-genre');
            var title = t ? t.textContent.toLowerCase() : '';
            var genre = g ? g.textContent.toLowerCase() : '';
            if (title.indexOf(query) !== -1 || genre.indexOf(query) !== -1) {
                seen.add(id);
                grid.appendChild(card.cloneNode(true));
                foundCount++;
            }
        });

        if (foundCount === 0) {
            var none = document.createElement('div');
            none.className = 'empty-section';
            none.innerHTML = '<i class="fas fa-search"></i><h3>Tidak Ditemukan</h3><p>Film dengan kata kunci "<strong style="color:#e50914;">' + esc(query) + '</strong>" tidak ditemukan.</p>';
            container.appendChild(none);
        } else {
            var count = document.createElement('p');
            count.className = 'section-sub';
            count.style.marginBottom = '1rem';
            count.textContent = 'Ditemukan ' + foundCount + ' film';
            container.appendChild(count);
            container.appendChild(grid);
        }

        box.appendChild(container);
        var anchor = document.querySelector('.hero') || document.querySelector('.intro') || document.querySelector('.navbar');
        anchor.insertAdjacentElement('afterend', box);
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
        searchInput.addEventListener('keypress', function (e) { if (e.key === 'Enter') { e.preventDefault(); performSearch(); } });
    }
    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) { e.preventDefault(); performSearch(); });
    }
});

// Kalau halaman dibuka lagi lewat tombol Back, popup selamat datang tidak boleh muncul lagi
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        ['loginSuccessPopup', 'popupOverlay'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentNode) el.parentNode.removeChild(el);
        });
    }
});
</script>

</body>
</html>