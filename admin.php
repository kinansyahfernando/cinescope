<?php
// File: admin.php (FIXED VERSION - Sync Rating System)
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
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

// Create upload folders if not exist
$uploadDirs = ['uploads', 'uploads/posters', 'uploads/videos'];
foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0777, true);
}

$message = '';
$messageType = '';
$action = $_GET['action'] ?? 'dashboard';

// =======================
// 🔥 UNIVERSAL SYNC SYSTEM (FIXED)
// =======================

function getBaseMovieTitle($title) {
    $baseTitle = preg_replace('/\s*\d{4}\s*/', ' ', $title);
    $baseTitle = preg_split('/[:\-–—]/', $baseTitle)[0];
    $baseTitle = preg_replace('/\s*(part|bagian|episode|ep)?\s*\d+\s*$/i', '', $baseTitle);
    $baseTitle = trim($baseTitle);
    return strtolower($baseTitle);
}

function syncAllSeriesMovies(PDO $pdo, int $movieId, string $title, string $posterPath) {
    $baseTitle = getBaseMovieTitle($title);
    
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$movieId]);
    $sourceMovie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceMovie) return ['success' => false, 'count' => 0];
    
    // Cari semua film dengan nama serupa
    $findStmt = $pdo->query("SELECT id, title FROM movies");
    $allMovies = $findStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $syncedCount = 0;
    foreach ($allMovies as $movie) {
        if ($movie['id'] == $movieId) continue;
        
        $otherBaseTitle = getBaseMovieTitle($movie['title']);
        
        if ($baseTitle === $otherBaseTitle) {
            $updateStmt = $pdo->prepare("
                UPDATE movies SET 
                    poster = :poster,
                    release_year = :release_year,
                    duration = :duration,
                    genre = :genre,
                    director = :director,
                    synopsis = :synopsis,
                    trailer_url = :trailer_url,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':poster' => $posterPath,
                ':release_year' => $sourceMovie['release_year'],
                ':duration' => $sourceMovie['duration'],
                ':genre' => $sourceMovie['genre'],
                ':director' => $sourceMovie['director'],
                ':synopsis' => $sourceMovie['synopsis'],
                ':trailer_url' => $sourceMovie['trailer_url'],
                ':id' => $movie['id']
            ]);
            
            $syncedCount++;
        }
    }
    
    return ['success' => true, 'count' => $syncedCount];
}

// ==================== HELPER FUNCTIONS ====================

function updateMovieAverageRating(PDO $pdo, int $movieId) {
    if ($movieId <= 0) return;

    $sql = "UPDATE movies m 
            SET m.average_rating = (
                SELECT COALESCE(AVG(r.rating), 0)
                FROM reviews r 
                WHERE r.movie_id = ?
            )
            WHERE m.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movieId, $movieId]);
}

// ==================== PROCESS ACTIONS ====================

