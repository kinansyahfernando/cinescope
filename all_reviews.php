<?php
// File: all_reviews.php
// Simpan di: C:/xampp/htdocs/movie-review/all_reviews.php
// Akses: http://localhost/movie-review/all_reviews.php?id=7
// Menampilkan SEMUA review untuk film apapun

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database Configuration
$host = '127.0.0.1';
$dbname = 'cinescope';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movieId <= 0) {
    die("❌ ID Film tidak valid!");
}

// Fetch movie data
$movieStmt = $pdo->prepare("SELECT * FROM movies WHERE id = :id");
$movieStmt->execute([':id' => $movieId]);
$movieData = $movieStmt->fetch();

if (!$movieData) {
    die("❌ Film tidak ditemukan!");
}

// Get statistics
$statsStmt = $pdo->prepare("SELECT 
    COUNT(*) as review_count,
    COALESCE(AVG(rating), 0) as avg_rating
    FROM reviews WHERE movie_id = :movie_id");
$statsStmt->execute([':movie_id' => $movieId]);
$stats = $statsStmt->fetch();

// Get ALL reviews untuk film ini
$allReviewsStmt = $pdo->prepare("SELECT r.*, u.username, u.full_name 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = :movie_id
    ORDER BY r.created_at DESC");
$allReviewsStmt->execute([':movie_id' => $movieId]);
$allReviews = $allReviewsStmt->fetchAll();

$reviewCount = $stats['review_count'];
$avgRating = round($stats['avg_rating'], 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Ulasan - <?php echo htmlspecialchars($movieData['title']); ?> - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #e50914;
            --background-dark: #0a0a0a;
            --card-dark: #1a1a1a;
            --text-light: #ffffff;
            --star-gold: #ffc107;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--background-dark); 
            color: var(--text-light); 
            line-height: 1.6; 
            padding: 20px; 
        }
        
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 20px; 
            background-color: var(--card-dark); 
            border-radius: 12px; 
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5); 
        }
        
        .back-button {
            display: inline-block;
            background-color: #333;
            color: var(--text-light);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s;
            font-weight: 600;
        }
        .back-button:hover { background-color: #555; }

        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .movie-poster-small {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .header-info h1 {
            color: var(--primary-color);
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .header-info p {
            color: #ccc;
        }
        
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: #333;
            padding: 10px 20px;
            border-radius: 6px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--star-gold);
        }
        
        .stat-label {
            font-size: 0.85em;
            color: #aaa;
        }
        
        .reviews-section h2 {
            color: var(--text-light);
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .review-card {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s;
        }
        
        .review-card:hover {
            transform: translateX(5px);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.1em;
        }
        
        .review-date {
            color: #888;
            font-size: 0.85em;
        }
        
        .rating-stars {
            margin: 10px 0;
        }
        
        .rating-stars i {
            font-size: 1.2em;
            margin-right: 2px;
        }
        
        .review-text {
            color: #e0e0e0;
            line-height: 1.7;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #444;
        }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #333;
            border: 2px solid #444;
            color: var(--text-light);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="movie_detail.php?id=<?php echo $movieId; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Film
        </a>
        
        <div class="header">
            <?php if (!empty($movieData['poster']) && file_exists($movieData['poster'])): ?>
                <img src="<?php echo htmlspecialchars($movieData['poster']); ?>" alt="<?php echo htmlspecialchars($movieData['title']); ?>" class="movie-poster-small">
            <?php else: ?>
                <div style="width:100px;height:150px;background:#667eea;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:36px;">🎬</div>
            <?php endif; ?>
            
            <div class="header-info">
                <h1><?php echo htmlspecialchars($movieData['title']); ?></h1>
                <p><?php echo $movieData['release_year']; ?> • <?php echo htmlspecialchars($movieData['genre']); ?></p>
                
                <div class="stats-bar">
                    <div class="stat-item">
                        <div class="stat-number">⭐ <?php echo $avgRating; ?>/5</div>
                        <div class="stat-label">Rating Rata-rata</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $reviewCount; ?></div>
                        <div class="stat-label">Total Ulasan</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="reviews-section">
            <h2><i class="fas fa-comments"></i> Semua Ulasan Pengguna (<?php echo $reviewCount; ?>)</h2>
            
            <div class="filter-bar">
                <button class="filter-btn active" onclick="filterReviews('all')">Semua</button>
                <button class="filter-btn" onclick="filterReviews(5)">⭐ 5 Bintang</button>
                <button class="filter-btn" onclick="filterReviews(4)">⭐ 4 Bintang</button>
                <button class="filter-btn" onclick="filterReviews(3)">⭐ 3 Bintang</button>
                <button class="filter-btn" onclick="filterReviews(2)">⭐ 2 Bintang</button>
                <button class="filter-btn" onclick="filterReviews(1)">⭐ 1 Bintang</button>
            </div>
            
            <div id="reviews-container">
                <?php if (count($allReviews) === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum Ada Ulasan</h3>
                        <p>Jadilah yang pertama memberikan ulasan untuk film ini!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allReviews as $review): ?>
                        <div class="review-card" data-rating="<?php echo $review['rating']; ?>">
                            <div class="review-header">
                                <span class="reviewer-name">
                                    <i class="fas fa-user-circle"></i> 
                                    <?php echo htmlspecialchars($review['full_name'] ?? $review['username'] ?? 'Anonim'); ?>
                                </span>
                                <span class="review-date">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date('d M Y, H:i', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? 'var(--star-gold)' : '#444'; ?>;"></i>
                                <?php endfor; ?>
                                <span style="margin-left: 10px; color: var(--star-gold); font-weight: 600;">
                                    <?php echo $review['rating']; ?>/5
                                </span>
                            </div>
                            
                            <?php if (!empty($review['review_text'])): ?>
                                <div class="review-text">
                                    <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="review-text" style="font-style: italic; color: #666;">
                                    (Pengguna hanya memberikan rating tanpa ulasan tertulis)
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterReviews(rating) {
            const allCards = document.querySelectorAll('.review-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            allCards.forEach(card => {
                if (rating === 'all') {
                    card.style.display = 'block';
                } else {
                    if (parseInt(card.dataset.rating) === rating) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>