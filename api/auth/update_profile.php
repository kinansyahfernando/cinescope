<?php
require_once '../middleware/auth.php';

$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(false, null, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $conn = getDBConnection();
    
    $updates = [];
    $params = [];
    
    if (isset($data['full_name'])) {
        $updates[] = "full_name = ?";
        $params[] = $data['full_name'];
    }
    
    if (isset($data['bio'])) {
        $updates[] = "bio = ?";
        $params[] = $data['bio'];
    }
    
    if (isset($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, null, 'Invalid email format', 400);
        }
        $updates[] = "email = ?";
        $params[] = $data['email'];
    }
    
    if (isset($data['password']) && !empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            sendResponse(false, null, 'Password must be at least 6 characters', 400);
        }
        $updates[] = "password = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    if (isset($data['profile_picture'])) {
        $updates[] = "profile_picture = ?";
        $params[] = $data['profile_picture'];
    }
    
    if (empty($updates)) {
        sendResponse(false, null, 'No fields to update', 400);
    }
    
    $params[] = $user['id'];
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    sendResponse(true, null, 'Profile updated successfully');
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>