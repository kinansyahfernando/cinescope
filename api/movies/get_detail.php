<?php
require_once '../config/database.php';

if (empty($_GET['id'])) {
    sendResponse(false, null, 'Movie ID is required', 400);
}

try {
    $conn = getDBConnection();
    
    // Get movie detail
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $movie = $stmt->fetch();
    
    if (!$movie) {
        sendResponse(false, null, 'Movie not found', 404);
    }
    
    // Get reviews
    $stmt = $conn->prepare("
        SELECT r.*, u.username, u.profile_picture 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.movie_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$_GET['id']]);
    $movie['reviews'] = $stmt->fetchAll();
    
    // Rating distribution
    $stmt = $conn->prepare("
        SELECT 
            FLOOR(rating) as rating_group,
            COUNT(*) as count
        FROM reviews 
        WHERE movie_id = ?
        GROUP BY rating_group
        ORDER BY rating_group DESC
    ");
    $stmt->execute([$_GET['id']]);
    $movie['rating_distribution'] = $stmt->fetchAll();
    
    sendResponse(true, $movie);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>