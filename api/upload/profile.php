<?php
require_once '../middleware/auth.php';

$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Method not allowed', 405);
}

if (!isset($_FILES['profile'])) {
    sendResponse(false, null, 'No file uploaded', 400);
}

$file = $_FILES['profile'];

// Validasi file
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, null, 'Upload error', 400);
}

// Validasi tipe file
$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed)) {
    sendResponse(false, null, 'Invalid file type. Only JPG, PNG, WEBP allowed', 400);
}

// Validasi ukuran (max 2MB untuk profile)
if ($file['size'] > 2 * 1024 * 1024) {
    sendResponse(false, null, 'File too large. Maximum 2MB', 400);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $user['id'] . '_' . time() . '.' . $ext;
$uploadPath = __DIR__ . '/../../uploads/profiles/' . $filename;

// Hapus foto profile lama jika ada
if (!empty($user['profile_picture'])) {
    $oldFile = __DIR__ . '/../../' . $user['profile_picture'];
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Pindahkan file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $url = 'uploads/profiles/' . $filename;
    
    // Update database
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$url, $user['id']]);
        
        sendResponse(true, [
            'filename' => $filename,
            'url' => $url
        ], 'Profile picture uploaded successfully');
    } catch(PDOException $e) {
        sendResponse(false, null, 'Database error', 500);
    }
} else {
    sendResponse(false, null, 'Failed to upload file', 500);
}
?>