// CREATE REVIEW
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    try {
        $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
        
        if ($movie_id <= 0) throw new Exception("❌ Film harus dipilih!");
        if ($user_id <= 0) throw new Exception("❌ User harus dipilih!");
        if ($rating < 1 || $rating > 5) throw new Exception("❌ Rating harus 1-5!");
        
        $stmtCheckMovie = $pdo->prepare("SELECT id, title FROM movies WHERE id = ?");
        $stmtCheckMovie->execute([$movie_id]);
        $movieData = $stmtCheckMovie->fetch(PDO::FETCH_ASSOC);
        if (!$movieData) throw new Exception("❌ Film tidak ditemukan!");
        
        $stmtCheckUser = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
        $stmtCheckUser->execute([$user_id]);
        $userData = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);
        if (!$userData) throw new Exception("❌ User tidak ditemukan!");
        
        $stmtCheckExisting = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND movie_id = ?");
        $stmtCheckExisting->execute([$user_id, $movie_id]);
        if ($stmtCheckExisting->fetch()) throw new Exception("⚠️ User ini sudah memberikan review untuk film ini!");
        
        $userName = !empty($userData['full_name']) ? $userData['full_name'] : $userData['username'];
        $reviewText = !empty($review_text) ? $review_text : null;
        
        $pdo->beginTransaction();
        
        $sqlInsert = "INSERT INTO reviews (movie_id, user_id, rating, review_text, user_name, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmtInsert = $pdo->prepare($sqlInsert);
        if (!$stmtInsert->execute([$movie_id, $user_id, $rating, $reviewText, $userName])) {
            throw new Exception("❌ Failed to insert review!");
        }
        
        $pdo->commit();
        updateMovieAverageRating($pdo, $movie_id);
        
        $_SESSION['success_message'] = "✅ Review berhasil ditambahkan untuk film: " . htmlspecialchars($movieData['title']);
        header("Location: admin.php?action=reviews");
        exit;

    } catch(Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

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
    
    // TIDAK PERLU input average_rating manual - biarkan 0 dan akan diupdate otomatis dari reviews
    $sql = "INSERT INTO movies (title, release_year, duration, genre, director, synopsis, poster, trailer_url, average_rating, created_at) 
            VALUES (:title, :release_year, :duration, :genre, :director, :synopsis, :poster, :trailer_url, 0, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $_POST['title'],
        ':release_year' => $_POST['release_year'],
        ':duration' => $_POST['duration'],
        ':genre' => $_POST['genre'],
        ':director' => $_POST['director'],
        ':synopsis' => $_POST['synopsis'],
        ':poster' => $posterPath,
        ':trailer_url' => $_POST['trailer_url']
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
        $posterTarget = 'uploads/posters/' . $posterName;
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $posterTarget)) {
            $posterPath = $posterTarget;
            if (!empty($_POST['existing_poster']) && file_exists($_POST['existing_poster'])) {
                unlink($_POST['existing_poster']);
            }
        }
    }
    
    // UPDATE tanpa mengubah average_rating (diambil dari reviews)
    $sql = "UPDATE movies SET 
            title = :title,
            release_year = :release_year,
            duration = :duration,
            genre = :genre,
            director = :director,
            synopsis = :synopsis,
            poster = :poster,
            trailer_url = :trailer_url,
            updated_at = NOW()
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $_POST['title'],
        ':release_year' => $_POST['release_year'],
        ':duration' => $_POST['duration'],
        ':genre' => $_POST['genre'],
        ':director' => $_POST['director'],
        ':synopsis' => $_POST['synopsis'],
        ':poster' => $posterPath,
        ':trailer_url' => $_POST['trailer_url'],
        ':id' => $movieId
    ]);
    
    // Recalculate rating dari reviews
    updateMovieAverageRating($pdo, $movieId);
    
    // Sync film dengan nama serupa
    $syncResult = syncAllSeriesMovies($pdo, $movieId, $_POST['title'], $posterPath);
    
    $redirectMsg = 'updated';
    if ($syncResult['success'] && $syncResult['count'] > 0) {
        $redirectMsg = 'updated_synced&count=' . $syncResult['count'];
    }
    
    header("Location: admin.php?action=movies&msg=" . $redirectMsg);
    exit;
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
    
    $stmt = $pdo->prepare("SELECT movie_id FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $reviewId]);
    $review = $stmt->fetch();
    $movieId = $review['movie_id'] ?? 0;

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $reviewId]);

    if ($movieId > 0) updateMovieAverageRating($pdo, $movieId);
    
    header("Location: admin.php?action=reviews&msg=review_deleted");
    exit;
}

// Handle session message
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $messageType = 'success';
    unset($_SESSION['success_message']);
}

// Handle messages from URL
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'created': 
            $message = "✅ Film berhasil ditambahkan!"; 
            $messageType = 'success'; 
            break;
        case 'updated': 
            $message = "✅ Film berhasil diupdate!"; 
            $messageType = 'success'; 
            break;
        case 'updated_synced':
            $count = $_GET['count'] ?? 0;
            $message = "✅ Film berhasil diupdate dan di-sync ke {$count} film series lainnya!"; 
            $messageType = 'success'; 
            break;
        case 'deleted': 
            $message = "✅ Film berhasil dihapus!"; 
            $messageType = 'success'; 
            break;
        case 'review_deleted': 
            $message = "✅ Review berhasil dihapus!"; 
            $messageType = 'success'; 
            break;
    }
}

// ==================== FETCH DATA (FIXED - Consistent Rating Calculation) ====================

