<?php
/**
 * SIMPAN FILE INI DI: C:\xampp\htdocs\movie-review\get_reviews_simple.php
 */

// CORS & Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

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

// GET MOVIE TITLE
$movie_title = isset($_GET['movie']) ? trim($_GET['movie']) : '';

if (empty($movie_title)) {
    echo json_encode(array(
        "success" => false,
        "message" => "Parameter 'movie' diperlukan"
    ));
    exit;
}

// CARI MOVIE
$stmt = $pdo->prepare("SELECT id, title FROM movies WHERE title = ? LIMIT 1");
$stmt->execute(array($movie_title));
$movie = $stmt->fetch();

if (!$movie) {
    echo json_encode(array(
        "success" => false,
        "message" => "Film '$movie_title' tidak ditemukan"
    ));
    exit;
}

$movie_id = $movie['id'];

// GET REVIEWS
$stmt = $pdo->prepare("
    SELECT 
        r.rating,
        r.review_text as text,
        COALESCE(u.username, u.full_name, 'Anonymous') as name,
        DATE_FORMAT(r.created_at, '%Y-%m-%d') as date
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");

$stmt->execute(array($movie_id));
$reviews = $stmt->fetchAll();

// CALCULATE AVERAGE
$avg = 0;
if (count($reviews) > 0) {
    $stmt = $pdo->prepare("SELECT ROUND(AVG(rating), 1) FROM reviews WHERE movie_id = ?");
    $stmt->execute(array($movie_id));
    $avg = $stmt->fetchColumn();
}

// RESPONSE
echo json_encode(array(
    "success" => true,
    "reviews" => $reviews,
    "average" => floatval($avg),
    "count" => count($reviews),
    "movie_id" => $movie_id
));
?>