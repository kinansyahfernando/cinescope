<?php
// File: user.php (USER VERSION - Bisa Review & Rating)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi + session dari config.php (database: movie_review_db)
require __DIR__ . '/config.php';
$pdo = db();

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserName = $_SESSION['username'] ?? 'Guest';
$loginSuccess = isset($_GET['login_success']) ? htmlspecialchars($_GET['login_success']) : null;

function getPosterPath($posterFromDB) {
    if (empty($posterFromDB)) return null;
    $possiblePaths = array(__DIR__ . '/' . $posterFromDB, $posterFromDB);
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) return $posterFromDB;
    }
    return null;
}

function generateMovieCardLink($movie) {
    $movieLinks = array(
        1 => 'film.sore.php',
        2 => 'Petaka Gunung Gede.php',
        3 => 'Dilan 1990.php',
        4 => 'rest area.php',
        5 => 'tukar takdir.php',
        6 => 'rangga & cinta.php'
    );
    return isset($movieLinks[$movie['id']]) ? $movieLinks[$movie['id']] : 'all_reviews.php?id=' . (int)$movie['id'];
}

// ==========================================
// 🔥 RANKING FILM (semua film berstatus 'Sudah Rilis' / Daftar Film)
// Skor = (jumlah komentar x 2 + jumlah like) x rata-rata rating
// - Komentar & like banyak  -> skor naik
// - Komentar bernilai rendah -> skor tertahan (dikali rata-rata rating)
// Semua 6 film dihitung ulang tiap halaman dibuka, jadi film di "Daftar Film"
// yang skornya melonjak otomatis naik ke Top 3, dan sebaliknya.
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
        // Jika tabel/kolom like tidak ada, tetap jalan tanpa like
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

$rankedMovies      = fetchRankedMovies($pdo);
$trendingMovies    = array_slice($rankedMovies, 0, 3);   // Top 3
// ==========================================
// 📋 DAFTAR FILM - SEMUA film rilis ditampilkan (termasuk yang masuk Top 3).
// Film terbaru (id terbesar) otomatis di urutan pertama baris pertama.
// ==========================================
$listMovies = $rankedMovies;
usort($listMovies, function ($a, $b) {
    // Terbaru dulu: berdasarkan waktu upload (created_at), lalu id. Film upload baru otomatis di urutan pertama.
    return [(string)($b['created_at'] ?? ''), (int)$b['id']] <=> [(string)($a['created_at'] ?? ''), (int)$a['id']];
});

$recommendedMovies = $listMovies;   // Daftar Film = semua film rilis

// Bagi film ke beberapa baris supaya tampilan seimbang:
// - target maksimal 10 film per baris (batas keras 15)
// - minimal 2 baris kalau film >= 2, jadi baris pertama tidak pernah penuh sendirian
// - sisa pembagian diberikan ke baris paling atas (baris 1 selalu >= baris berikutnya)
// Contoh: 6 -> 3+3 | 10 -> 5+5 | 14 -> 7+7 | 20 -> 10+10 | 21 -> 7+7+7
function splitIntoRows(array $items, $target = 10, $hardMax = 15) {
    $n = count($items);
    if ($n === 0) return array();
    $rows = ($n < 2) ? 1 : max(2, (int)ceil($n / $target));
    $rows = max($rows, (int)ceil($n / $hardMax));
    $base = intdiv($n, $rows);
    $extra = $n % $rows;
    $out = array();
    $offset = 0;
    for ($i = 0; $i < $rows; $i++) {
        $size = $base + ($i < $extra ? 1 : 0);
        $out[] = array_slice($items, $offset, $size);
        $offset += $size;
    }
    return $out;
}

// ==========================================
// 🎬 COMING SOON - hanya film yang diupload admin dengan status "Coming Soon".
// Tidak ada kartu placeholder: kalau kosong, section Coming Soon disembunyikan.
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
    $posterPath = getPosterPath($movie['poster']);
    ob_start(); ?>
    <div class="movie-card">
        <a href="<?php echo $link; ?>"<?php echo $link === '#' ? ' onclick="showComingSoonAlert(event)"' : ''; ?>>
            <?php if ($posterPath): ?>
                <img src="<?php echo htmlspecialchars($posterPath); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
            <?php else: ?>
                <div class="movie-poster" style="background:<?php echo $bg; ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;">🎬</div>
            <?php endif; ?>
            <?php echo $badgeHtml; ?>
        </a>
        <div class="movie-info">
            <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
            <div class="movie-genre"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></div>
            <div class="movie-stats">
                <span class="comments"><i class="fas fa-comment"></i> <?php echo (int)$movie['review_count']; ?> komentar</span>
                <span class="likes"><i class="fas fa-heart"></i> <?php echo (int)$movie['like_count']; ?> like</span>
            </div>
            <div class="movie-rating">⭐ <?php echo number_format($movie['avg_rating'], 1); ?>/5.0</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
