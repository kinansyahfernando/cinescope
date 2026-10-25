<?php
require_once '../config/database.php';

if (empty($_GET['genre'])) {
    sendResponse(false, null, 'Genre is required', 400);
}

try {
    $conn = getDBConnection();
    
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 12;
    $offset = ($page - 1) * $limit;
    
    $stmt = $conn->prepare("
        SELECT * FROM movies 
        WHERE genre LIKE ?
        ORDER BY average_rating DESC
        LIMIT ? OFFSET ?
    ");
    
    $genre = "%{$_GET['genre']}%";
    $stmt->execute([$genre, $limit, $offset]);
    $movies = $stmt->fetchAll();
    
    // Total count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM movies WHERE genre LIKE ?");
    $stmt->execute([$genre]);
    $total = $stmt->fetch()['total'];
    
    sendResponse(true, [
        'movies' => $movies,
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