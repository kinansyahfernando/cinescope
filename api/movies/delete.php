<?php
require_once '../middleware/auth.php';

$user = authenticate();
requireAdmin($user);

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendResponse(false, null, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    sendResponse(false, null, 'Movie ID is required',