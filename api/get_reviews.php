<?php
// get_reviews.php - API untuk mengambil review
header('Content-Type: application/json');
require_once 'config.php';

$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$response = ['success' => false, 'data' => []];

if ($movie_id > 0) {
    // Jika user_id ada, ambil review user tersebut saja
    if ($user_id > 0) {
        $sql = "SELECT r.*, u.username, u.email 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.movie_id = ? AND r.user_id = ?
                ORDER BY r.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $movie_id, $user_id);
    } else {
        // Ambil semua review untuk movie tersebut
        $sql = "SELECT r.*, u.username, u.email 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.movie_id = ? 
                ORDER BY r.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $movie_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $reviews = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = [
            'id' => $row['id'],
            'rating' => $row['rating'],
            'text' => $row['review_text'],
            'name' => $row['username'] ?? 'Guest',
            'email' => $row['email'] ?? '',
            'date' => date('Y-m-d', strtotime($row['created_at'])),
            'is_spoiler' => $row['is_spoiler'],
            'helpful_count' => $row['helpful_count']
        ];
    }
    
    $response['success'] = true;
    $response['data'] = $reviews;
    mysqli_stmt_close($stmt);
}

// Ambil statistik movie
if ($movie_id > 0) {
    $sql = "SELECT average_rating, total_reviews FROM movies WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $movie_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $movie_stats = mysqli_fetch_assoc($result);
    
    $response['stats'] = [
        'average_rating' => $movie_stats['average_rating'] ?? 0,
        'total_reviews' => $movie_stats['total_reviews'] ?? 0
    ];
    mysqli_stmt_close($stmt);
}

echo json_encode($response);
mysqli_close($conn);
?>