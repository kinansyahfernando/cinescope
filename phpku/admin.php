<?php
// File: admin.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Buat folder uploads jika belum ada
$uploadDirs = ['uploads', 'uploads/posters', 'uploads/videos'];
foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0777, true);
}

$message = '';
$messageType = '';
$action = $_GET['action'] ?? 'dashboard';

// ==================== FUNGSI BANTUAN ====================

/**
 * Menghitung ulang dan memperbarui kolom average_rating film di tabel 'movies'.
 * @param PDO $pdo Koneksi PDO
 * @param int $movieId ID Film yang akan diperbarui
 */
function updateMovieAverageRating(PDO $pdo, int $movieId) {
    if ($movieId <= 0) return;

    $sql = "UPDATE movies m 
            SET m.average_rating = (
                SELECT COALESCE(AVG(r.rating) * 2, 0)
                FROM reviews r 
                WHERE r.movie_id = :movie_id
            )
            WHERE m.id = :movie_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':movie_id' => $movieId]);
}

// ==================== PROSES ACTIONS ====================

// CREATE MOVIE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_movie'])) {
    $posterPath = '';
    
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $posterName = time() . '_' . basename($_FILES['poster']['name']);
        $posterTarget = 'uploads/posters/' . $posterName;
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $posterTarget)) {
            $posterPath = $posterTarget;
        }
    }
    
    $sql = "INSERT INTO movies (title, original_title, release_year, duration, genre, director, synopsis, poster, trailer_url, average_rating, created_at) 
            VALUES (:title, :original_title, :release_year, :duration, :genre, :director, :synopsis, :poster, :trailer_url, :rating, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $_POST['title'],
        ':original_title' => $_POST['original_title'],
        ':release_year' => $_POST['release_year'],
        ':duration' => $_POST['duration'],
        ':genre' => $_POST['genre'],
        ':director' => $_POST['director'],
        ':synopsis' => $_POST['synopsis'],
        ':poster' => $posterPath,
        ':trailer_url' => $_POST['trailer_url'],
        ':rating' => $_POST['average_rating'] ?? 7.5
    ]);
    
    header("Location: admin.php?action=movies&msg=created");
    exit;
}

// UPDATE MOVIE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_movie'])) {
    $movieId = $_POST['movie_id'];
    $posterPath = $_POST['existing_poster'];
    
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $posterName = time() . '_' . basename($_FILES['poster']['name']);
        $posterTarget = 'phpku/uploads/posters/' . $posterName;
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $posterTarget)) {
            $posterPath = $posterTarget;
            if (!empty($_POST['existing_poster']) && file_exists($_POST['existing_poster'])) {
                unlink($_POST['existing_poster']);
            }
        }
    }
    
    $sql = "UPDATE movies SET 
            title = :title,
            original_title = :original_title,
            release_year = :release_year,
            duration = :duration,
            genre = :genre,
            director = :director,
            synopsis = :synopsis,
            poster = :poster,
            trailer_url = :trailer_url,
            average_rating = :rating,
            updated_at = NOW()
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $_POST['title'],
        ':original_title' => $_POST['original_title'],
        ':release_year' => $_POST['release_year'],
        ':duration' => $_POST['duration'],
        ':genre' => $_POST['genre'],
        ':director' => $_POST['director'],
        ':synopsis' => $_POST['synopsis'],
        ':poster' => $posterPath,
        ':trailer_url' => $_POST['trailer_url'],
        ':rating' => $_POST['average_rating'] ?? 7.5,
        ':id' => $movieId
    ]);
    
    header("Location: admin.php?action=movies&msg=updated");
    exit;
}

