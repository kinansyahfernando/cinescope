<?php
// require_once '../middleware/auth.php';
// $user = authenticate();
// requireAdmin($user);

$user = ['id' => 1, 'role' => 'admin']; // dummy admin agar bisa upload tanpa login


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Method not allowed', 405);
}

try {
    $conn = getDBConnection();

    // Ambil data dari form (bukan JSON lagi)
    $title = $_POST['title'] ?? null;
    $original_title = $_POST['original_title'] ?? null;
    $release_year = $_POST['release_year'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $genre = $_POST['genre'] ?? null;
    $director = $_POST['director'] ?? null;
    $cast = $_POST['cast'] ?? null;
    $synopsis = $_POST['synopsis'] ?? null;
    $trailer_url = $_POST['trailer_url'] ?? null;
    $posterName = null;

    // ✅ Upload poster kalau ada
    if (!empty($_FILES['poster']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $posterName = time() . '_' . basename($_FILES['poster']['name']);
        $targetPath = $uploadDir . $posterName;

        if (!move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
            sendResponse(false, null, 'Failed to upload poster', 500);
        }
    }

    // Validasi wajib
    if (empty($title) || empty($release_year)) {
        sendResponse(false, null, 'Title and release year are required', 400);
    }

    // Simpan ke database
    $stmt = $conn->prepare("
        INSERT INTO movies (title, original_title, release_year, duration, genre, 
                           director, cast, synopsis, poster, trailer_url, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $original_title,
        $release_year,
        $duration,
        $genre,
        $director,
        $cast ? json_encode(explode(',', $cast)) : null,
        $synopsis,
        $posterName,
        $trailer_url,
        $user['id']
    ]);

    sendResponse(true, ['id' => $conn->lastInsertId()], 'Movie uploaded successfully', 201);

} catch(PDOException $e) {
    sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}
?>
