<?php
require_once '../middleware/auth.php';

$user = authenticate();
requireAdmin($user);

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(false, null, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    sendResponse(false, null, 'Movie ID is required', 400);
}

try {
    $conn = getDBConnection();
    
    $updates = [];
    $params = [];
    
    $allowedFields = ['title', 'original_title', 'release_year', 'duration', 'genre', 
                      'director', 'synopsis', 'poster', 'trailer_url'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (isset($data['cast'])) {
        $updates[] = "cast = ?";
        $params[] = json_encode($data['cast']);
    }
    
    if (empty($updates)) {
        sendResponse(false, null, 'No fields to update', 400);
    }
    
    $params[] = $data['id'];
    $sql = "UPDATE movies SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    sendResponse(true, null, 'Movie updated successfully');
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>