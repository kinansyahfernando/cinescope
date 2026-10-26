<?php
// File: migrate_review_handler.php
// Simpan di: C:/xampp/htdocs/movie-review/migrate_review_handler.php
// Handler untuk menerima review dari localStorage dan simpan ke database

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable untuk production

// Database connection

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit;
}

// Get POST data
$movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : 'Anonymous';
$rating = isset($_POST['rating']) ? (float)$_POST['rating'] : 8.0;
$reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
$isSpoiler = isset($_POST['is_spoiler']) ? (int)$_POST['is_spoiler'] : 0;

// Validate
if ($movieId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid movie ID'
    ]);
    exit;
}

if (empty($reviewText)) {
    $reviewText = "Review dari pengguna " . $username;
}

// Validate rating (convert 5-scale to 10-scale if needed)
// Keep rating in 5-scale (no conversion needed)
if ($rating < 0 || $rating > 5) {
    $rating = 4.0; // Default rating in 5-scale
}
try {
    // Check if movie exists
    $checkMovie = $pdo->prepare("SELECT id, title FROM movies WHERE id = :id");
    $checkMovie->execute([':id' => $movieId]);
    $movie = $checkMovie->fetch(PDO::FETCH_ASSOC);
    
    if (!$movie) {
        echo json_encode([
            'success' => false,
            'message' => 'Movie not found (ID: ' . $movieId . ')'
        ]);
        exit;
    }
    
    // Check or create user
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $checkUser->execute([':username' => $username]);
    $user = $checkUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Create guest user automatically
        $createUser = $pdo->prepare("
            INSERT INTO users (username, full_name, email, password, created_at) 
            VALUES (:username, :full_name, :email, :password, NOW())
        ");
        
        $createUser->execute([
            ':username' => $username,
            ':full_name' => $username,
            ':email' => strtolower(str_replace(' ', '', $username)) . '@guest.com',
            ':password' => password_hash('guest123', PASSWORD_DEFAULT)
        ]);
        
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
    }
    
    // Check if review already exists (prevent duplicate)
    $checkDuplicate = $pdo->prepare("
        SELECT id FROM reviews 
        WHERE movie_id = :movie_id 
        AND user_id = :user_id 
        AND review_text = :review_text
        LIMIT 1
    ");
    
    $checkDuplicate->execute([
        ':movie_id' => $movieId,
        ':user_id' => $userId,
        ':review_text' => $reviewText
    ]);
    
    if ($checkDuplicate->fetch()) {
        echo json_encode([
            'success' => true,
            'message' => 'Review already exists (skipped duplicate)',
            'duplicate' => true
        ]);
        exit;
    }
    
    // Insert review
    $insertReview = $pdo->prepare("
        INSERT INTO reviews (movie_id, user_id, rating, review_text, is_spoiler, helpful_count, created_at, updated_at) 
        VALUES (:movie_id, :user_id, :rating, :review_text, :is_spoiler, 0, NOW(), NOW())
    ");
    
    $insertReview->execute([
        ':movie_id' => $movieId,
        ':user_id' => $userId,
        ':rating' => $rating,
        ':review_text' => $reviewText,
        ':is_spoiler' => $isSpoiler
    ]);
    
    $reviewId = $pdo->lastInsertId();
    
    // Update movie average rating
    $updateRating = $pdo->prepare("
        UPDATE movies 
        SET average_rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE movie_id = :movie_id
        ),
        updated_at = NOW()
        WHERE id = :movie_id
    ");
    
    $updateRating->execute([':movie_id' => $movieId]);
    
    // Get new average
    $getAvg = $pdo->prepare("SELECT average_rating FROM movies WHERE id = :id");
    $getAvg->execute([':id' => $movieId]);
    $newAvg = $getAvg->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Review berhasil ditambahkan!',
        'data' => [
            'review_id' => $reviewId,
            'movie_id' => $movieId,
            'movie_title' => $movie['title'],
            'user_id' => $userId,
            'username' => $username,
            'rating' => $rating,
            'new_average' => round($newAvg, 1)
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>