<?php
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    $stmt = $conn->query("
        SELECT DISTINCT genre FROM movies 
        WHERE genre IS NOT NULL AND genre != ''
        ORDER BY genre
    ");
    
    $genreRows = $stmt->fetchAll();
    
    // Extract unique genres (karena genre bisa comma-separated)
    $genres = [];
    foreach ($genreRows as $row) {
        $genreList = explode(',', $row['genre']);
        foreach ($genreList as $genre) {
            $genre = trim($genre);
            if ($genre && !in_array($genre, $genres)) {
                $genres[] = $genre;
            }
        }
    }
    
    sort($genres);
    
    sendResponse(true, $genres);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>