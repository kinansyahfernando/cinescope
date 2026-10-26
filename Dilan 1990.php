<?php
// File: Dilan 1990.php (FIXED - User bisa rating & komentar)
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Halaman ini dipakai Dilan 1990 (ID 3) DAN film lain lewat ?id= (film dummy / film upload admin).
$MOVIE_ID = isset($_GET['id']) ? (int)$_GET['id'] : 3;
$IS_DILAN = ($MOVIE_ID === 3);

// 🔥 FIX: Pastikan user_id valid
$CURRENT_USER_NAME = $_SESSION['username'] ?? $_SESSION['full_name'] ?? 'Pengguna CineScope';
$USER_ID = $_SESSION['user_id'] ?? null;

// Database Configuration

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Error Koneksi Database: " . $e->getMessage());
}

// 🔥 FIX: Jika user_id null, coba cari user berdasarkan username
if ($USER_ID === null && isset($_SESSION['username'])) {
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $userStmt->execute([$_SESSION['username']]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $USER_ID = $userRow['id'];
        $_SESSION['user_id'] = $USER_ID;
    }
}

// 🔥 FIX: Jika masih null, buat user guest atau gunakan user pertama yang ada
if ($USER_ID === null) {
    $guestStmt = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    $guestRow = $guestStmt->fetch(PDO::FETCH_ASSOC);
    if ($guestRow) {
        $USER_ID = $guestRow['id'];
    } else {
        // Buat user guest jika belum ada
        $pdo->exec("INSERT INTO users (username, full_name, email, password) VALUES ('guest', 'Pengguna CineScope', 'guest@cinescope.com', '')");
        $USER_ID = $pdo->lastInsertId();
    }
}

// AMBIL DATA FILM DARI DATABASE
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$MOVIE_ID]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

// Film tidak ada di database: untuk film lain tampilkan 404 (Dilan tetap memakai data bawaan di bawah)
if (!$movie && !$IS_DILAN) {
    http_response_code(404);
    die('<!DOCTYPE html><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Film tidak ditemukan - CineScope</title><body style="font-family:sans-serif;background:#0a0a0a;color:#fff;text-align:center;padding:80px 20px"><h1>Film tidak ditemukan</h1><p style="color:#aaa;margin:12px 0 20px">Film yang kamu cari tidak ada atau sudah dihapus.</p><a style="color:#e50914;font-weight:700" href="user.php">&larr; Kembali ke beranda</a></body>');
}

$defaultTitle    = $IS_DILAN ? 'Dilan 1990' : '';
$defaultPoster   = $IS_DILAN ? 'uploads/posters/1763311429.png' : '';
$defaultYear     = $IS_DILAN ? '2018' : '';
$defaultGenre    = $IS_DILAN ? 'Drama, Romantis' : '';
$defaultDuration = $IS_DILAN ? '1h 50m' : '';
$defaultDirector = $IS_DILAN ? 'Fajar Bustomi' : '';
$defaultCast     = $IS_DILAN ? 'Iqbaal Ramadhan, Vanesha Prescilla' : '';

$title = $movie['title'] ?? $defaultTitle;
$posterPath = ($movie['poster'] ?? '') !== '' ? $movie['poster'] : $defaultPoster;
$releaseYear = $movie['release_year'] ?? $defaultYear;
$genre = $movie['genre'] ?? $defaultGenre;
$duration = $movie['duration'] ?? $defaultDuration;
if (is_numeric($duration) && $duration !== '') $duration .= ' menit';
$director = ($movie['director'] ?? '') !== '' ? $movie['director'] : $defaultDirector;
$cast = ($movie['cast'] ?? '') !== '' ? $movie['cast'] : $defaultCast;
$synopsis = trim((string)($movie['synopsis'] ?? ''));

// Poster: kalau filenya tidak ada (mis. film dummy belum punya poster) tampilkan kotak placeholder
$posterOk = ($posterPath !== '' && is_file(__DIR__ . '/' . $posterPath));

// Trailer: Dilan memakai file bawaannya; film lain memakai trailer_url dari admin (URL / file upload)
$trailerHref = 'uploads/videos/Official Trailer Dilan 1990.mp4';
if (!$IS_DILAN) {
    $tu = trim((string)($movie['trailer_url'] ?? ''));
    $trailerHref = ($tu !== '' && (preg_match('#^https?://#i', $tu) || is_file(__DIR__ . '/' . $tu))) ? $tu : '';
}

