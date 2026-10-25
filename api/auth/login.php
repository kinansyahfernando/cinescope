<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['username']) || empty($data['password'])) {
    sendResponse(false, null, 'Username and password are required', 400);
}

try {
    $conn = getDBConnection();
    
    // Cari user (bisa pakai username atau email)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['username']]);
    $user = $stmt->fetch();
    
    // Verifikasi password
    if (!$user || !password_verify($data['password'], $user['password'])) {
        sendResponse(false, null, 'Invalid credentials', 401);
    }
    
    // Hapus session lama yang expired
    $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ? AND expires_at < NOW()");
    $stmt->execute([$user['id']]);
    
    // Generate token baru
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $conn->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expiresAt]);
    
    // Hapus password dari response
    unset($user['password']);
    
    sendResponse(true, [
        'user' => $user,
        'token' => $token
    ], 'Login successful');
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>