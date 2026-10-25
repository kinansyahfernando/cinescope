<?php
// File: setup_movies.php
// Simpan di: C:/xampp/htdocs/movie-review/setup_movies.php
// Akses: http://localhost/movie-review/setup_movies.php
// Script ini akan otomatis insert film-film dari user.html ke database

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .movie-item { padding: 10px; border-bottom: 1px solid #eee; }
        .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>";
    
    echo "<div class='container'>";
    echo "<h1>🎬 Setup Film dari User.html</h1>";
    
    // Hapus semua film lama (opsional)
    if (isset($_GET['clear'])) {
        $pdo->exec("TRUNCATE TABLE movies");
        echo "<p class='success'>✅ Semua film lama berhasil dihapus!</p>";
    }
    
    // Data film dari user.html
    $movies = [
        [
            'title' => 'Sore',
            'original_title' => 'Sore',
            'release_year' => 2025,
            'duration' => 120,
            'genre' => 'Drama Romantis, Fantasi Ilmiah',
            'director' => 'TBA',
            'synopsis' => 'Sebuah cerita tentang cinta yang melampaui dimensi. Film ini mengisahkan perjalanan cinta yang tidak terbatas oleh ruang dan waktu.',
            'poster' => 'uploads/posters/sore.jpg',
            'trailer_url' => '',
            'average_rating' => 8.1
        ],
        [
            'title' => 'Petaka Gunung Gede',
            'original_title' => 'Petaka Gunung Gede',
            'release_year' => 2025,
            'duration' => 110,
            'genre' => 'Horor, Petualangan',
            'director' => 'TBA',
            'synopsis' => 'Petualangan menakutkan di gunung yang penuh misteri. Sekelompok pendaki menghadapi teror supernatural di Gunung Gede yang angker.',
            'poster' => 'uploads/posters/petaka-gunung-gede.jpg',
            'trailer_url' => '',
            'average_rating' => 7.9
        ],
        [
            'title' => 'Dilan 1990',
            'original_title' => 'Dilan 1990',
            'release_year' => 2018,
            'duration' => 110,
            'genre' => 'Documentary, Drama',
            'director' => 'Fajar Bustomi, Pidi Baiq',
            'synopsis' => 'Kisah cinta legendaris di era 90-an. Dilan, seorang siswa SMA yang karismatik, jatuh cinta pada Milea yang baru pindah ke Bandung.',
            'poster' => 'uploads/posters/dilan-1990.jpg',
            'trailer_url' => '',
            'average_rating' => 8.4
        ],
        [
            'title' => 'Rest Area',
            'original_title' => 'Rest Area',
            'release_year' => 2025,
            'duration' => 95,
            'genre' => 'Horor',
            'director' => 'TBA',
            'synopsis' => 'Malam mencekam di rest area yang terisolasi. Sebuah keluarga terjebak di rest area tol yang menyimpan rahasia kelam dan teror mengerikan.',
            'poster' => 'uploads/posters/rest-area.jpg',
            'trailer_url' => '',
            'average_rating' => 8.1
        ],
        [
            'title' => 'Tukar Takdir',
            'original_title' => 'Tukar Takdir',
            'release_year' => 2025,
            'duration' => 105,
            'genre' => 'Drama',
            'director' => 'TBA',
            'synopsis' => 'Ketika sebuah aplikasi bisa menukar nasib hidupmu. Film tentang konsekuensi dari keinginan untuk mengubah takdir.',
            'poster' => 'uploads/posters/tukar-takdir.jpg',
            'trailer_url' => '',
            'average_rating' => 7.9
        ],
        [
            'title' => 'Rangga & Cinta',
            'original_title' => 'Rangga & Cinta',
            'release_year' => 2025,
            'duration' => 115,
            'genre' => 'Drama, Romantis',
            'director' => 'TBA',
            'synopsis' => 'Adaptasi dari kisah Rangga dan Cinta. Sebuah kisah cinta yang penuh dengan konflik dan pengorbanan antara dua insan yang saling mencintai.',
            'poster' => 'uploads/posters/rangga-cinta.jpg',
            'trailer_url' => '',
            'average_rating' => 8.4
        ]
    ];
    
    echo "<h2>📥 Menambahkan Film...</h2>";
    
    $sql = "INSERT INTO movies (title, original_title, release_year, duration, genre, director, synopsis, poster, trailer_url, average_rating, created_at, updated_at) 
            VALUES (:title, :original_title, :release_year, :duration, :genre, :director, :synopsis, :poster, :trailer_url, :average_rating, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                original_title = VALUES(original_title),
                release_year = VALUES(release_year),
                duration = VALUES(duration),
                genre = VALUES(genre),
                director = VALUES(director),
                synopsis = VALUES(synopsis),
                average_rating = VALUES(average_rating),
                updated_at = NOW()";
    
    $stmt = $pdo->prepare($sql);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($movies as $movie) {
        try {
            $stmt->execute([
                ':title' => $movie['title'],
                ':original_title' => $movie['original_title'],
                ':release_year' => $movie['release_year'],
                ':duration' => $movie['duration'],
                ':genre' => $movie['genre'],
                ':director' => $movie['director'],
                ':synopsis' => $movie['synopsis'],
                ':poster' => $movie['poster'],
                ':trailer_url' => $movie['trailer_url'],
                ':average_rating' => $movie['average_rating']
            ]);
            
            echo "<div class='movie-item'>✅ <strong>{$movie['title']}</strong> ({$movie['release_year']}) - Berhasil ditambahkan!</div>";
            $successCount++;
            
        } catch (PDOException $e) {
            echo "<div class='movie-item'>❌ <strong>{$movie['title']}</strong> - Error: " . $e->getMessage() . "</div>";
            $errorCount++;
        }
    }
    
    echo "<hr>";
    echo "<h2>📊 Hasil:</h2>";
    echo "<p class='success'>✅ Berhasil: {$successCount} film</p>";
    if ($errorCount > 0) {
        echo "<p class='error'>❌ Gagal: {$errorCount} film</p>";
    }
    
    // Tampilkan semua film di database
    echo "<hr>";
    echo "<h2>🎬 Film di Database:</h2>";
    
    $result = $pdo->query("SELECT id, title, release_year, genre, average_rating FROM movies ORDER BY id ASC");
    $allMovies = $result->fetchAll();
    
    if (count($allMovies) > 0) {
        echo "<table border='1' cellpadding='10' style='width:100%; border-collapse:collapse;'>";
        echo "<tr style='background:#667eea; color:white;'><th>ID</th><th>Judul</th><th>Tahun</th><th>Genre</th><th>Rating</th></tr>";
        
        foreach ($allMovies as $m) {
            echo "<tr>";
            echo "<td>{$m['id']}</td>";
            echo "<td><strong>{$m['title']}</strong></td>";
            echo "<td>{$m['release_year']}</td>";
            echo "<td>{$m['genre']}</td>";
            echo "<td>⭐ {$m['average_rating']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Belum ada film di database.</p>";
    }
    
    echo "<hr>";
    echo "<h2>🔗 Langkah Selanjutnya:</h2>";
    echo "<ol>";
    echo "<li>Upload poster film ke folder: <code>C:/xampp/htdocs/movie-review/uploads/posters/</code></li>";
    echo "<li>Nama file poster harus sesuai: <code>sore.jpg</code>, <code>petaka-gunung-gede.jpg</code>, dll</li>";
    echo "<li>Atau upload via Admin Panel: <a href='admin.php?action=edit&id=1'>Edit Film</a></li>";
    echo "</ol>";
    
    echo "<div style='margin-top:30px;'>";
    echo "<a href='index.html' class='btn'>📺 Lihat Website</a> ";
    echo "<a href='admin.php' class='btn'>⚙️ Admin Panel</a> ";
    echo "<a href='setup_movies.php?clear=1' class='btn' style='background:#dc3545;' onclick='return confirm(\"Yakin hapus semua film?\")'>🗑️ Hapus Semua & Reset</a>";
    echo "</div>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='container'>";
    echo "<h1 class='error'>❌ Database Connection Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Pastikan MySQL di XAMPP sudah running dan database <strong>movie_review_db</strong> sudah ada.</p>";
    echo "</div>";
}
?>