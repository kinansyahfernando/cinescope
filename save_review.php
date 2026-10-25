<?php
// File: save_review.php
// Simpan di: C:/xampp/htdocs/movie-review/save_review.php
// API untuk menyimpan review dari HTML ke Database

// CORS Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log untuk debugging
file_put_contents('review_log.txt', "\n=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

// Database Configuration
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Ambil data JSON dari request
$input = json_decode(file_get_contents('php://input'), true);

file_put_contents('review_log.txt', "Input: " . json_encode($input, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

// Validasi input
if (!isset($input['movie_title']) || !isset($input['rating']) || !isset($input['review_text'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields',
        'received' => array_keys($input)
    ]);
    exit;
}

$movieTitle = trim($input['movie_title']);
$rating = floatval($input['rating']);
$reviewText = $input['review_text'];
$userName = $input['user_name'] ?? 'Pengguna CineScope Baru';

file_put_contents('review_log.txt', "Searching for movie: '$movieTitle'\n", FILE_APPEND);

// ================================================
// PENCARIAN FILM YANG LEBIH FLEKSIBEL
// ================================================

// Coba cari dengan EXACT match dulu
$stmt = $pdo->prepare("SELECT id, title FROM movies WHERE title = :title LIMIT 1");
$stmt->execute([':title' => $movieTitle]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

// Kalau tidak ketemu, coba LIKE
if (!$movie) {
    $stmt = $pdo->prepare("SELECT id, title FROM movies WHERE title LIKE :title LIMIT 1");
    $stmt->execute([':title' => "%$movieTitle%"]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Kalau masih tidak ketemu, coba dari original_title
if (!$movie) {
    $stmt = $pdo->prepare("SELECT id, title FROM movies WHERE original_title LIKE :title LIMIT 1");
    $stmt->execute([':title' => "%$movieTitle%"]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Kalau tetap tidak ketemu, coba mapping manual untuk film-film tertentu
if (!$movie) {
    // Mapping manual untuk judul yang sering berbeda
   $manualMapping = [
    'Dilan 1990' => 'Dilan 1990',  // Sesuaikan dengan judul di database
    'Ada Apa Dengan Cinta? (Rangga & Cinta)' => 'Ada Apa dengan Cinta?',
    'Sore (Ada Apa Dengan Cinta? 3)' => 'Sore',
    'Rest Area' => 'Rest Area',
    'Tukar Takdir' => 'Tukar Takdir',
    'Petaka Gunung Gede' => 'Petaka Gunung Gede'
];
    
    if (isset($manualMapping[$movieTitle])) {
        $mappedTitle = $manualMapping[$movieTitle];
        file_put_contents('review_log.txt', "Using manual mapping: '$movieTitle' -> '$mappedTitle'\n", FILE_APPEND);
        
        $stmt = $pdo->prepare("SELECT id, title FROM movies WHERE title LIKE :title LIMIT 1");
        $stmt->execute([':title' => "%$mappedTitle%"]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$movie) {
    // Tampilkan semua film yang ada untuk debugging
    $allMovies = $pdo->query("SELECT id, title FROM movies ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $movieList = array_map(function($m) { return $m['id'] . ': ' . $m['title']; }, $allMovies);
    
    file_put_contents('review_log.txt', "Movie NOT FOUND! Available movies:\n" . print_r($movieList, true) . "\n", FILE_APPEND);
    
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'Movie not found: ' . $movieTitle,
        'searched_title' => $movieTitle,
        'available_movies' => $movieList
    ]);
    exit;
}

$movieId = $movie['id'];
$foundMovieTitle = $movie['title'];

file_put_contents('review_log.txt', "✓ Movie found! ID: $movieId, Title: '$foundMovieTitle'\n", FILE_APPEND);

// ================================================
// PROSES USER
// ================================================

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
$stmt->execute([':username' => $userName]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Buat user baru
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, created_at) 
                           VALUES (:username, :email, :password, :full_name, NOW())");
    $stmt->execute([
        ':username' => $userName,
        ':email' => strtolower(str_replace(' ', '', $userName)) . '@cinescope.com',
        ':password' => password_hash('password123', PASSWORD_DEFAULT),
        ':full_name' => $userName
    ]);
    $userId = $pdo->lastInsertId();
    file_put_contents('review_log.txt', "✓ New user created! ID: $userId\n", FILE_APPEND);
} else {
    $userId = $user['id'];
    file_put_contents('review_log.txt', "✓ Existing user found! ID: $userId\n", FILE_APPEND);
}

// ================================================
// SIMPAN REVIEW
// ================================================

try {
    $stmt = $pdo->prepare("INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at) 
                           VALUES (:movie_id, :user_id, :rating, :review_text, NOW())");
    $stmt->execute([
        ':movie_id' => $movieId,
        ':user_id' => $userId,
        ':rating' => $rating,
        ':review_text' => $reviewText
    ]);
    
    $reviewId = $pdo->lastInsertId();
    
    // Update average rating
    $stmt = $pdo->prepare("UPDATE movies SET average_rating = (
        SELECT AVG(rating) FROM reviews WHERE movie_id = :movie_id
    ) WHERE id = :movie_id");
    $stmt->execute([':movie_id' => $movieId]);
    
    file_put_contents('review_log.txt', "✓ SUCCESS! Review ID: $reviewId saved for movie '$foundMovieTitle'\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Review berhasil disimpan!',
        'review_id' => $reviewId,
        'movie_id' => $movieId,
        'movie_title' => $foundMovieTitle,
        'user_id' => $userId,
        'rating' => $rating
    ]);
    
} catch(PDOException $e) {
    file_put_contents('review_log.txt', "✗ ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving review: ' . $e->getMessage()]);
}
$movieMapping = [
    'Inception' => 1,
    'The Shawshank Redemption' => 2,
    'Pulp Fiction' => 4,
    'Parasite' => 5,
    '기생충' => 5,
    'film rawr' => 6,
    'film rawr' => 7,
    'pp' => 8,
    'coba tes' => 9,
    'rawrrrr' => 9,
    'AYAM NASI' => 10,
    'gorengan' => 10,
    'Sore' => 11,
    'Petaka Gunung Gede' => 12,
    'Dilan 1990' => 13,
    'Rest Area' => 14,
    'Tukar Takdir' => 15,
    'Rangga & Cinta' => 16,
    'Sore' => 17,
    'Petaka Gunung Gede' => 18,
    'Dilan 1990' => 19,
    'Rest Area' => 20,
    'Tukar Takdir' => 21,
    'Rangga & Cinta' => 22,
];
?>