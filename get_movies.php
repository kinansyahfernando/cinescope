<?php
// File: get_movies.php
// Simpan di: C:/xampp/htdocs/movie-review/get_movies.php
// File ini akan dipanggil oleh JavaScript di index.html Anda

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database Configuration
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Get all movies
    $query = "SELECT 
                id,
                title,
                original_title,
                release_year,
                duration,
                genre,
                director,
                synopsis,
                poster,
                trailer_url,
                average_rating,
                created_at
              FROM movies 
              ORDER BY id DESC";
    
    $stmt = $pdo->query($query);
    $movies = $stmt->fetchAll();
    
    // Format data untuk frontend
    $formattedMovies = [];
    foreach ($movies as $movie) {
        $formattedMovies[] = [
            'id' => (int)$movie['id'],
            'title' => $movie['title'] ?? 'Untitled',
            'originalTitle' => $movie['original_title'] ?? '',
            'year' => $movie['release_year'] ?? '',
            'duration' => $movie['duration'] ?? '',
            'genre' => $movie['genre'] ?? '',
            'director' => $movie['director'] ?? '',
            'description' => $movie['synopsis'] ?? 'Deskripsi tidak tersedia',
            'synopsis' => $movie['synopsis'] ?? '',
            'poster' => $movie['poster'] ?? '',
            'image' => $movie['poster'] ? 'http://localhost/movie-review/' . $movie['poster'] : '',
            'trailerUrl' => $movie['trailer_url'] ?? '',
            'rating' => $movie['average_rating'] ? number_format($movie['average_rating'], 1) : '0.0',
            'averageRating' => $movie['average_rating'] ?? 0,
            'createdAt' => $movie['created_at'] ?? ''
        ];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Movies retrieved successfully',
        'count' => count($formattedMovies),
        'data' => [
            'movies' => $formattedMovies
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => null
    ], JSON_PRETTY_PRINT);
}
?>