<?php
// File: api/get_reviews.php
// Pastikan file ini berada di folder 'api'

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

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
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "DB Error: " . $e->getMessage()));
    exit;
}

// Ambil parameter movie dari URL (e.g., ?movie=Dilan%201990)
$movie_title = isset($_GET['movie']) ? $_GET['movie'] : '';

if ($movie_title === '') {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Movie title required"));
    exit;
}

try {
    // 1. Cari movie ID
    $stmt = $pdo->prepare("SELECT id, average_rating, total_reviews FROM movies WHERE title = :t LIMIT 1");
    $stmt->execute(array(":t" => $movie_title));
    $movie = $stmt->fetch();

    if (!$movie) {
        // Beri respons sukses (true) tetapi dengan data kosong jika film tidak ditemukan,
        // agar front-end bisa menampilkan "Belum Ada Ulasan".
        echo json_encode(array(
            "success" => true,
            "message" => "Film ditemukan, tetapi tidak ada review.",
            "reviews" => [],
            "average_rating" => 0,
            "total_reviews" => 0
        ));
        exit;
    }

    $movie_id = $movie['id'];

    // 2. Ambil semua review untuk movie ini
    $stmt = $pdo->prepare("
        SELECT 
            r.rating,
            r.review_text as text,
            COALESCE(u.username, u.full_name, 'Anonymous') as name,
            DATE_FORMAT(r.created_at, '%Y-%m-%d') as date
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.movie_id = :m
        ORDER BY r.created_at DESC
    ");
    $stmt->execute(array(":m" => $movie_id));
    $reviews = $stmt->fetchAll();

    // 3. Ambil statistik dari tabel movies (sudah dihitung oleh submit_review.php)
    $avg_rating = $movie['average_rating'] ?? 0;
    $total_reviews = $movie['total_reviews'] ?? 0;

    // Success response
    echo json_encode(array(
        "success" => true,
        "message" => "Reviews berhasil dimuat",
        "reviews" => $reviews,
        "average_rating" => $avg_rating,
        "total_reviews" => $total_reviews
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Server Error: " . $e->getMessage()));
}
?>