<title>CineScope - Movie Review Community</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; line-height: 1.6; background-color: #0a0a0a; color: #ffffff; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.navbar { background-color: #111; padding: 1rem 0; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #333; }
.nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
.nav-brand h1 { color: #e50914; font-size: 1.8rem; font-weight: 700; }
.nav-search { display: flex; align-items: center; flex: 1; max-width: 400px; margin: 0 2rem; }
.search-input { flex: 1; padding: 0.8rem 1rem; border: 1px solid #333; border-radius: 4px 0 0 4px; background-color: #1a1a1a; color: white; font-size: 0.9rem; outline: none; }
.search-btn { padding: 0.8rem 1rem; background-color: #e50914; border: none; border-radius: 0 4px 4px 0; color: white; cursor: pointer; }
.nav-menu { display: flex; align-items: center; gap: 2rem; }
.nav-link { color: #ccc; text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
.nav-link:hover { color: #e50914; }
.section { padding: 4rem 0; }
.section-title { font-size: 2rem; margin-bottom: 2rem; color: white; text-align: center; }
.section-title i { color: #e50914; margin-right: 10px; }
.movies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem; }
.movie-card { background-color: #1a1a1a; border-radius: 8px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; position: relative; }
.movie-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(229, 9, 20, 0.3); }
.movie-card a { display: block; position: relative; padding-top: 150%; overflow: hidden; }
.movie-poster { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.movie-info { padding: 1rem; }
.movie-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem; color: white; }
.movie-genre { color: #999; font-size: 0.9rem; margin-bottom: 0.5rem; }
.movie-rating { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #ffc107; font-weight: 600; }
.trending-badge, .new-badge { position: absolute; top: 10px; right: 10px; background-color: rgba(229, 9, 20, 0.9); color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; z-index: 10; }
.empty-section { text-align: center; padding: 60px 20px; color: #666; }
.empty-section i { font-size: 4em; margin-bottom: 20px; color: #333; }
.footer { background-color: #111; padding: 3rem 0 1rem; margin-top: 4rem; border-top: 1px solid #333; }
.footer-content { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
.footer-section h4 { color: #e50914; margin-bottom: 1rem; font-size: 1.2rem; }
.footer-section p { color: #ccc; line-height: 1.6; }
.footer-section a { display: block; color: #ccc; text-decoration: none; margin-bottom: 0.5rem; transition: color 0.3s ease; }
.footer-section a:hover { color: #e50914; }
.social-links { display: flex; gap: 1rem; }
.social-links a { font-size: 1.5rem; }
.footer-bottom { border-top: 1px solid #333; padding-top: 1rem; text-align: center; color: #888; }
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; justify-content: center; align-items: center; }
.modal-content { background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); padding: 40px; border-radius: 15px; text-align: center; max-width: 400px; box-shadow: 0 10px 50px rgba(229, 9, 20, 0.5); }
.modal-content i { font-size: 4em; color: #e50914; margin-bottom: 20px; }
.modal-content h3 { font-size: 1.8em; margin-bottom: 15px; color: #fff; }
.modal-content p { color: #ccc; font-size: 1.1em; margin-bottom: 25px; }
.modal-close-btn { background: #e50914; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 1em; font-weight: 600; cursor: pointer; }
.login-success-popup { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); padding: 40px 60px; border-radius: 15px; box-shadow: 0 10px 50px rgba(229, 9, 20, 0.5); z-index: 9999; text-align: center; border: 2px solid #e50914; display: none; }
.login-success-popup i { font-size: 4em; color: #00ff40; margin-bottom: 20px; }
.login-success-popup h2 { font-size: 2em; margin-bottom: 10px; color: #e50914; }
.login-success-popup p { font-size: 1.2em; color: #ccc; }
.popup-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9998; display: none; }
@media (max-width: 768px) {
    .nav-container { flex-direction: column; gap: 1rem; }
    .nav-search { margin: 1rem 0; max-width: 100%; }
    .movies-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
    .footer-content { grid-template-columns: 1fr; text-align: center; }
}
.movie-stats { display: flex; align-items: center; gap: 0.9rem; flex-wrap: wrap; margin-bottom: 0.6rem; color: #999; font-size: 0.85rem; }
.movie-stats .likes { color: #ff4d6d; font-weight: 600; }
.movie-stats .comments { color: #9ad; font-weight: 600; }
.coming-placeholder { background: linear-gradient(160deg, #2a2a2a, #151515); flex-direction: column; gap: 10px; text-align: center; padding: 10px; }
.coming-placeholder small { font-size: 0.8rem; color: #888; }

/* ===== Daftar Film: baris yang bisa digeser horizontal (tanpa tombol panah) ===== */
.movie-row { display: flex; gap: 2rem; overflow-x: auto; overflow-y: hidden; padding: 10px 4px 22px; margin: 0 -4px; -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; scroll-snap-type: x proximity; scrollbar-width: none; -ms-overflow-style: none; cursor: grab; }
.movie-row::-webkit-scrollbar { display: none; }
.movie-row .movie-card { flex: 0 0 clamp(200px, calc((100% - 6rem) / 4), 300px); scroll-snap-align: start; }
.movie-row.dragging { cursor: grabbing; scroll-snap-type: none; }
.movie-row img { -webkit-user-drag: none; user-select: none; }
</style>
</head>
<body>

<?php if ($loginSuccess): ?>
<div class="popup-overlay" id="popupOverlay" style="display:block;"></div>
<div class="login-success-popup" id="loginSuccessPopup" style="display:block;">
    <i class="fas fa-check-circle"></i>
    <h2>SELAMAT DATANG</h2>
    <p class="username"><?php echo $loginSuccess; ?></p>
</div>
<script>
setTimeout(function() {
    document.getElementById('loginSuccessPopup').style.display = 'none';
    document.getElementById('popupOverlay').style.display = 'none';
}, 5000);
</script>
<?php endif; ?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand"><h1>CineScope</h1></div>
        <div class="nav-search">
            <input type="text" id="searchInput" placeholder="Cari film..." class="search-input">
            <button class="search-btn" id="searchBtn"><i class="fas fa-search"></i></button>
        </div>
        <div class="nav-menu">
            <a href="jelajahi.php" class="nav-link">Jelajahi</a>
            <a href="daftar tonton.php" class="nav-link">Daftar Tonton</a>
            <a href="artikel.php" class="nav-link">Artikel</a>
            <a href="logout.php" class="nav-link">Akun</a>
        </div>
    </div>
</nav>
<?php if (count($trendingMovies) > 0): ?>
<section class="section trending-section">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-fire"></i> Top 3 Film Trending</h2>
        <div class="movies-grid">
            <?php foreach ($trendingMovies as $i => $movie):
                echo renderMovieCard($movie, '<div class="trending-badge">🔥 Top ' . ($i + 1) . '</div>', '#666', generateMovieCardLink($movie));
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (count($recommendedMovies) > 0): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-list"></i> Daftar Film</h2>
        <?php foreach (splitIntoRows($recommendedMovies) as $rowMovies): ?>
        <div class="movie-row">
            <?php foreach ($rowMovies as $movie):
                echo renderMovieCard($movie, '<div class="new-badge"><i class="fas fa-star"></i> Daftar Film</div>', '#0277bd', generateMovieCardLink($movie));
            endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (count($comingSoonMovies) > 0): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Coming Soon!</h2>
        <div class="movies-grid">
            <?php foreach ($comingSoonMovies as $movie): $posterPath = getPosterPath($movie['poster']); ?>
                <div class="movie-card">
                    <a href="#" onclick="showComingSoonAlert(event)">
                        <?php if ($posterPath): ?>
                            <img src="<?php echo htmlspecialchars($posterPath); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                        <?php else: ?>
                            <div class="movie-poster" style="background:#558b2f;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;">🎬</div>
                        <?php endif; ?>
                        <div class="new-badge"><i class="fas fa-clock"></i> Coming Soon</div>
                    </a>
                    <div class="movie-info">
                        <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
                        <div class="movie-genre"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></div>
                        <div class="movie-rating"><?php echo !empty($movie['release_date']) ? '📅 Rilis ' . formatTanggalID($movie['release_date']) : '⏳ Segera Hadir'; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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
                <a href="tentang kami.php">Tentang Kami</a>
                <a href="kebijakan privasi.php">Kebijakan Privasi</a>
                <a href="syarat & ketentuan.php">Syarat & Ketentuan</a>
            </div>
            <div class="footer-section">
                <h4>Ikuti Kami</h4>
                <div class="social-links">
                    <a href="https://x.com/piesekkn" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.facebook.com/share/17aUUywEem/" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/fernandokinansyah_" target="_blank"><i class="fab fa-instagram"></i></a>
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
window.onclick = function(event) {
    const modal = document.getElementById('comingSoonModal');
    if (event.target === modal) modal.style.display = 'none';
}
function esc(s) { return s.replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c])); }
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const searchBtn = document.getElementById("searchBtn");
    const movieCards = document.querySelectorAll(".movie-card");
    const sections = document.querySelectorAll(".section");

    function performSearch() {
        const query = searchInput.value.toLowerCase().trim();
        let foundCount = 0;
        let searchResultsContainer = document.getElementById("searchResultsContainer");
        if (searchResultsContainer) searchResultsContainer.remove();

        if (query === "") {
            sections.forEach(s => s.style.display = "");
            movieCards.forEach(c => c.style.display = "");
            return;
        }

        sections.forEach(s => s.style.display = "none");

        searchResultsContainer = document.createElement("section");
        searchResultsContainer.id = "searchResultsContainer";
        searchResultsContainer.className = "section";
        searchResultsContainer.style.cssText = "padding: 2rem 0; margin-top: 20px;";

        const container = document.createElement("div");
        container.className = "container";
        const resultTitle = document.createElement("h2");
        resultTitle.className = "section-title";
        resultTitle.style.cssText = "text-align: left; margin-bottom: 1.5rem;";
        resultTitle.innerHTML = `<i class="fas fa-search"></i> Hasil Pencarian: "${esc(query)}"`;
        container.appendChild(resultTitle);

        const moviesGrid = document.createElement("div");
        moviesGrid.className = "movies-grid";
        moviesGrid.style.cssText = "margin-top: 1rem;";

        const seenTitles = new Set();   // film Top 3 juga ada di Daftar Film, jangan tampil dobel
        movieCards.forEach(card => {
            const title = card.querySelector(".movie-title")?.textContent.toLowerCase() || "";
            const genre = card.querySelector(".movie-genre")?.textContent.toLowerCase() || "";
            if ((title.includes(query) || genre.includes(query)) && !seenTitles.has(title)) {
                seenTitles.add(title);
                const clonedCard = card.cloneNode(true);
                clonedCard.style.display = "";
                moviesGrid.appendChild(clonedCard);
                foundCount++;
            }
        });

        if (foundCount === 0) {
            const noResult = document.createElement("div");
            noResult.className = "empty-section";
            noResult.style.cssText = "padding: 80px 20px; text-align: center;";
            noResult.innerHTML = `
                <i class="fas fa-search" style="font-size: 5em; margin-bottom: 25px; color: #333;"></i>
                <h3 style="font-size: 1.8em; margin-bottom: 15px; color: #999;">Tidak Ditemukan</h3>
                <p style="color: #666; font-size: 1.1em;">Film dengan kata kunci "<strong style="color:#e50914;">${esc(query)}</strong>" tidak ditemukan.</p>
            `;
            container.appendChild(noResult);
        } else {
            const resultCount = document.createElement("p");
            resultCount.style.cssText = "color: #aaa; margin-bottom: 20px; font-size: 0.95em;";
            resultCount.textContent = `Ditemukan ${foundCount} film`;
            container.appendChild(resultCount);
            container.appendChild(moviesGrid);
        }

        searchResultsContainer.appendChild(container);
        document.querySelector(".hero").insertAdjacentElement("afterend", searchResultsContainer);
        searchResultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", performSearch);
        searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") { e.preventDefault(); performSearch(); }
        });
    }
    if (searchBtn) {
        searchBtn.addEventListener("click", (e) => { e.preventDefault(); performSearch(); });
    }
});

// Geser horizontal dengan mouse (laptop). Di HP cukup swipe biasa.
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".movie-row").forEach(row => {
        let down = false, moved = false, startX = 0, startScroll = 0;
        row.addEventListener("mousedown", e => {
            if (e.button !== 0) return;
            down = true; moved = false; startX = e.pageX; startScroll = row.scrollLeft;
        });
        window.addEventListener("mousemove", e => {
            if (!down) return;
            const dx = e.pageX - startX;
            if (Math.abs(dx) > 5) { moved = true; row.classList.add("dragging"); }
            if (moved) { row.scrollLeft = startScroll - dx; e.preventDefault(); }
        });
        window.addEventListener("mouseup", () => { down = false; row.classList.remove("dragging"); });
        row.addEventListener("click", e => { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
        row.addEventListener("dragstart", e => e.preventDefault());
    });
});
</script>

</body>
</html>