// CREATE REVIEW (Manual from Admin) - PERBAIKAN LOGIKA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    try {
        // Validasi input
        $movie_id = isset($_POST['movie_id']) && is_numeric($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        $user_id = isset($_POST['user_id']) && is_numeric($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $rating = isset($_POST['rating']) && is_numeric($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
        
        // Validasi data
        if ($movie_id <= 0) {
            throw new Exception("Film harus dipilih. Silakan pilih film dari dropdown.");
        }
        
        if ($user_id <= 0) {
            throw new Exception("User harus dipilih. Silakan pilih user dari dropdown.");
        }
        
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Rating harus dipilih antara 1-5 bintang.");
        }
        
        // Cek apakah movie_id ada di database
        $checkMovie = $pdo->prepare("SELECT id FROM movies WHERE id = :id");
        $checkMovie->execute([':id' => $movie_id]);
        if (!$checkMovie->fetch()) {
            throw new Exception("Film dengan ID {$movie_id} tidak ditemukan di database.");
        }
        
        // Cek apakah user_id ada di database
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $checkUser->execute([':id' => $user_id]);
        if (!$checkUser->fetch()) {
            throw new Exception("User dengan ID {$user_id} tidak ditemukan di database.");
        }
        
        // Insert review ke database
        $sql = "INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at, updated_at) 
                VALUES (:movie_id, :user_id, :rating, :review_text, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':movie_id' => $movie_id,
            ':user_id' => $user_id,
            ':rating' => $rating,
            ':review_text' => $review_text
        ]);
        
        if ($result) {
            // Update average rating film
            updateMovieAverageRating($pdo, $movie_id);
            
            header("Location: admin.php?action=reviews&msg=review_created");
            exit;
        } else {
            throw new Exception("Gagal menyimpan review ke database.");
        }

    } catch(PDOException $e) {
        $message = "❌ Database Error: " . $e->getMessage();
        $messageType = 'error';
    } catch(Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// DELETE MOVIE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $movieId = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT poster FROM movies WHERE id = :id");
    $stmt->execute([':id' => $movieId]);
    $movie = $stmt->fetch();
    
    if ($movie && !empty($movie['poster']) && file_exists($movie['poster'])) {
        unlink($movie['poster']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM movies WHERE id = :id");
    $stmt->execute([':id' => $movieId]);
    
    header("Location: admin.php?action=movies&msg=deleted");
    exit;
}

// DELETE REVIEW
if (isset($_GET['delete_review']) && is_numeric($_GET['delete_review'])) {
    $reviewId = (int)$_GET['delete_review'];
    
    // Ambil movie_id sebelum dihapus
    $stmt = $pdo->prepare("SELECT movie_id FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $reviewId]);
    $review = $stmt->fetch();
    $movieId = $review['movie_id'] ?? 0;

    // Hapus review
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $reviewId]);

    // Hitung ulang rating
    if ($movieId > 0) {
        updateMovieAverageRating($pdo, $movieId);
    }
    
    header("Location: admin.php?action=reviews&msg=review_deleted");
    exit;
}

// Handle messages from URL
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'created': $message = "✅ Film berhasil ditambahkan!"; $messageType = 'success'; break;
        case 'updated': $message = "✅ Film berhasil diupdate!"; $messageType = 'success'; break;
        case 'deleted': $message = "✅ Film berhasil dihapus!"; $messageType = 'success'; break;
        case 'review_deleted': $message = "✅ Review berhasil dihapus!"; $messageType = 'success'; break;
        case 'review_created': $message = "✅ Review berhasil ditambahkan!"; $messageType = 'success'; break; 
    }
}

// ==================== FETCH DATA ====================

