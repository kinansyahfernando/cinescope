<?php

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔄 Sinkronisasi Data...</h2>";
    
    // 1. Hapus data lama
    echo "<p>1. Menghapus data lama...</p>";
    $pdo->exec("TRUNCATE TABLE reviews");
    $pdo->exec("DELETE FROM movies WHERE id <= 10");
    
    // 2. Insert film baru
    echo "<p>2. Menambahkan film baru...</p>";
    $movies = [
        ['Rangga & Cinta', 'Rangga & Cinta', 2025, 120, 'Drama, Romantis', 'Unknown', 'Kisah cinta Rangga dan Cinta yang penuh drama'],
        ['Tukar Takdir', 'Tukar Takdir', 2025, 110, 'Drama', 'Unknown', 'Sebuah aplikasi yang bisa menukar nasib hidupmu'],
        ['Rest Area', 'Rest Area', 2025, 95, 'Horor', 'Unknown', 'Malam mencekam di rest area yang terisolasi'],
        ['Dilan 1990', 'Dilan 1990', 2018, 110, 'Documentary, Drama', 'Fajar Bustomi', 'Kisah cinta legendaris di era 90-an']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO movies (title, original_title, release_year, duration, genre, director, synopsis, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    foreach ($movies as $movie) {
        $stmt->execute($movie);
    }
    
    // 3. Insert reviews
    echo "<p>3. Menambahkan sample reviews...</p>";
    $reviews = [
        ['Rangga & Cinta', 8.5, 'Film yang sangat menyentuh hati, ceritanya bagus sekali!'],
        ['Tukar Takdir', 8.0, 'Konsep yang unik dan menarik, sangat recommended!'],
        ['Rest Area', 7.5, 'Film horor yang cukup menegangkan, worth to watch!'],
        ['Dilan 1990', 9.0, 'Masterpiece! Salah satu film romantis terbaik Indonesia']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO reviews (movie_id, user_id, rating, review_text) 
        VALUES ((SELECT id FROM movies WHERE title = ? LIMIT 1), 1, ?, ?)
    ");
    
    foreach ($reviews as $review) {
        $stmt->execute($review);
    }
    
    // 4. Update rating
    echo "<p>4. Mengupdate rating film...</p>";
    $pdo->exec("
        UPDATE movies m SET 
            average_rating = (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.movie_id = m.id),
            total_reviews = (SELECT COUNT(*) FROM reviews r WHERE r.movie_id = m.id)
    ");
    
    echo "<h3 style='color:green;'>✅ SINKRONISASI SELESAI!</h3>";
    echo "<p><a href='admin.php?action=movies'>→ Lihat Kelola Film</a></p>";
    echo "<p><a href='admin.php?action=reviews'>→ Lihat Kelola Reviews</a></p>";
    
} catch(PDOException $e) {
    echo "<h3 style='color:red;'>❌ ERROR: " . $e->getMessage() . "</h3>";
}
?>
```

**Cara pakai:**
```
http://localhost/movie-review/sync_data.php