<?php
require_once '../middleware/auth.php';

$user = authenticate();

try {
    $conn = getDBConnection();
    
    // Hitung statistik user
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalReviews = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM watchlist WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalWatchlist = $stmt->fetch()['total'];
    
    $user['stats'] = [
        'total_reviews' => $totalReviews,
        'total_watchlist' => $totalWatchlist
    ];
    
    sendResponse(true, $user);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>