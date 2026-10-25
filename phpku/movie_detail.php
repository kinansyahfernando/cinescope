<?php
// File: movie_detail.php
// Simpan di: C:/xampp/htdocs/movie-review/movie_detail.php
// Akses: http://localhost/movie-review/movie_detail.php?id=7
// UNIVERSAL PAGE - Untuk semua film (terutama film baru dengan ID > 6)

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database Configuration
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

// Get movie ID dari URL
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movieId <= 0) {
    die("❌ ID Film tidak valid!");
}

// Fetch movie data dari database
$movieStmt = $pdo->prepare("SELECT * FROM movies WHERE id = :id");
$movieStmt->execute([':id' => $movieId]);
$movieData = $movieStmt->fetch();

if (!$movieData) {
    die("❌ Film tidak ditemukan!");
}

// Set variabel dari database
$movieTitle = $movieData['title'];
$moviePoster = $movieData['poster'] ?: 'uploads/posters/default.jpg';
$movieGenre = $movieData['genre'];
$movieYear = $movieData['release_year'];
$movieDuration = $movieData['duration'];
$movieDirector = $movieData['director'];
$movieSynopsis = $movieData['synopsis'];
$movieTrailer = $movieData['trailer_url'] ?? '';
$movieOriginalTitle = $movieData['original_title'] ?? '';

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['username'] ?? 'Guest';
$currentUserFullName = $_SESSION['full_name'] ?? $currentUserName;

$message = '';
$messageType = '';

// ==================== PROSES SUBMIT REVIEW ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$isLoggedIn) {
        $message = "❌ Anda harus login terlebih dahulu untuk memberikan review!";
        $messageType = 'error';
    } else {
        $rating = (int)$_POST['rating'];
        $reviewText = trim($_POST['review_text']);
        
        if ($rating < 1 || $rating > 5) {
            $message = "❌ Rating harus antara 1-5!";
            $messageType = 'error';
        } else {
            // Cek apakah user sudah pernah review film ini
            $checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = :user_id AND movie_id = :movie_id");
            $checkStmt->execute([
                ':user_id' => $currentUserId,
                ':movie_id' => $movieId
            ]);
            
            if ($checkStmt->fetch()) {
                $message = "⚠️ Anda sudah memberikan review untuk film ini.";
                $messageType = 'warning';
            } else {
                // Insert review baru
                $insertStmt = $pdo->prepare("INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at) 
                                             VALUES (:movie_id, :user_id, :rating, :review_text, NOW())");
                $insertStmt->execute([
                    ':movie_id' => $movieId,
                    ':user_id' => $currentUserId,
                    ':rating' => $rating,
                    ':review_text' => $reviewText
                ]);
                
                // Update average rating di tabel movies
                $avgStmt = $pdo->prepare("UPDATE movies SET average_rating = (
                    SELECT AVG(rating) FROM reviews WHERE movie_id = :movie_id
                ) WHERE id = :movie_id");
                $avgStmt->execute([':movie_id' => $movieId]);
                
                $message = "✅ Review Anda berhasil ditambahkan!";
                $messageType = 'success';
            }
        }
    }
}