$moviesStmt = $pdo->query("SELECT m.*, 
    (SELECT COUNT(*) FROM reviews WHERE movie_id = m.id) as review_count,
    COALESCE((SELECT AVG(rating) FROM reviews WHERE movie_id = m.id), 0) as calc_avg_rating
    FROM movies m ORDER BY m.id ASC");
$movies = $moviesStmt->fetchAll();

$reviewsStmt = $pdo->query("SELECT r.*, m.title as movie_title, u.username, u.full_name 
    FROM reviews r 
    LEFT JOIN movies m ON r.movie_id = m.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC LIMIT 100");
$reviews = $reviewsStmt->fetchAll();

$moviesListStmt = $pdo->query("SELECT id, title FROM movies ORDER BY title ASC");
$moviesList = $moviesListStmt->fetchAll(PDO::FETCH_ASSOC);

$usersListStmt = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC");
$usersList = $usersListStmt->fetchAll(PDO::FETCH_ASSOC);

$totalMovies = count($movies);
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avgRating = $pdo->query("SELECT AVG(average_rating) FROM movies WHERE average_rating > 0")->fetchColumn();

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
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
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

        .rating-option {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
            background: white;
        }

        .rating-option:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .rating-option input[type="radio"] {
            margin: 0;
            cursor: pointer;
        }

        .rating-option input[type="radio"]:checked {
            accent-color: #667eea;
        }

        .rating-option:has(input[type="radio"]:checked) {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .sync-notice {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 16px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sync-notice i {
            color: #1976d2;
        }

        .sync-notice span {
            color: #1565c0;
            font-weight: 500;
        }

        .rating-info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .rating-info-box strong {
            color: #856404;
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
            <li><a href="user.php" target="_blank">
                <i class="fas fa-external-link-alt"></i> Lihat Website User
            </a></li>
            <li><a href="index.php" target="_blank">
                <i class="fas fa-globe"></i> Lihat Website Guest
            </a></li>
        </ul>
        
        <div class="quick-links">
            <h4>⚡ Quick Access</h4>
            <a href="get_movies.php" target="_blank"><i class="fas fa-code"></i> API Movies</a>
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
                <h3>🎥 Daftar Film</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Poster</th>
                            <th>Judul</th>
                            <th>Tahun</th>
                            <th>Rating</th>
                            <th>Reviews</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($movies as $movie): ?>
                        <tr>
                            <td><?= $movie['id'] ?></td>
                            <td>
                                <?php if (!empty($movie['poster'])): ?>
                                    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="movie-poster-thumb" alt="">
                                <?php else: ?>
                                    <div style="width:60px;height:90px;background:#667eea;border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;">🎬</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($movie['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                            <td><span class="rating-badge">⭐ <?php echo number_format($movie['calc_avg_rating'], 1); ?></span></td>
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
                            <td><strong><?php echo htmlspecialchars($movie['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                            <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                            <td><span class="rating-badge">⭐ <?php echo number_format($movie['calc_avg_rating'], 1); ?></span></td>
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
            
            <div class="sync-notice">
                <i class="fas fa-info-circle"></i>
                <span>💡 TIP: Film dengan nama serupa (contoh: Sore, Sore: Istri Dari Masa Depan) akan otomatis ter-sync posternya.</span>
            </div>

            <div class="rating-info-box">
                <strong>ℹ️ INFO RATING:</strong> Rating akan dihitung otomatis dari review pengguna. Tidak perlu input manual.
            </div>
            
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="movie_id" value="<?php echo $editMovie['id']; ?>">
                        <input type="hidden" name="existing_poster" value="<?php echo $editMovie['poster']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Judul Film <span style="color:red;">*</span></label>
                        <input type="text" name="title" required value="<?php echo $editMovie['title'] ?? ''; ?>" placeholder="Contoh: Sore">
                    </div>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Tahun Rilis <span style="color:red;">*</span></label>
                            <input type="number" name="release_year" required value="<?php echo $editMovie['release_year'] ?? date('Y'); ?>" min="1900" max="2099">
                        </div>
                        <div class="form-group">
                            <label>Durasi (menit)</label>
                            <input type="number" name="duration" value="<?php echo $editMovie['duration'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Rating Saat Ini (Auto)</label>
                            <input type="text" value="<?php echo $editMovie ? number_format($editMovie['average_rating'], 1) : '0.0'; ?>" disabled style="background:#f0f0f0;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Genre <span style="color:red;">*</span></label>
                        <input type="text" name="genre" required value="<?php echo $editMovie['genre'] ?? ''; ?>" placeholder="Contoh: Drama, Romance">
                    </div>
                    
                    <div class="form-group">
                        <label>Sutradara</label>
                        <input type="text" name="director" value="<?php echo $editMovie['director'] ?? ''; ?>" placeholder="Contoh: Fajar Bustomi">
                    </div>
                    
                    <div class="form-group">
                        <label>Sinopsis <span style="color:red;">*</span></label>
                        <textarea name="synopsis" required placeholder="Tulis sinopsis film..."><?php echo $editMovie['synopsis'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Poster Film</label>
                        <input type="file" name="poster" accept="image/*" onchange="previewImage(this)">
                        <?php if ($action === 'edit' && !empty($editMovie['poster'])): ?>
                            <img src="<?php echo $editMovie['poster']; ?>" class="poster-preview" id="preview">
                        <?php else: ?>
                            <img id="preview" class="poster-preview" style="display:none;">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>URL Trailer YouTube</label>
                        <input type="url" name="trailer_url" value="<?php echo $editMovie['trailer_url'] ?? ''; ?>" placeholder="https://youtube.com/...">
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
            
            <?php if (count($moviesList) === 0): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-film"></i>
                        <h3>Belum Ada Film</h3>
                        <p>Silakan tambahkan film terlebih dahulu</p>
                        <a href="?action=add" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Tambah Film
                        </a>
                    </div>
                </div>
            <?php elseif (count($usersList) === 0): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>Belum Ada User</h3>
                        <p>Pastikan ada user yang terdaftar di database</p>
                    </div>
                </div>
            <?php else: ?>
            
            <div class="card">
                <form method="POST" id="reviewForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilih Film <span style="color:red;">*</span></label>
                            <select name="movie_id" id="movie_id" required>
                                <option value="">-- Pilih Film --</option>
                                <?php foreach($moviesList as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>">
                                        [ID:<?php echo $movie['id']; ?>] <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Pilih User <span style="color:red;">*</span></label>
                            <select name="user_id" id="user_id" required>
                                <option value="">-- Pilih User --</option>
                                <?php foreach($usersList as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        [ID:<?php echo $user['id']; ?>] <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if (!empty($user['full_name'])): ?>
                                            (<?php echo htmlspecialchars($user['full_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rating (1-5 Bintang) <span style="color:red;">*</span></label>
                        <div class="rating-radio-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="rating-option">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                                    <span><?php echo $i; ?> <i class="fas fa-star" style="color:#ffc107;"></i></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Teks Review (Opsional)</label>
                        <textarea name="review_text" id="review_text" placeholder="Tuliskan ulasan..."></textarea>
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
            <?php endif; ?>
        
        <?php elseif ($action === 'reviews'): ?>
            <div class="header">
                <h2>⭐ Kelola Reviews</h2>
                <div>
                    <span style="color:#999; margin-right: 15px;">Total: <?php echo count($reviews); ?> reviews</span>
                    <a href="?action=add_review" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah Review
                    </a>
                </div>
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
                                <td style="max-width:300px;">
                                    <?php 
                                    $reviewText = $review['review_text'] ?? '';
                                    echo !empty($reviewText) ? substr(htmlspecialchars($reviewText), 0, 100) . '...' : '<em style="color:#999;">Tidak ada teks</em>';
                                    ?>
                                </td>
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
                        <a href="?action=add_review" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Tambah Review
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align:center; padding:30px; color:#999; margin-top:50px;">
            <p>© 2024 Movie Review Admin Panel</p>
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
        
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                const movieId = document.getElementById('movie_id').value;
                const userId = document.getElementById('user_id').value;
                const rating = document.querySelector('input[name="rating"]:checked');

                if (!movieId || movieId === '' || movieId === '0') {
                    e.preventDefault();
                    alert('❌ Silakan pilih film terlebih dahulu!');
                    return false;
                }

                if (!userId || userId === '' || userId === '0') {
                    e.preventDefault();
                    alert('❌ Silakan pilih user terlebih dahulu!');
                    return false;
                }

                if (!rating) {
                    e.preventDefault();
                    alert('❌ Silakan pilih rating 1-5 bintang!');
                    return false;
                }

                return true;
            });
        }
    </script>
</body>
</html>