<?php
// File: sync_ratings.php
// Simpan di: C:/xampp/htdocs/movie-review/sync_ratings.php
// Akses: http://localhost/movie-review/sync_ratings.php

error_reporting(E_ALL);
ini_set('display_errors', 1);


$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; }
        .success { color: #28a745; font-weight: bold; padding: 15px; background: #d4edda; border-radius: 8px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        .btn { background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .rating-old { color: #dc3545; text-decoration: line-through; }
        .rating-new { color: #28a745; font-weight: bold; }
    </style>";
    
    echo "<div class='container'>";
    echo "<h1>🔄 Sync Rating Frontend → Database (Skala Maksimal 5.0)</h1>";
    
    // Rating dari user.html (sesuai dengan moviesData) - Nilai dibagi 2 (dari skala 10.0 menjadi 5.0)
    // 8.1 / 2 = 4.05
    // 7.9 / 2 = 3.95
    // 8.4 / 2 = 4.20
    $correctRatings = [
        'Sore' => 4.05,        // Sebelumnya 8.1
        'Petaka Gunung Gede' => 3.95,  // Sebelumnya 7.9
        'Dilan 1990' => 4.20,     // Sebelumnya 8.4
        'Rest Area' => 4.05,       // Sebelumnya 8.1
        'Tukar Takdir' => 3.95,    // Sebelumnya 7.9
        'Rangga & Cinta' => 4.20   // Sebelumnya 8.4
    ];
    
    echo "<div class='info'>";
    echo "<strong>📊 Rating yang Akan Di-Update (Skala /5.0):</strong><br>";
    echo "<ul>";
    foreach ($correctRatings as $title => $rating) {
        echo "<li>{$title}: <strong>⭐ {$rating}/5.0</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Update ratings
    $stmt = $pdo->prepare("UPDATE movies SET average_rating = :rating WHERE title = :title");
    
    echo "<h2>🔧 Proses Update...</h2>";
    echo "<table>";
    echo "<tr><th>Film</th><th>Rating Lama</th><th>Rating Baru</th><th>Status</th></tr>";
    
    $updated = 0;
    foreach ($correctRatings as $title => $rating) {
        // Get old rating
        $getStmt = $pdo->prepare("SELECT average_rating FROM movies WHERE title = :title");
        $getStmt->execute([':title' => $title]);
        $oldRating = $getStmt->fetchColumn();
        
        // Update
        $stmt->execute([
            ':rating' => $rating,
            ':title' => $title
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo "<tr>";
            echo "<td><strong>{$title}</strong></td>";
            echo "<td><span class='rating-old'>⭐ " . ($oldRating ?: '0.0') . "</span></td>";
            echo "<td><span class='rating-new'>⭐ {$rating}</span></td>";
            echo "<td>✅ Updated</td>";
            echo "</tr>";
            $updated++;
        }
    }
    
    echo "</table>";
    
    echo "<div class='success'>";
    echo "✅ Berhasil update rating untuk <strong>{$updated} film</strong>!";
    echo "</div>";
    
    // Show current database state
    echo "<h2>📋 Rating Saat Ini di Database (Skala /5.0):</h2>";
    $result = $pdo->query("SELECT id, title, release_year, average_rating FROM movies ORDER BY id DESC");
    $movies = $result->fetchAll();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Judul</th><th>Tahun</th><th>Rating</th></tr>";
    
    foreach ($movies as $movie) {
        // Logika warna disesuaikan untuk skala 5.0 (misalnya, >= 4.0 = hijau)
        $ratingColor = ($movie['average_rating'] >= 4.0) ? '#28a745' : (($movie['average_rating'] >= 3.5) ? '#ffc107' : '#dc3545');
        echo "<tr>";
        echo "<td>{$movie['id']}</td>";
        echo "<td><strong>{$movie['title']}</strong></td>";
        echo "<td>{$movie['release_year']}</td>";
        echo "<td style='color:{$ratingColor}; font-weight:bold;'>⭐ {$movie['average_rating']}/5.0</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='admin.php' class='btn'>⚙️ Kembali ke Admin</a>";
    echo "<a href='index.html' class='btn'>🏠 Lihat Website</a>";
    echo "<a href='get_movies.php' class='btn' target='_blank'>🔍 Cek API</a>";
    echo "</div>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='container'>";
    echo "<h1 style='color:#dc3545;'>❌ Database Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>