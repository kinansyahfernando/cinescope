<?php
// save_review.php - API untuk menyimpan review
header('Content-Type: application/json');
require_once 'config.php';

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = mysqli_real_escape_string($conn, $data['movie_id']);
    $user_id = mysqli_real_escape_string($conn, $data['user_id']);
    $rating = intval($data['rating']);
    $review_text = mysqli_real_escape_string($conn, $data['review_text']);
    $is_spoiler = isset($data['is_spoiler']) ? intval($data['is_spoiler']) : 0;
    
    // Validasi
    if (empty($movie_id) || empty($user_id) || $rating < 1 || $rating > 10) {
        $response['message'] = 'Data tidak valid';
        echo json_encode($response);
        exit;
    }
    
    // Insert review ke database
    $sql = "INSERT INTO reviews (movie_id, user_id, rating, review_text, is_spoiler, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iidsi", $movie_id, $user_id, $rating, $review_text, $is_spoiler);
    
    if (mysqli_stmt_execute($stmt)) {
        $review_id = mysqli_insert_id($conn);
        
        // Update average rating di tabel movies
        updateMovieRating($conn, $movie_id);
        
        $response['success'] = true;
        $response['message'] = 'Review berhasil disimpan';
        $response['review_id'] = $review_id;
    } else {
        $response['message'] = 'Gagal menyimpan review: ' . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Method tidak diizinkan';
}

echo json_encode($response);

function updateMovieRating($conn, $movie_id) {
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
            FROM reviews 
            WHERE movie_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $movie_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    $avg_rating = round($data['avg_rating'], 1);
    $total_reviews = $data['total_reviews'];
    
    // Update tabel movies
    $update_sql = "UPDATE movies SET average_rating = ?, total_reviews = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "dii", $avg_rating, $total_reviews, $movie_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
}

mysqli_close($conn);
?>