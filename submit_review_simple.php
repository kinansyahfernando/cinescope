<?php
/**
 * SIMPAN FILE INI DI: C:\xampp\htdocs\movie-review\submit_review_simple.php
 */

// CORS & Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DATABASE CONNECTION

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong


try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (PDOException $e) {
    echo json_encode(array(
        "success" => false,
        "message" => "Koneksi database gagal: " . $e->getMessage()
    ));
    exit;
}

// GET INPUT
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(array("success" => false, "message" => "Invalid JSON"));
    exit;
}

$movie_title = isset($data["movie_title"]) ? trim($data["movie_title"]) : "";
$rating = isset($data["rating"]) ? floatval($data["rating"]) : 0;
$review_text = isset($data["review_text"]) ? trim($data["review_text"]) : "";
$user_name = isset($data["user_name"]) ? trim($data["user_name"]) : "Anonymous";

// VALIDASI
if (empty($movie_title) || $rating <= 0 || empty($review_text)) {
    echo json_encode(array(
        "success" => false,
        "message" => "Semua field harus diisi"
    ));
    exit;
}

// CARI MOVIE
$stmt = $pdo->prepare("SELECT id FROM movies WHERE title = ? LIMIT 1");
$stmt->execute(array($movie_title));
$movie = $stmt->fetch();

if (!$movie) {
    echo json_encode(array(
        "success" => false,
        "message" => "Film '$movie_title' tidak ditemukan"
    ));
    exit;
}

$movie_id = $movie["id"];

// CARI/BUAT USER
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->execute(array($user_name));
$user_id = $stmt->fetchColumn();

if (!$user_id) {
    $email = strtolower(str_replace(" ", "_", $user_name)) . "@cinescope.local";
    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute(array($user_name, $user_name, $email));
    $user_id = $pdo->lastInsertId();
}

// INSERT REVIEW
try {
    $stmt = $pdo->prepare("
        INSERT INTO reviews 
        (movie_id, user_id, rating, review_text, is_spoiler, helpful_count, created_at, updated_at)
        VALUES (?, ?, ?, ?, 0, 0, NOW(), NOW())
    ");
    
    $stmt->execute(array($movie_id, $user_id, $rating, $review_text));
    $review_id = $pdo->lastInsertId();
    
    // UPDATE AVERAGE
    $stmt = $pdo->prepare("SELECT ROUND(AVG(rating), 1) FROM reviews WHERE movie_id = ?");
    $stmt->execute(array($movie_id));
    $new_avg = $stmt->fetchColumn();
    
    echo json_encode(array(
        "success" => true,
        "message" => "Review berhasil disimpan!",
        "data" => array(
            "review_id" => $review_id,
            "movie_id" => $movie_id,
            "new_avg_rating" => $new_avg
        )
    ));
    
} catch (PDOException $e) {
    echo json_encode(array(
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ));
}
?>