// Get all movies with review stats
$moviesStmt = $pdo->query("SELECT m.*, 
    (SELECT COUNT(*) FROM reviews WHERE movie_id = m.id) as review_count,
    (SELECT AVG(rating) FROM reviews WHERE movie_id = m.id) as calc_avg_rating
    FROM movies m ORDER BY m.id DESC");
$movies = $moviesStmt->fetchAll();

// Get all reviews
$reviewsStmt = $pdo->query("SELECT r.*, m.title as movie_title, u.username, u.full_name 
    FROM reviews r 
    LEFT JOIN movies m ON r.movie_id = m.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC LIMIT 100");
$reviews = $reviewsStmt->fetchAll();

// Get Movies for dropdown
$moviesListStmt = $pdo->query("SELECT id, title FROM movies ORDER BY title ASC");
$moviesList = $moviesListStmt->fetchAll(PDO::FETCH_ASSOC);

// Get Users for dropdown
$usersListStmt = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC");
$usersList = $usersListStmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$totalMovies = count($movies);
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avgRating = $pdo->query("SELECT AVG(average_rating) FROM movies WHERE average_rating > 0")->fetchColumn();

// Get movie for edit
$editMovie = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    $editMovie = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        $titles = [
            'dashboard' => 'Dashboard',
            'movies' => 'Kelola Film',
            'add' => 'Tambah Film',
            'edit' => 'Edit Film',
            'reviews' => 'Kelola Reviews',
            'add_review' => 'Tambah Review Manual' 
        ];
        echo ($titles[$action] ?? 'Admin') . ' - Movie Review Admin';
    ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar h1 {
            font-size: 1.5em;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-menu li {
            margin-bottom: 10px;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: #667eea;
            color: white;
            transform: translateX(-5px);
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h2 {
            color: #333;
            font-size: 1.8em;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-info { background: #17a2b8; color: white; }
        
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .movie-poster-thumb {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .rating-badge {
            background: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
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
        
        .poster-preview {
            max-width: 300px;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .quick-links {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .quick-links h4 {
            color: white;
            margin-bottom: 15px;
        }
        
        .quick-links a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .quick-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #ddd;
        }

        .rating-radio-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .rating-radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .rating-radio-group label:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .rating-radio-group input[type="radio"]:checked + label,
        .rating-radio-group label:has(input[type="radio"]:checked) {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .rating-radio-group input[type="radio"] {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 13px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>🎬 Admin Panel</h1>
        <ul class="nav-menu">
            <li><a href="?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a></li>
            <li><a href="?action=movies" class="<?php echo $action === 'movies' ? 'active' : ''; ?>">
                <i class="fas fa-film"></i> Kelola Film
            </a></li>
            <li><a href="?action=add" class="<?php echo $action === 'add' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Tambah Film
            </a></li>
            <li><a href="?action=reviews" class="<?php echo $action === 'reviews' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Kelola Reviews
            </a></li>
            <li><a href="?action=add_review" class="<?php echo $action === 'add_review' ? 'active' : ''; ?>">
                <i class="fas fa-comment-dots"></i> Tambah Review Manual
            </a></li>
            <li><a href="user.html" target="_blank">
                <i class="fas fa-external-link-alt"></i> Lihat Website User
            </a></li>
            <li><a href="index.html" target="_blank">
                <i class="fas fa-globe"></i> Lihat Website Guest
            </a></li>
        </ul>
        
        <div class="quick-links">
            <h4>⚡ Quick Access</h4>
            <a href="get_movies.php" target="_blank"><i class="fas fa-code"></i> API Movies</a>
            <a href="setup_movies.php" target="_blank"><i class="fas fa-database"></i> Setup Database</a>
            <a href="sync_ratings.php" target="_blank"><i class="fas fa-sync"></i> Sync Ratings</a>
            <a href="http://localhost/phpmyadmin" target="_blank"><i class="fas fa-database"></i> phpMyAdmin</a>
        </div>
    </div>
    
    <div class="main-content">
        <?php if ($action !== 'dashboard'): ?>
            <a href="?action=<?php echo ($action === 'edit' || $action === 'add' || $action === 'add_review') ? 'movies' : 'dashboard'; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'dashboard'): ?>
            <div class="header">
                <h2>📊 Dashboard Overview</h2>
                <span style="color:#999;">Selamat datang di Admin Panel</span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-film"></i> Total Film</h3>
                    <div class="stat-number"><?php echo $totalMovies; ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-comments"></i> Total Reviews</h3>
                    <div class="stat-number"><?php echo $totalReviews; ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-star"></i> Rating Rata-rata</h3>
                    <div class="stat-number"><?php echo $avgRating ? number_format($avgRating, 1) : '0.0'; ?></div>
                </div>
            </div>
            
            <div class="card">
                <h3>🎥 Film Terbaru</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Judul</th>
                            <th>Tahun</th>
                            <th>Rating</th>
                            <th>Reviews</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($movies, 0, 10) as $movie): ?>
                        <tr>
                            <td>
                                <?php if (!empty($movie['poster'])): ?>
                                    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="movie-poster-thumb" alt="">
                                <?php else: ?>
                                    <div style="width:60px;height:90px;background:#667eea;border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;">🎬</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($movie['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                            <td><span class="rating-badge">⭐ <?php echo number_format($movie['calc_avg_rating'] ?? 0, 1); ?></span></td>
                            <td><?php echo $movie['review_count']; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $movie['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($action === 'movies'): ?>
            <div class="header">
                <h2>🎥 Kelola Film</h2>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Film Baru
                </a>
            </div>
            
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Poster</th>
                            <th>Judul</th>
                            <th>Genre</th>
                            <th>Tahun</th>
                            <th>Rating</th>
                            <th>Reviews</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($movies as $movie): ?>
                        <tr>
                            <td><?php echo $movie['id']; ?></td>
                            <td>
                                <?php if (!empty($movie['poster'])): ?>
                                    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="movie-poster-thumb" alt="">
                                <?php else: ?>
                                    <div style="width:60px;height:90px;background:#667eea;border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;">🎬</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                                <?php if (!empty($movie['original_title'])): ?>
                                    <br><small style="color:#999;"><?php echo htmlspecialchars($movie['original_title']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                            <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                            <td><span class="rating-badge">⭐ <?php echo number_format($movie['average_rating'], 1); ?></span></td>
                            <td><?php echo $movie['review_count']; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $movie['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $movie['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus film ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <div class="header">
                <h2><?php echo $action === 'edit' ? '✏️ Edit Film' : '➕ Tambah Film Baru'; ?></h2>
            </div>
            
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="movie_id" value="<?php echo $editMovie['id']; ?>">
                        <input type="hidden" name="existing_poster" value="<?php echo $editMovie['poster']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Judul Film <span style="color:red;">*</span></label>
                        <input type="text" name="title" required value="<?php echo $editMovie['title'] ?? ''; ?>" placeholder="Contoh: Pengabdi Setan 2">
                    </div>
                    
                    <div class="form-group">
                        <label>Judul Asli (Original Title)</label>
                        <input type="text" name="original_title" value="<?php echo $editMovie['original_title'] ?? ''; ?>" placeholder="Contoh: Satan's Slaves 2">
                    </div>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Tahun Rilis <span style="color:red;">*</span></label>
                            <input type="number" name="release_year" required value="<?php echo $editMovie['release_year'] ?? date('Y'); ?>" min="1900" max="2099">
                        </div>
                        <div class="form-group">
                            <label>Durasi (menit)</label>
                            <input type="number" name="duration" value="<?php echo $editMovie['duration'] ?? ''; ?>" placeholder="120">
                        </div>
                        <div class="form-group">
                            <label>Rating (0-10)</label>
                            <input type="number" step="0.1" min="0" max="10" name="average_rating" value="<?php echo $editMovie['average_rating'] ?? '7.5'; ?>" placeholder="8.5">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Genre <span style="color:red;">*</span></label>
                        <input type="text" name="genre" required placeholder="Contoh: Horror, Thriller, Drama" value="<?php echo $editMovie['genre'] ?? ''; ?>">
                        <small style="color:#999;">Pisahkan dengan koma untuk multiple genre</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Sutradara</label>
                        <input type="text" name="director" value="<?php echo $editMovie['director'] ?? ''; ?>" placeholder="Contoh: Joko Anwar">
                    </div>
                    
                    <div class="form-group">
                        <label>Sinopsis <span style="color:red;">*</span></label>
                        <textarea name="synopsis" required placeholder="Tuliskan ringkasan cerita film..."><?php echo $editMovie['synopsis'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Poster Film</label>
                        <input type="file" name="poster" accept="image/*" onchange="previewImage(this)">
                        <small style="color:#999;">Format: JPG, PNG, WEBP (Max 5MB)</small>
                        <?php if ($action === 'edit' && !empty($editMovie['poster'])): ?>
                            <img src="<?php echo $editMovie['poster']; ?>" class="poster-preview" id="preview">
                        <?php else: ?>
                            <img id="preview" class="poster-preview" style="display:none;">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>URL Trailer YouTube (Opsional)</label>
                        <input type="url" name="trailer_url" placeholder="https://youtube.com/watch?v=..." value="<?php echo $editMovie['trailer_url'] ?? ''; ?>">
                    </div>
                    
                    <div style="display:flex; gap:10px; margin-top:30px;">
                        <button type="submit" name="<?php echo $action === 'edit' ? 'update_movie' : 'create_movie'; ?>" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Film' : 'Simpan Film'; ?>
                        </button>
                        <a href="?action=movies" class="btn btn-danger">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'add_review'): ?> 
            <div class="header">
                <h2>➕ Tambah Review Manual</h2>
            </div>
            
            <div class="card">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilih Film <span style="color:red;">*</span></label>
                            <select name="movie_id" required>
                                <option value="">-- Pilih Film --</option>
                                <?php foreach($moviesList as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Pilih User <span style="color:red;">*</span></label>
                            <select name="user_id" required>
                                <option value="">-- Pilih User --</option>
                                <?php foreach($usersList as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['username'] . (!empty($user['full_name']) ? ' (' . $user['full_name'] . ')' : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rating (1-5 Bintang) <span style="color:red;">*</span></label>
                        <div class="rating-radio-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                                    <?php echo $i; ?> <i class="fas fa-star" style="color:#ffc107;"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Teks Review</label>
                        <textarea name="review_text" placeholder="Tuliskan ulasan manual di sini (Opsional)..."></textarea>
                    </div>
                    
                    <div style="display:flex; gap:10px; margin-top:30px;">
                        <button type="submit" name="submit_review" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Review
                        </button>
                        <a href="?action=reviews" class="btn btn-danger">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        
        <?php elseif ($action === 'reviews'): ?>
            <div class="header">
                <h2>⭐ Kelola Reviews</h2>
                <span style="color:#999;">Total: <?php echo count($reviews); ?> reviews</span>
            </div>
            
            <div class="card">
                <?php if (count($reviews) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Film</th>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($review['movie_title'] ?? 'Unknown'); ?></strong></td>
                                <td><?php echo htmlspecialchars($review['full_name'] ?? $review['username'] ?? 'Anonymous'); ?></td>
                                <td><span class="rating-badge">⭐ <?php echo number_format($review['rating'], 1); ?></span></td>
                                <td style="max-width:300px;"><?php echo substr(htmlspecialchars($review['review_text']), 0, 100); ?>...</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <a href="?action=reviews&delete_review=<?php echo $review['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus review ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>Belum Ada Review</h3>
                        <p>Review dari user akan muncul di sini</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align:center; padding:30px; color:#999; margin-top:50px;">
            <p>© 2024 Movie Review Admin Panel</p>
            <p style="margin-top:10px;">Made with ❤️ by Your Team</p>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Auto-hide success messages after 5 seconds
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