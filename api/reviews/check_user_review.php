<?php
require_once '../middleware/auth.php';

$user = authenticate();

if (empty($_GET['movie_id'])) {
    sendResponse(false, null, 'Movie ID is required', 400);
}

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT * FROM reviews 
        WHERE user_id = ? AND movie_id = ?
    ");
    $stmt->execute([$user['id'], $_GET['movie_id']]);
    $review = $stmt->fetch();
    
    sendResponse(true, [
        'has_reviewed' => (bool)$review,
        'review' => $review ?: null
    ]);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>