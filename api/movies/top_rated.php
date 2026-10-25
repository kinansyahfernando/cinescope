<?php
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
    $minReviews = isset($_GET['min_reviews']) ? max(1, (int)$_GET['min_reviews']) : 5;
    
    $stmt = $conn->prepare("
        SELECT * FROM movies 
        WHERE total_reviews >= ?
        ORDER BY average_rating DESC, total_reviews DESC
        LIMIT ?
    ");
    
    $stmt->execute([$minReviews, $limit]);
    $movies = $stmt->fetchAll();
    
    sendResponse(true, $movies);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>