// Link "Lihat Semua Ulasan" + ID watchlist
$allReviewsLink = $IS_DILAN ? 'ulasan_dilan1990.php' : 'ulasan_sore.php?id=' . $MOVIE_ID;
$watchId        = $IS_DILAN ? 'DILAN_1990' : 'MOVIE_' . $MOVIE_ID;

// 🔥 FIX: Render HTML untuk SEMUA ulasan - perbaiki rendering bintang
function generate_reviews_html($reviews, $default_user_name) {
    ob_start();
    if (count($reviews) > 0): 
        foreach ($reviews as $review): ?>
            <div class="review-item">
                <div class="rating-display">
                    <?php 
                        $starHtml = '';
                        $ratingValue = (int)$review['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            $color = $i <= $ratingValue ? '#ffc107' : '#444';
                            $starHtml .= '<i class="fas fa-star" style="color: ' . $color . '; font-size: 1.1rem;"></i>';
                        }
                        echo $starHtml;
                    ?>
                </div>
                <p><?php echo htmlspecialchars($review['review_text'] ?: '(Tidak ada ulasan tertulis)'); ?></p>
                <div class="reviewer-info">
                    Oleh: <?php echo htmlspecialchars($review['display_name']); ?> 
                    - 
                    <?php echo date('d M Y, H:i', strtotime($review['created_at'])); ?>
                </div>
            </div>
        <?php endforeach;
    else: ?>
        <p style="color:#777; font-style: italic;">Belum ada ulasan untuk film ini.</p>
    <?php endif;
    
    return ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_ajax']) && $_POST['is_ajax'] == 1) {
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];
    $rating = (float)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['reviewText'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $response['message'] = 'Rating harus diisi antara 1 sampai 5 bintang!';
        echo json_encode($response);
        exit;
    }

    try {
        // Pastikan user ada di tabel users
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->execute([$USER_ID]);
        if (!$checkUser->fetch()) {
            // Buat user jika tidak ada
            $pdo->exec("INSERT INTO users (id, username, full_name, email, password) VALUES ($USER_ID, 'user$USER_ID', 'Pengguna CineScope', 'user$USER_ID@cinescope.com', '')");
        }

        $sql = "INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $executeResult = $stmt->execute([$MOVIE_ID, $USER_ID, $rating, $reviewText]);
        
        if (!$executeResult) {
            throw new Exception("❌ Gagal menyimpan ulasan!");
        }

        // Perbarui rata-rata rating film (dipakai halaman admin)
        try {
            $pdo->prepare("UPDATE movies SET average_rating = (SELECT COALESCE(ROUND(AVG(rating),1),0) FROM reviews WHERE movie_id = ?) WHERE id = ?")
                ->execute([$MOVIE_ID, $MOVIE_ID]);
        } catch (PDOException $e) { /* kolom belum ada: abaikan */ }
        
        $response['success'] = true;
        $response['message'] = 'Ulasan Anda berhasil ditambahkan!';

    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Ambil SEMUA ulasan
$allReviewsStmt = $pdo->prepare("
    SELECT r.id, r.movie_id, r.user_id, r.rating, r.review_text, r.created_at, r.user_name as review_user_name,
    COALESCE(u.full_name, u.username, r.user_name, 'Pengguna CineScope') as display_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");
$allReviewsStmt->execute([$MOVIE_ID]);
$allReviews = $allReviewsStmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$totalReviews = count($allReviews);
$avgRating = $totalReviews > 0 ? array_sum(array_column($allReviews, 'rating')) / $totalReviews : 0;
$formattedAvgRating = $avgRating > 0 ? number_format($avgRating, 1) : '0.0';

if (isset($_GET['fetch_data']) && $_GET['fetch_data'] == 1) {
    header('Content-Type: application/json');
    $response = [
        'averageRating' => $formattedAvgRating,
        'totalReviews' => $totalReviews,
        'myReviewsHtml' => generate_reviews_html($allReviews, $CURRENT_USER_NAME),
    ];
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Beri Review Film <?= htmlspecialchars($title); ?> - CineScope</title>
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
        body { font-family: 'Inter', sans-serif; background-color: var(--background-dark); color: var(--text-light); line-height: 1.6; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; background-color: var(--card-dark); border-radius: 12px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.5); }
        .back-to-home { display: inline-block; background-color: #333; color: var(--text-light); padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-bottom: 20px; transition: background-color 0.3s; cursor: pointer; border: none; font-weight: 600; }
        .back-to-home:hover { background-color: #555; }
        .movie-header { display: flex; gap: 20px; margin-bottom: 30px; align-items: flex-start; }
        .movie-poster { width: 150px; height: 225px; object-fit: cover; border-radius: 8px; }
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
        textarea { width: 100%; min-height: 120px; padding: 10px; border-radius: 4px; border: 1px solid #444; background-color: var(--input-bg); color: var(--text-light); resize: vertical; }
        .submit-btn { background-color: var(--primary-color); color: var(--text-light); padding: 10px 25px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .submit-btn:hover { background-color: #f00; }
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
        .review-item p { margin-bottom: 10px; font-size: 1em; color: #e0e0e0; }
        .reviewer-info { font-size: 0.85em; color: #aaa; text-align: right; border-top: 1px dashed #555; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <button class="back-to-home" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        
        <section class="movie-header">
            <?php if ($posterOk): ?>
                <img src="<?= htmlspecialchars($posterPath); ?>" alt="Poster Film <?= htmlspecialchars($title); ?>" class="movie-poster">
            <?php else: ?>
                <div class="movie-poster" style="background: linear-gradient(135deg, #333 0%, #555 100%); display:flex; align-items:center; justify-content:center; font-size:3em; color:#888;"><i class="fas fa-film"></i></div>
            <?php endif; ?>
            <div class="movie-info">
                <h1><?= htmlspecialchars($title); ?></h1>
                <p><?= htmlspecialchars(implode(' • ', array_filter([(string)$releaseYear, (string)$genre, (string)$duration], function ($v) { return $v !== ''; }))); ?></p>
                <?php if ($director !== '' || $cast !== ''): ?>
                    <p><?php
                        $parts = [];
                        if ($director !== '') $parts[] = 'Sutradara: ' . htmlspecialchars($director);
                        if ($cast !== '')     $parts[] = 'Pemain: ' . htmlspecialchars($cast);
                        echo implode(' • ', $parts);
                    ?></p>
                <?php endif; ?>
                <?php if (!$IS_DILAN && $synopsis !== ''): ?><p><?= htmlspecialchars($synopsis); ?></p><?php endif; ?>
                
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <button id="likeButton" class="submit-btn like-btn" data-movie-id="<?= $watchId; ?>">
                        <i class="fas fa-thumbs-up"></i> <span id="likeText">Suka</span>
                    </button>
                    <a href="daftar tonton.php" class="submit-btn" style="text-decoration: none; display: inline-block; background-color: #333;"><i class="fas fa-bookmark"></i> Daftar Tonton</a>
                    
                    <a href="<?= htmlspecialchars($allReviewsLink); ?>" class="submit-btn review-link" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-eye"></i> Lihat Semua Ulasan
                    </a>
                </div>
                
                <div class="rating-summary" style="margin-top: 25px;">
                    <div class="rating-box">
                        <div class="score" id="averageRating"><?php echo $formattedAvgRating; ?>/5</div>
                        <div class="count">Rata-Rata</div>
                    </div>
                    <div class="rating-box">
                        <div class="score" id="reviewCount"><?php echo $totalReviews; ?></div>
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
                    <textarea id="reviewText" name="reviewText" placeholder="Tuliskan ulasan Anda tentang <?= htmlspecialchars($title); ?> di sini..."></textarea>
                </div>
                
                <button type="submit" class="submit-btn" id="submitButton">
                    <i class="fas fa-paper-plane"></i> Kirim Ulasan
                </button>
            </form>
        </section>
        
        <section class="my-reviews-section">
            <h2><i class="fas fa-comments"></i> Semua Ulasan</h2>
            <div id="my-reviews-display">
                <?php echo generate_reviews_html($allReviews, $CURRENT_USER_NAME); ?>
            </div>
        </section>
    </div>

    <script>
        const WATCHLIST_KEY = 'cineScopeWatchlist';
        const CURRENT_USER_EMAIL = localStorage.getItem('cineScopeCurrentUser') || 'guest@cinescope.com';
        const MOVIE_DATA = {
            id: '<?= $watchId; ?>',
            title: '<?= addslashes($title); ?>',
            poster: '<?= addslashes($posterPath); ?>',
            genre: '<?= addslashes($genre); ?>'
        };

        const reviewForm = document.getElementById('reviewForm');
        const submitButton = document.getElementById('submitButton');
        const averageRatingSpan = document.getElementById('averageRating');
        const reviewCountSpan = document.getElementById('reviewCount');
        const likeButton = document.getElementById('likeButton');
        const likeText = document.getElementById('likeText');
        const trailerButton = document.getElementById('trailerButton');

        const TRAILER_URL = <?= json_encode($trailerHref, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const SELF_URL = location.pathname + '?id=<?= $MOVIE_ID; ?>';   // halaman ini + ID film

        function getWatchlist() {
            const watchlistData = JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || {};
            return watchlistData[CURRENT_USER_EMAIL] || [];
        }

        function saveWatchlist(watchlist) {
            const watchlistData = JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || {};
            watchlistData[CURRENT_USER_EMAIL] = watchlist;
            localStorage.setItem(WATCHLIST_KEY, JSON.stringify(watchlistData));
        }

        function updateLikeButtonState(watchlist) {
            const isLiked = watchlist.some(movie => movie.id === MOVIE_DATA.id);
            
            if (isLiked) {
                likeButton.classList.add('liked');
                likeText.textContent = 'Disukai';
                likeButton.querySelector('i').className = 'fas fa-heart'; 
            } else {
                likeButton.classList.remove('liked');
                likeText.textContent = 'Suka';
                likeButton.querySelector('i').className = 'fas fa-thumbs-up'; 
            }
        }

        likeButton.addEventListener('click', function() {
            let watchlist = getWatchlist();
            const isLiked = watchlist.some(movie => movie.id === MOVIE_DATA.id);

            if (isLiked) {
                watchlist = watchlist.filter(movie => movie.id !== MOVIE_DATA.id);
                alert(`Film "${MOVIE_DATA.title}" dihapus dari Daftar Tonton.`);
            } else {
                watchlist.push(MOVIE_DATA);
                alert(`Film "${MOVIE_DATA.title}" ditambahkan ke Daftar Tonton!`);
            }

            saveWatchlist(watchlist);
            updateLikeButtonState(watchlist);
        });

        function loadReviewsAndStats() {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
            
            fetch(SELF_URL + '&fetch_data=1')
            .then(response => response.json())
            .then(data => {
                averageRatingSpan.textContent = data.averageRating + '/5';
                reviewCountSpan.textContent = data.totalReviews;
                document.getElementById('my-reviews-display').innerHTML = data.myReviewsHtml;
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';
            })
            .catch(error => {
                console.error('❌ Error:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';
            });
        }

        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const selectedRating = document.querySelector('input[name="rating"]:checked');
            if (!selectedRating) {
                alert("Harap berikan rating (bintang) sebelum mengirim ulasan!");
                return;
            }

            const formData = new URLSearchParams();
            formData.append('is_ajax', 1);
            formData.append('rating', selectedRating.value);
            formData.append('reviewText', document.getElementById('reviewText').value);

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

            fetch(SELF_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message); 
                    document.getElementById('reviewText').value = ''; 
                    document.querySelectorAll('input[name="rating"]').forEach(input => {
                        input.checked = false;
                    });
                    loadReviewsAndStats();
                } else {
                    alert('❌ ' + data.message);
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan'; 
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                alert('❌ Terjadi kesalahan jaringan. Cek console log.');
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            loadReviewsAndStats();
            const initialWatchlist = getWatchlist();
            updateLikeButtonState(initialWatchlist);
        });

        trailerButton.addEventListener('click', function() {
            if (!TRAILER_URL) { alert('Trailer untuk film ini belum tersedia.'); return; }
            window.open(TRAILER_URL, '_blank');
        });
    </script>
</body>
</html>