// ==================== FETCH REVIEW DATA ====================
// Get average rating & review count
$statsStmt = $pdo->prepare("SELECT 
    COUNT(*) as review_count,
    COALESCE(AVG(rating), 0) as avg_rating
    FROM reviews WHERE movie_id = :movie_id");
$statsStmt->execute([':movie_id' => $movieId]);
$stats = $statsStmt->fetch();

$reviewCount = $stats['review_count'];
$avgRating = round($stats['avg_rating'], 1);

// Get user's reviews only (jika sudah login)
$myReviews = [];
if ($isLoggedIn) {
    $myReviewsStmt = $pdo->prepare("SELECT r.*, u.username, u.full_name 
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.movie_id = :movie_id AND r.user_id = :user_id
        ORDER BY r.created_at DESC");
    $myReviewsStmt->execute([
        ':movie_id' => $movieId,
        ':user_id' => $currentUserId
    ]);
    $myReviews = $myReviewsStmt->fetchAll();
}

// Get ALL reviews untuk link "Lihat Semua"
$allReviewsCount = $reviewCount;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movieTitle); ?> - CineScope</title>
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
        
        .back-to-home {
            display: inline-block;
            background-color: #333;
            color: var(--text-light);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s;
            cursor: pointer;
            border: none;
            font-weight: 600;
        }
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
        
        textarea {
            width: 100%; min-height: 120px; padding: 10px; border-radius: 4px; border: 1px solid #444;
            background-color: var(--input-bg); color: var(--text-light); resize: vertical;
        }

        .submit-btn {
            background-color: var(--primary-color); color: var(--text-light); padding: 10px 25px;
            border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: background-color 0.3s;
        }
        .submit-btn:hover { background-color: #f00; }

        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5em; color: #444; cursor: pointer; padding: 0 5px; transition: color 0.2s; }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label { color: var(--star-gold); }
        
        .my-reviews-section { margin-bottom: 40px; }
        .my-reviews-section h2 { font-size: 1.8em; color: var(--text-light); margin-bottom: 25px; border-bottom: 2px solid var(--star-gold); padding-bottom: 5px; }

        .review-item {
            background-color: #333; padding: 15px 20px; border-radius: 6px; margin-bottom: 15px;
            border-left: 5px solid var(--primary-color); 
        }
        .rating-display { margin-bottom: 10px; }
        .review-item p { margin-bottom: 10px; font-size: 1em; color: #e0e0e0; }
        .reviewer-info { font-size: 0.85em; color: #aaa; text-align: right; border-top: 1px dashed #555; padding-top: 5px; }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .login-notice {
            background: #333;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px dashed #666;
        }
        
        .login-notice a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-notice a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <a href="user.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <section class="movie-header">
            <?php if (!empty($moviePoster) && file_exists($moviePoster)): ?>
                <img src="<?php echo htmlspecialchars($moviePoster); ?>" alt="Poster Film <?php echo htmlspecialchars($movieTitle); ?>" class="movie-poster">
            <?php else: ?>
                <div style="width:150px;height:225px;background:#667eea;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:48px;">🎬</div>
            <?php endif; ?>
            
            <div class="movie-info">
                <h1><?php echo htmlspecialchars($movieTitle); ?></h1>
                <?php if (!empty($movieOriginalTitle) && $movieOriginalTitle !== $movieTitle): ?>
                    <p style="color: #888; font-style: italic; margin-bottom: 5px;"><?php echo htmlspecialchars($movieOriginalTitle); ?></p>
                <?php endif; ?>
                <p><?php echo $movieYear; ?> • <?php echo htmlspecialchars($movieGenre); ?> • <?php echo $movieDuration; ?> menit</p>
                <p>Sutradara: <?php echo htmlspecialchars($movieDirector); ?></p>
                
                <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 6px;">
                    <strong style="color: var(--primary-color);">Sinopsis:</strong>
                    <p style="margin-top: 8px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($movieSynopsis)); ?></p>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <?php if ($isLoggedIn): ?>
                        <button class="submit-btn" style="background-color: #3498db;">
                            <i class="fas fa-thumbs-up"></i> Suka
                        </button>
                        <a href="daftar_tonton.php" class="submit-btn" style="text-decoration: none; display: inline-block; background-color: #333;">
                            <i class="fas fa-bookmark"></i> Daftar Tonton
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($movieTrailer)): ?>
                        <a href="<?php echo htmlspecialchars($movieTrailer); ?>" target="_blank" class="submit-btn" style="text-decoration: none; display: inline-block; background-color: #e74c3c;">
                            <i class="fas fa-play"></i> Tonton Trailer
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="rating-summary" style="margin-top: 25px;">
                    <div class="rating-box">
                        <div class="score"><?php echo $avgRating; ?>/5</div>
                        <div class="count">Rata-Rata</div>
                    </div>
                    <div class="rating-box">
                        <div class="score"><?php echo $reviewCount; ?></div>
                        <div class="count">Total Ulasan</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="review-form-section">
            <h2><i class="fas fa-edit"></i> Berikan Ulasan dan Rating Anda</h2>
            
            <?php if (!$isLoggedIn): ?>
                <div class="login-notice">
                    <p><i class="fas fa-lock"></i> Anda harus <a href="login.html">login</a> terlebih dahulu untuk memberikan review!</p>
                    <p style="margin-top: 10px;">Belum punya akun? <a href="register.html">Daftar di sini</a></p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Rating Anda (1-5 Bintang)</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Sangat Suka"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Bagus"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Lumayan"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Kurang"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Buruk"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reviewText">Ulasan Anda:</label>
                        <textarea name="review_text" id="reviewText" placeholder="Tuliskan ulasan Anda tentang <?php echo htmlspecialchars($movieTitle); ?> di sini..."></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Kirim Ulasan
                    </button>
                </form>
            <?php endif; ?>
        </section>
        
        <?php if ($isLoggedIn): ?>
        <section class="my-reviews-section">
            <h2><i class="fas fa-user-check"></i> Ulasan Saya</h2>
            <div id="my-reviews-display">
                <?php if (count($myReviews) === 0): ?>
                    <p style="color:#777; font-style: italic;">Anda belum membuat ulasan untuk film ini.</p>
                <?php else: ?>
                    <?php foreach ($myReviews as $review): ?>
                        <div class="review-item">
                            <div class="rating-display">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? 'var(--star-gold)' : '#444'; ?>; font-size: 1.1rem;"></i>
                                <?php endfor; ?>
                            </div>
                            <p><?php echo htmlspecialchars($review['review_text']) ?: '(Tidak ada ulasan tertulis)'; ?></p>
                            <div class="reviewer-info">
                                <?php echo htmlspecialchars($review['full_name'] ?? $review['username']); ?> - 
                                <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Tombol Lihat Semua Ulasan -->
            <?php if ($allReviewsCount > 0): ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="all_reviews.php?id=<?php echo $movieId; ?>" class="submit-btn" style="text-decoration: none; background: #2ecc71;">
                    <i class="fas fa-list"></i> Lihat Semua Ulasan (<?php echo $allReviewsCount; ?>)
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php else: ?>
        <!-- Jika belum login, tetap tampilkan info ulasan -->
        <?php if ($reviewCount > 0): ?>
        <section class="my-reviews-section">
            <h2><i class="fas fa-comments"></i> Ulasan Pengguna</h2>
            <div style="text-align: center; padding: 30px; background: #333; border-radius: 8px;">
                <p style="margin-bottom: 15px;">Lihat apa kata pengguna lain tentang film ini</p>
                <a href="all_reviews.php?id=<?php echo $movieId; ?>" class="submit-btn" style="text-decoration: none; background: #2ecc71;">
                    <i class="fas fa-list"></i> Lihat Semua Ulasan (<?php echo $reviewCount; ?>)
                </a>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>
        
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>

</body>
</html>