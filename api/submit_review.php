<?php
// File: api/submit_review.php
// Pastikan file ini berada di folder 'api'

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan di production

// Handle preflight CORS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database Configuration
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = ''; // Kosongkan jika Anda tidak menggunakan password di XAMPP

// Koneksi Database
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database Connection Error: " . $e->getMessage()
    ]);
    exit;
}

// Pastikan request adalah POST dan body berisi JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty(file_get_contents("php://input"))) {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Metode request tidak diizinkan atau data kosong."]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// 1. Ambil dan sanitasi data input
$movie_title = trim($data["movie_title"] ?? "");
$rating = (int)($data["rating"] ?? 0);
$review_text = trim($data["review_text"] ?? "");
$user_name = trim($data["user_name"] ?? "Anonymous"); // Gunakan nama default jika tidak ada

if (empty($movie_title) || $rating < 1 || $rating > 5 || empty($review_text)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Data ulasan tidak lengkap atau tidak valid."]);
    exit;
}

try {
    // 2. Cari Movie ID
    $stmt = $pdo->prepare("SELECT id FROM movies WHERE title = :t LIMIT 1");
    $stmt->execute([':t' => $movie_title]);
    $movie = $stmt->fetch();

    if (!$movie) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Film '$movie_title' tidak ditemukan di database."]);
        exit;
    }
    $movie_id = $movie['id'];

    // 3. Cari/Buat User (Asumsi user dibuat jika belum ada)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR full_name = :u LIMIT 1");
    $stmt->execute([':u' => $user_name]);
    $user = $stmt->fetch();

    if ($user) {
        $user_id = $user['id'];
    } else {
        // Jika user tidak ditemukan, buat user baru (dengan nama yang sama)
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, created_at, updated_at) VALUES (:u, :u, NOW(), NOW())");
        $stmt->execute([':u' => $user_name]);
        $user_id = $pdo->lastInsertId();
    }

    // 4. Insert Review
    $stmt = $pdo->prepare("
        INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at, updated_at) 
        VALUES (:movie_id, :user_id, :rating, :review_text, NOW(), NOW())
    ");
    
    $stmt->execute([
        ':movie_id' => $movie_id,
        ':user_id' => $user_id,
        ':rating' => $rating,
        ':review_text' => $review_text
    ]);
    
    $review_id = $pdo->lastInsertId();
    
    // 5. Update rating rata-rata di tabel movies
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_reviews
        FROM reviews 
        WHERE movie_id = :movie_id
    ");
    $stmt->execute([':movie_id' => $movie_id]);
    $stats = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        UPDATE movies 
        SET average_rating = :avg_rating, 
            total_reviews = :total_reviews,
            updated_at = NOW()
        WHERE id = :movie_id
    ");
    $stmt->execute([
        ':avg_rating' => $stats['avg_rating'],
        ':total_reviews' => $stats['total_reviews'],
        ':movie_id' => $movie_id
    ]);
    
    // Success response
    echo json_encode([
        "success" => true,
        "message" => "Review berhasil disimpan!",
        "data" => [
            "review_id" => $review_id,
            "movie_id" => $movie_id
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error saat menyimpan data: " . $e->getMessage()]);
}
?>