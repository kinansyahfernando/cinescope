<?php
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    $limit = isset($_GET['limit']) ? min(20, max(1, (int)$_GET['limit'])) : 10;
    $days = isset($_GET['days']) ? max(1, (int)$_GET['days']) : 7;
    
    // Movies dengan review terbanyak dalam X hari terakhir
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            COUNT(r.id) as recent_reviews
        FROM movies m
        LEFT JOIN reviews r ON m.id = r.movie_id 
            AND r.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY m.id
        HAVING recent_reviews > 0
        ORDER BY recent_reviews DESC, m.average_rating DESC
        LIMIT ?
    ");
    
    $stmt->execute([$days, $limit]);
    $movies = $stmt->fetchAll();
    
    sendResponse(true, $movies);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>