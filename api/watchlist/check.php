<?php
require_once '../middleware/auth.php';

$user = authenticate();

if (empty($_GET['movie_id'])) {
    sendResponse(false, null, 'Movie ID is required', 400);
}

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT id FROM watchlist 
        WHERE user_id = ? AND movie_id = ?
    ");
    $stmt->execute([$user['id'], $_GET['movie_id']]);
    
    $exists = (bool)$stmt->fetch();
    
    sendResponse(true, ['in_watchlist' => $exists]);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>