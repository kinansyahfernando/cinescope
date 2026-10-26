<?php

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

$movieId = 2;

$movieStmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$movieStmt->execute([$movieId]);
$movie = $movieStmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    die("❌ Film tidak ditemukan!");
}

function getPosterPath($posterFromDB) {
    if (empty($posterFromDB)) return null;
    $possiblePaths = [__DIR__ . '/' . $posterFromDB, $posterFromDB];
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) return $posterFromDB;
    }
    return null;
}

$reviewStmt = $pdo->prepare("
    SELECT r.id, r.movie_id, r.user_id, r.rating, r.review_text, r.created_at, r.user_name as review_user_name,
    COALESCE(u.full_name, u.username, r.user_name, 'Pengguna CineScope') as display_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([$movieId]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

$totalReviews = count($reviews);
$avgRating = $totalReviews > 0 ? array_sum(array_column($reviews, 'rating')) / $totalReviews : 0;
$posterPath = getPosterPath($movie['poster']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Ulasan Saya - <?= htmlspecialchars($movie['title']) ?> | CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #e50914; --background-dark: #0a0a0a; --card-dark: #1a1a1a; --text-light: #ffffff; --star-gold: #ffc107; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--background-dark); color: var(--text-light); line-height: 1.6; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; background-color: var(--card-dark); border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .back-to-home { display: inline-block; background-color: #333; color: var(--text-light); padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-bottom: 20px; transition: background-color 0.3s; cursor: pointer; border: none; font-weight: 600; }
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
        .rating-display { margin-bottom: 10px; }
        .review-item p { margin-bottom: 10px; font-size: 1em; color: #e0e0e0; }
        .reviewer-info { font-size: 0.85em; color: #aaa; text-align: right; border-top: 1px dashed #555; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <button class="back-to-home" onclick="window.history.back()"><i class="fas fa-arrow-left"></i> Kembali</button>
        <section class="movie-header">
            <?php if ($posterPath): ?>
                <img src="<?= htmlspecialchars($posterPath) ?>" alt="Poster" class="movie-poster">
            <?php else: ?>
                <div class="poster-placeholder"><i class="fas fa-film"></i></div>
            <?php endif; ?>
            <div class="movie-info">
                <h1><?= htmlspecialchars($movie['title']) ?></h1>
                <p><?= htmlspecialchars($movie['release_year']) ?> • <?= htmlspecialchars($movie['genre']) ?> • <?= htmlspecialchars($movie['duration']) ?? '1h 45m' ?></p>
                <?php if (!empty($movie['synopsis'])): ?><p><?= htmlspecialchars($movie['synopsis']) ?></p><?php endif; ?>
                <div class="rating-summary">
                    <div class="rating-box"><div class="score"><?= $totalReviews > 0 ? number_format($avgRating, 1) . '/5' : '-' ?></div><div class="count">Rata-Rata</div></div>
                    <div class="rating-box"><div class="score"><?= $totalReviews ?></div><div class="count">Total Ulasan</div></div>
                </div>
            </div>
        </section>
        <section class="my-reviews-section">
            <h2><i class="fas fa-user-check"></i> Ulasan Saya</h2>
            <div id="my-reviews-display">
                <?php if ($totalReviews > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="rating-display">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?= $i <= $review['rating'] ? 'var(--star-gold)' : '#444' ?>; font-size: 1.1rem;"></i>
                                <?php endfor; ?>
                            </div>
                            <p><?= htmlspecialchars($review['review_text'] ?: '(Tidak ada ulasan tertulis)') ?></p>
                            <div class="reviewer-info">Oleh: <?= htmlspecialchars($review['display_name']) ?> - <?= date('d M Y, H:i', strtotime($review['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#777; font-style: italic;">Anda belum membuat ulasan untuk film ini.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>