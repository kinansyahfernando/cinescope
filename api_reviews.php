<?php
// api_reviews.php - API untuk mengelola review film
header('Content-Type: application/json');
require_once 'config.php';

$conn = getDBConnection();

// GET: Ambil semua review untuk film tertentu
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
    
    if ($movie_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
        exit;
    }
    
    // Ambil semua review untuk film ini
    $stmt = $conn->prepare("SELECT reviewer_name, rating, review_text, DATE_FORMAT(review_date, '%d %b %Y') as review_date 
                            FROM user_reviews 
                            WHERE movie_id = ? 
                            ORDER BY id DESC");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    // Hitung jumlah dan rata-rata rating
    $count = count($reviews);
    $average = $count > 0 ? calculateAverageRating($conn, $movie_id) : 0;
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'count' => $count,
        'average' => $average
    ]);
    
    $stmt->close();
}

// POST: Tambah review baru
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_text = isset($_POST['review_text']) ? sanitize_input($_POST['review_text']) : '';
    
    // Validasi input
    if ($movie_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating harus antara 1-5']);
        exit;
    }
    
    // Cek apakah film ada
    $check_stmt = $conn->prepare("SELECT id FROM movies WHERE id = ?");
    $check_stmt->bind_param("i", $movie_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Film tidak ditemukan']);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
    
    // Insert review baru
    $reviewer_name = "Anda (Pengguna)"; // Bisa diganti dengan session user jika ada login
    $review_date = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO user_reviews (movie_id, reviewer_name, rating, review_text, review_date) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $movie_id, $reviewer_name, $rating, $review_text, $review_date);
    
    if ($stmt->execute()) {
        // Update rata-rata rating di tabel movies (opsional)
        $new_average = calculateAverageRating($conn, $movie_id);
        $update_stmt = $conn->prepare("UPDATE movies SET rating = ? WHERE id = ?");
        $update_stmt->bind_param("di", $new_average, $movie_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Review berhasil ditambahkan',
            'new_average' => $new_average
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan review: ' . $conn->error]);
    }
    
    $stmt->close();
}

// Method tidak dikenali
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or action']);
}

$conn->close();
?>