<?php
/**
 * Kirim response JSON ke frontend
 */
function sendResponse($success, $data = null, $message = '', $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_PRETTY_PRINT);
    exit;
}
?>
