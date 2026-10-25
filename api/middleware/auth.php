<?php
require_once '../config/database.php';
require_once '../utils/response.php';

/**
 * Ambil user dari token Authorization
 */
function authenticate() {
    $headers = getallheaders();
    
    if (!isset($headers['Authorization'])) {
        sendResponse(false, null, 'Token tidak ditemukan (Authorization header hilang)', 401);
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') !== 0) {
        sendResponse(false, null, 'Format token salah', 401);
        exit;
    }

    $token = substr($authHeader, 7);

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(false, null, 'Token tidak valid atau pengguna tidak ditemukan', 401);
        exit;
    }

    return $user;
}

/**
 * Pastikan user adalah admin
 */
function requireAdmin($user) {
    if (!isset($user['role']) || strtolower($user['role']) !== 'admin') {
        sendResponse(false, null, 'Hanya admin yang boleh mengakses', 403);
        exit;
    }
}
?>
