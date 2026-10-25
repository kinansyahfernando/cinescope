<?php
// File: api/submit_review.php
// Simpan di: C:/xampp/htdocs/movie-review/api/submit_review.php

// ==================== HEADER DAN KONEKSI ====================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// AKTIFKAN DISPLAY ERROR SEMENTARA untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); 

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GUNAKAN KONFIGURASI YANG SAMA DENGAN ADMIN.PHP
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

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
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

// ==================== BACA INPUT DAN VALIDASI ====================
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Tambahkan log input mentah untuk debugging
error_log("Raw JSON received: " . $raw);
error_log("Decoded data: " . print_r($data, true));

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON input or empty body. Raw: " . $raw
    ]);
    exit;
}

// Ambil dan bersihkan data
$movie_title = trim($data["movie_title"] ?? "");
$rating = floatval($data["rating"] ?? 0);
$review_text = trim($data["review_text"] ?? "");
// Asumsi 'user_name' dikirim dari klien, jika tidak ada, gunakan default
$user_name = trim($data["user_name"] ?? "Anonymous"); 

// Validasi
if (empty($movie_title) || $rating <= 0 || $rating > 5) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (movie_title, rating must be 1-5, review_text). Title: $movie_title, Rating: $rating"
    ]);
    exit;
}

// ==================== PROSES DATABASE (TRANSAKSI) ====================
try {
    $pdo->beginTransaction(); // Mulai transaksi

    // 1. Cari Movie ID
    $stmt = $pdo->prepare("SELECT id FROM movies WHERE title = :title LIMIT 1");
    $stmt->execute([':title' => $movie_title]);
    $movie = $stmt->fetch();
    
    if (!$movie) {
        throw new Exception("Movie '$movie_title' not found in database.");
    }
    
    $movie_id = $movie['id'];
    
    // 2. Cari atau buat User
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $user_name]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Buat user baru
        $email = strtolower(str_replace(' ', '_', $user_name)) . '@cinescope.local';
        $stmt = $pdo->prepare("
            INSERT INTO users (username, full_name, email, created_at) 
            VALUES (:username, :fullname, :email, NOW())
        ");
        $stmt->execute([
            ':username' => $user_name,
            ':fullname' => $user_name,
            ':email' => $email
        ]);
        $user_id = $pdo->lastInsertId();
    } else {
        $user_id = $user['id'];
    }
    
    // 3. Insert Review
    $stmt = $pdo->prepare("
        INSERT INTO reviews 
        (movie_id, user_id, rating, review_text, is_spoiler, helpful_count, created_at, updated_at)
        VALUES (:movie_id, :user_id, :rating, :review_text, 0, 0, NOW(), NOW())
    ");
    
    $stmt->execute([
        ':movie_id' => $movie_id,
        ':user_id' => $user_id,
        ':rating' => $rating,
        ':review_text' => $review_text
    ]);
    
    $review_id = $pdo->lastInsertId();
    
    // 4. Update rating rata-rata di tabel movies
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
    
    $pdo->commit(); // Commit transaksi jika semua berhasil
    
    // Success response
    echo json_encode([
        "success" => true,
        "message" => "Review berhasil disimpan!",
        "data" => [
            "review_id" => $review_id,
            "movie_id" => $movie_id,
            "user_id" => $user_id,
            "new_avg_rating" => $stats['avg_rating'],
            "total_reviews" => $stats['total_reviews']
        ]
    ]);
    
} catch (Exception $e) { // Tangkap PDOException dan Exception umum
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback jika ada error
    }
    error_log("Submit Review Error: " . $e->getMessage()); // Log error ke file log server
    http_response_code(500); // Set status HTTP ke 500
    echo json_encode([
        "success" => false,
        "message" => "Proses gagal: " . $e->getMessage()
    ]);
}
?>