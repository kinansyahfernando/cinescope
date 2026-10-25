<?php
// File: jelajahi.php (FIXED - Consistent Rating)
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

// Login check
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['username'] ?? 'Guest';

// ==================== HANDLE REVIEW SUBMISSION ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$isLoggedIn) {
        $reviewMessage = "Anda harus login untuk memberikan review!";
        $reviewMessageType = "error";
    } else {
        try {
            $movie_id = intval($_POST['movie_id']);
            $rating = intval($_POST['rating']);
            $reviewText = trim($_POST['review_text']);
            
            if ($rating < 1 || $rating > 5) {
                throw new Exception("Rating harus antara 1-5 bintang!");
            }
            
            // Get user full name
            $userStmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
            $userStmt->execute([$currentUserId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            $userName = !empty($userData['full_name']) ? $userData['full_name'] : $userData['username'];
            
            // Check if user already reviewed this movie
            $checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND movie_id = ?");
            $checkStmt->execute([$currentUserId, $movie_id]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("Anda sudah memberikan review untuk film ini!");
            }
            
            // Insert review
            $insertStmt = $pdo->prepare("
                INSERT INTO reviews (movie_id, user_id, rating, review_text, user_name, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertStmt->execute([$movie_id, $currentUserId, $rating, $reviewText, $userName]);
            
            // Update movie average rating
            $updateStmt = $pdo->prepare("
                UPDATE movies m 
                SET m.average_rating = (
                    SELECT COALESCE(AVG(r.rating), 0)
                    FROM reviews r 
                    WHERE r.movie_id = ?
                )
                WHERE m.id = ?
            ");
            $updateStmt->execute([$movie_id, $movie_id]);
            
            $reviewMessage = "✅ Review berhasil ditambahkan!";
            $reviewMessageType = "success";
            
            // Redirect to same movie detail
            header("Location: jelajahi.php?movie_id=" . $movie_id . "&msg=review_added");
            exit;
            
        } catch(Exception $e) {
            $reviewMessage = "❌ " . $e->getMessage();
            $reviewMessageType = "error";
        }
    }
}

// ==================== FETCH DATA (FIXED - Consistent Rating) ====================

// Get ALL movies with average rating and review count - CONSISTENT CALCULATION
$moviesStmt = $pdo->query("
    SELECT m.*, 
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(r.id) as review_count
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    GROUP BY m.id
    ORDER BY m.id ASC
");
$movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique genres from movies
$genresStmt = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL ORDER BY genre");
$genres = $genresStmt->fetchAll(PDO::FETCH_COLUMN);

// Get unique years
$yearsStmt = $pdo->query("SELECT DISTINCT release_year FROM movies WHERE release_year IS NOT NULL ORDER BY release_year DESC");
$years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get movie detail if requested
$selectedMovie = null;
$movieReviews = [];
if (isset($_GET['movie_id'])) {
    $movieId = intval($_GET['movie_id']);
    
    $movieStmt = $pdo->prepare("
        SELECT m.*, 
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as review_count
        FROM movies m
        LEFT JOIN reviews r ON m.id = r.movie_id
        WHERE m.id = ?
        GROUP BY m.id
    ");
    $movieStmt->execute([$movieId]);
    $selectedMovie = $movieStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all reviews for this movie
    if ($selectedMovie) {
        $reviewsStmt = $pdo->prepare("
            SELECT r.*, u.username, u.full_name 
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.movie_id = ?
            ORDER BY r.created_at DESC
        ");
        $reviewsStmt->execute([$movieId]);
        $movieReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Helper function for poster path
function getPosterPath($posterFromDB) {
    if (empty($posterFromDB)) {
        return 'https://via.placeholder.com/200x300?text=No+Poster';
    }
    
    if (file_exists($posterFromDB)) {
        return $posterFromDB;
    }
    
    return 'https://via.placeholder.com/200x300?text=No+Poster';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
   <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title><?php echo $selectedMovie ? 'Review ' . htmlspecialchars($selectedMovie['title']) : 'Jelajahi Film'; ?> - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e50914;
            --background-dark: #0a0a0a;
            --card-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-muted: #ccc;
            --star-gold: #ffc107;
            --divider-color: #333;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-dark);
            color: var(--text-light);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .back-link-container { margin-bottom: 20px; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 1rem;
            padding: 8px 12px;
            border-radius: 5px;
            transition: color 0.3s, background-color 0.3s;
        }

        .back-link:hover {
            color: var(--text-light);
            background-color: #333;
        }
        
        .page-title {
            text-align: center;
            color: var(--primary-color);
            font-size: 2.5em;
            margin-bottom: 30px;
            text-shadow: 0 0 5px rgba(229, 9, 20, 0.5);
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
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

        .filter-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 40px;
            justify-content: center;
        }

        .search-group, .filter-group {
            flex-grow: 1;
            min-width: 200px;
        }

        .search-group input, .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: var(--card-dark);
            color: var(--text-light);
            font-size: 1rem;
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }

        .movie-card {
            background-color: var(--card-dark);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            cursor: pointer;
        }

        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(229, 9, 20, 0.4);
        }

        .movie-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-bottom: 3px solid var(--primary-color);
        }

        .movie-info {
            padding: 15px;
        }

        .movie-info h3 {
            font-size: 1.1em;
            margin-bottom: 5px;
            color: var(--text-light);
        }

        .movie-info p {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        
        .rating-chip {
            display: inline-block;
            background-color: #27ae60;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.85em;
        }

        /* Movie Detail Styles */
        .movie-detail {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-dark);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
        }

        .movie-header-detail {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }

        .movie-header-detail img {
            width: 250px;
            height: 375px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }

        .movie-details {
            flex-grow: 1;
        }

        .movie-details h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .movie-details p {
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .rating-box {
            background-color: #222;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 5px solid var(--star-gold);
        }

        .rating-box h3 {
            color: var(--star-gold);
            margin-bottom: 5px;
        }

        .review-content h2 {
            font-size: 1.8rem;
            color: var(--text-light);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .review-content p {
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #ddd;
            text-align: justify;
        }

        .sinopsis {
            font-style: italic;
            color: #aaa;
            border-left: 3px solid #777;
            padding-left: 15px;
            margin: 20px 0;
        }

        .user-reviews-section {
            background-color: #222;
            padding: 30px;
            border-radius: 10px;
            margin-top: 40px;
        }

        .user-reviews-section h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .login-notice {
            background: #333;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .login-notice a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .rating-input-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-light);
            font-weight: 600;
        }

        .rating-stars {
            font-size: 1.8rem;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .rating-stars i {
            color: #444;
            transition: color 0.2s;
        }

        .rating-stars i.active {
            color: var(--star-gold);
        }

        .review-form textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #444;
            background-color: #333;
            color: var(--text-light);
            resize: vertical;
            margin-bottom: 15px;
            font-family: inherit;
        }

        .review-form button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .review-form button:hover {
            background-color: #c0392b;
        }

        .reviews-display {
            margin-top: 30px;
        }

        .review-item {
            border-top: 1px solid #444;
            padding: 15px 0;
        }

        .reviewer-info {
            font-size: 0.85rem;
            color: #999;
            margin-top: 5px;
        }
        
        .review-item p {
            margin: 5px 0 10px 0;
            font-size: 1rem;
            color: #ddd;
        }

        @media (max-width: 768px) {
            .movie-header-detail {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .movie-header-detail img {
                width: 100%;
                max-width: 250px;
                height: auto;
            }

            .filter-controls {
                flex-direction: column;
            }
            .page-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="back-link-container">
            <a href="<?php echo $selectedMovie ? 'jelajahi.php' : 'user.php'; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (isset($reviewMessage)): ?>
            <div class="message <?php echo $reviewMessageType; ?>">
                <?php echo $reviewMessage; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'review_added'): ?>
            <div class="message success">
                ✅ Review Anda berhasil ditambahkan!
            </div>
        <?php endif; ?>
        
        <?php if (!$selectedMovie): ?>
            <!-- LIST VIEW -->
            <h2 class="page-title">Jelajahi Semua Film</h2>
            
            <p style="text-align: center; color: #999; margin-bottom: 30px;">
                <i class="fas fa-info-circle"></i> Menampilkan <?php echo count($movies); ?> film dari berbagai genre dan tahun
            </p>

            <div class="filter-controls">
                <div class="search-group">
                    <input type="text" id="searchInput" placeholder="Cari film (judul)..." onkeyup="filterMovies()">
                </div>
                <div class="filter-group">
                    <select id="genreFilter" onchange="filterMovies()">
                        <option value="">Semua Genre</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="yearFilter" onchange="filterMovies()">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="sortFilter" onchange="filterMovies()">
                        <option value="">Urutkan Berdasarkan</option>
                        <option value="rating">Rating Tertinggi</option>
                        <option value="newest">Terbaru</option>
                    </select>
                </div>
            </div>

            <div class="movie-grid" id="movieGrid">
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card" 
                         data-title="<?php echo strtolower($movie['title']); ?>"
                         data-genre="<?php echo $movie['genre']; ?>"
                         data-year="<?php echo $movie['release_year']; ?>"
                         data-rating="<?php echo $movie['avg_rating']; ?>"
                         onclick="window.location.href='jelajahi.php?movie_id=<?php echo $movie['id']; ?>'">
                        <img src="<?php echo getPosterPath($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p><?php echo htmlspecialchars($movie['genre']); ?> | <?php echo $movie['release_year']; ?></p>
                            <span class="rating-chip">
                                <i class="fas fa-star" style="color: var(--star-gold);"></i> 
                                <?php echo number_format($movie['avg_rating'], 1); ?>/5
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- DETAIL VIEW -->
            <div class="movie-detail">
                <div class="movie-header-detail">
                    <img src="<?php echo getPosterPath($selectedMovie['poster']); ?>" alt="<?php echo htmlspecialchars($selectedMovie['title']); ?>">
                    <div class="movie-details">
                        <h1><?php echo htmlspecialchars($selectedMovie['title']); ?></h1>
                        <p><strong>Genre:</strong> <?php echo htmlspecialchars($selectedMovie['genre']); ?></p>
                        <p><strong>Tahun:</strong> <?php echo $selectedMovie['release_year']; ?></p>
                        <p><strong>Durasi:</strong> <?php echo $selectedMovie['duration']; ?> menit</p>
                        <p><strong>Sutradara:</strong> <?php echo htmlspecialchars($selectedMovie['director'] ?? 'N/A'); ?></p>
                        <div class="rating-box">
                            <h3>Rata-rata Rating Pengguna</h3>
                            <p style="font-size: 2rem; color: var(--star-gold); font-weight: 700;">
                                <?php echo $selectedMovie['review_count'] > 0 ? number_format($selectedMovie['avg_rating'], 1) . '/5' : 'Belum Ada'; ?>
                            </p>
                            <p style="font-size: 0.9rem; color: #999;">Dari <?php echo $selectedMovie['review_count']; ?> ulasan</p>
                        </div>
                    </div>
                </div>

                <div class="review-content">
                    <h2>Sinopsis</h2>
                    <p class="sinopsis"><?php echo nl2br(htmlspecialchars($selectedMovie['synopsis'])); ?></p>
                </div>

                <div class="user-reviews-section">
                    <h2>Ulasan Pengguna (<?php echo count($movieReviews); ?>)</h2>
                    
                    <?php if (!$isLoggedIn): ?>
                        <div class="login-notice">
                            <p><i class="fas fa-info-circle"></i> Anda harus <a href="login.html">login</a> untuk memberikan review</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="review-form" id="reviewForm">
                            <input type="hidden" name="movie_id" value="<?php echo $selectedMovie['id']; ?>">
                            
                            <div class="rating-input-group">
                                <label>Beri Rating Anda:</label>
                                <div class="rating-stars" id="ratingInput">
                                    <i class="fas fa-star" data-rating="1"></i>
                                    <i class="fas fa-star" data-rating="2"></i>
                                    <i class="fas fa-star" data-rating="3"></i>
                                    <i class="fas fa-star" data-rating="4"></i>
                                    <i class="fas fa-star" data-rating="5"></i>
                                </div>
                                <input type="hidden" id="rating" name="rating" value="0" required>
                            </div>

                            <label for="review_text">Tulis Ulasan Anda:</label>
                            <textarea name="review_text" id="review_text" placeholder="Bagikan pendapat Anda tentang film ini..."></textarea>
                            
                            <button type="submit" name="submit_review">Kirim Ulasan</button>
                        </form>
                    <?php endif; ?>

                    <div class="reviews-display">
                        <?php if (count($movieReviews) > 0): ?>
                            <?php foreach ($movieReviews as $review): ?>
                                <div class="review-item">
                                    <div class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? 'var(--star-gold)' : '#444'; ?>; font-size: 1.1rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p><?php echo !empty($review['review_text']) ? nl2br(htmlspecialchars($review['review_text'])) : '<em>(Tidak ada ulasan tertulis)</em>'; ?></p>
                                    <div class="reviewer-info">
                                        <?php 
                                        $displayName = !empty($review['full_name']) ? $review['full_name'] : $review['username'];
                                        echo htmlspecialchars($displayName); 
                                        ?> - 
                                        <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#777;">Belum ada ulasan untuk film ini. Jadilah yang pertama!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Filter Movies
        function filterMovies() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const genreFilter = document.getElementById('genreFilter').value;
            const yearFilter = document.getElementById('yearFilter').value;
            const sortFilter = document.getElementById('sortFilter').value;
            
            const movieCards = Array.from(document.querySelectorAll('.movie-card'));
            let visibleCards = [];

            movieCards.forEach(card => {
                const title = card.getAttribute('data-title');
                const genre = card.getAttribute('data-genre');
                const year = card.getAttribute('data-year');
                
                const matchSearch = title.includes(searchTerm);
                const matchGenre = !genreFilter || genre === genreFilter;
                const matchYear = !yearFilter || year === yearFilter;
                
                if (matchSearch && matchGenre && matchYear) {
                    card.style.display = 'block';
                    visibleCards.push(card);
                } else {
                    card.style.display = 'none';
                }
            });

            // Sorting
            if (sortFilter === 'rating') {
                visibleCards.sort((a, b) => {
                    return parseFloat(b.getAttribute('data-rating')) - parseFloat(a.getAttribute('data-rating'));
                });
            } else if (sortFilter === 'newest') {
                visibleCards.sort((a, b) => {
                    return parseInt(b.getAttribute('data-year')) - parseInt(a.getAttribute('data-year'));
                });
            }

            // Re-append sorted cards
            const movieGrid = document.getElementById('movieGrid');
            visibleCards.forEach(card => movieGrid.appendChild(card));
        }

        // Rating Stars Interaction
        <?php if ($selectedMovie && $isLoggedIn): ?>
        const stars = document.querySelectorAll('#ratingInput i');
        const ratingInput = document.getElementById('rating');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingInput.value = rating;
                
                stars.forEach(s => {
                    const starRating = parseInt(s.getAttribute('data-rating'));
                    if (starRating <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        // Form validation
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            if (parseInt(ratingInput.value) === 0) {
                e.preventDefault();
                alert('Harap berikan rating bintang!');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>