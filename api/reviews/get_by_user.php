<?php
require_once '../config/database.php';

if (empty($_GET['user_id'])) {
    sendResponse(false, null, 'User ID is required', 400);
}

try {
    $conn = getDBConnection();
    
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            m.title,
            m.poster,
            m.release_year
        FROM reviews r
        JOIN movies m ON r.movie_id = m.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$_GET['user_id'], $limit, $offset]);
    $reviews = $stmt->fetchAll();
    
    // Total count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE user_id = ?");
    $stmt->execute([$_GET['user_id']]);
    $total = $stmt->fetch()['total'];
    
    sendResponse(true, [
        'reviews' => $reviews,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>