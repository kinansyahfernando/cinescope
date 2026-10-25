<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validasi input
$required = ['username', 'email', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        sendResponse(false, null, "Field $field is required", 400);
    }
}

// Validasi email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, null, 'Invalid email format', 400);
}

// Validasi password (minimal 6 karakter)
if (strlen($data['password']) < 6) {
    sendResponse(false, null, 'Password must be at least 6 characters', 400);
}

// Validasi username (alphanumeric dan underscore saja)
if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $data['username'])) {
    sendResponse(false, null, 'Username must be at least 3 characters (letters, numbers, underscore)', 400);
}

try {
    $conn = getDBConnection();
    
    // Cek username atau email sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    
    if ($stmt->fetch()) {
        sendResponse(false, null, 'Username or email already exists', 409);
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user baru
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, full_name) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['username'],
        $data['email'],
        $hashedPassword,
        $data['full_name'] ?? null
    ]);
    
    $userId = $conn->lastInsertId();
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $conn->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expiresAt]);
    
    sendResponse(true, [
        'user' => [
            'id' => $userId,
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'] ?? null
        ],
        'token' => $token
    ], 'Registration successful', 201);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}
?>