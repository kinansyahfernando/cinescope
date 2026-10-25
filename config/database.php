<?php
/**
 * Test Database Connection
 * File untuk testing apakah koneksi database berhasil
 */

// Include config
require_once 'config/database.php';
// Set HTML response
header("Content-Type: text/html; charset=utf-8");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 3px solid #e50914; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .info-box { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Test</h1>
        
        <?php
        echo "<div class='info-box'>";
        echo "<strong>Database Config:</strong><br>";
        echo "Host: " . DB_HOST . "<br>";
        echo "Database: " . DB_NAME . "<br>";
        echo "User: " . DB_USER . "<br>";
        echo "Charset: " . DB_CHARSET;
        echo "</div>";
        
        // Test 1: Database Connection
        echo "<h2>Test 1: Koneksi Database</h2>";
        try {
            $pdo = getDBConnection();
            echo "<p class='success'>✅ Koneksi database BERHASIL!</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Koneksi database GAGAL!</p>";
            echo "<pre>" . $e->getMessage() . "</pre>";
            exit;
        }
        
        // Test 2: Cek Tabel Movies
        echo "<h2>Test 2: Daftar Film di Database</h2>";
        try {
            $stmt = $pdo->query("SELECT id, title, release_year, genre FROM movies ORDER BY title LIMIT 10");
            $movies = $stmt->fetchAll();
            
            if (count($movies) > 0) {
                echo "<p class='success'>✅ Ditemukan " . count($movies) . " film</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Judul</th><th>Tahun</th><th>Genre</th></tr>";
                foreach ($movies as $movie) {
                    echo "<tr>";
                    echo "<td>{$movie['id']}</td>";
                    echo "<td>{$movie['title']}</td>";
                    echo "<td>{$movie['release_year']}</td>";
                    echo "<td>{$movie['genre']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>⚠️ Tidak ada film di database</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        // Test 3: Cek Film "Dilan 1990"
        echo "<h2>Test 3: Cek Film 'Dilan 1990'</h2>";
        try {
            $stmt = $pdo->prepare("SELECT * FROM movies WHERE title = ? LIMIT 1");
            $stmt->execute(array("Dilan 1990"));
            $dilan = $stmt->fetch();
            
            if ($dilan) {
                echo "<p class='success'>✅ Film 'Dilan 1990' DITEMUKAN!</p>";
                echo "<pre>";
                print_r($dilan);
                echo "</pre>";
            } else {
                echo "<p class='error'>❌ Film 'Dilan 1990' TIDAK DITEMUKAN!</p>";
                echo "<p>Coba cari dengan nama lain:</p>";
                
                $stmt = $pdo->query("SELECT id, title FROM movies WHERE title LIKE '%Dilan%' OR title LIKE '%1990%'");
                $similar = $stmt->fetchAll();
                
                if (count($similar) > 0) {
                    echo "<ul>";
                    foreach ($similar as $movie) {
                        echo "<li>ID: {$movie['id']} - {$movie['title']}</li>";
                    }
                    echo "</ul>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        // Test 4: Struktur Tabel Reviews
        echo "<h2>Test 4: Struktur Tabel Reviews</h2>";
        try {
            $stmt = $pdo->query("DESCRIBE reviews");
            $columns = $stmt->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        // Test 5: Total Reviews
        echo "<h2>Test 5: Total Reviews di Database</h2>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
            $total = $stmt->fetchColumn();
            
            echo "<p class='success'>✅ Total reviews: <strong>$total</strong></p>";
            
            if ($total > 0) {
                $stmt = $pdo->query("
                    SELECT 
                        m.title,
                        COUNT(r.id) as review_count,
                        ROUND(AVG(r.rating), 1) as avg_rating
                    FROM reviews r
                    JOIN movies m ON r.movie_id = m.id
                    GROUP BY m.id, m.title
                    ORDER BY review_count DESC
                    LIMIT 5
                ");
                $stats = $stmt->fetchAll();
                
                echo "<p>Film dengan review terbanyak:</p>";
                echo "<table>";
                echo "<tr><th>Film</th><th>Jumlah Review</th><th>Rating Rata-rata</th></tr>";
                foreach ($stats as $stat) {
                    echo "<tr>";
                    echo "<td>{$stat['title']}</td>";
                    echo "<td>{$stat['review_count']}</td>";
                    echo "<td>{$stat['avg_rating']}/5</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        // Test 6: Test Insert Dummy Review
        echo "<h2>Test 6: Test Insert Review (Optional)</h2>";
        echo "<p><em>Tidak dijalankan otomatis. Untuk test manual, uncomment code di test_connection.php</em></p>";
        
        /*
        // UNCOMMENT UNTUK TEST INSERT
        try {
            // Ambil movie_id Dilan 1990
            $stmt = $pdo->prepare("SELECT id FROM movies WHERE title = ?");
            $stmt->execute(array("Dilan 1990"));
            $movie_id = $stmt->fetchColumn();
            
            // Ambil atau buat user test
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute(array("test_user"));
            $user_id = $stmt->fetchColumn();
            
            if (!$user_id) {
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute(array("test_user", "Test User", "test@test.com"));
                $user_id = $pdo->lastInsertId();
            }
            
            // Insert dummy review
            $stmt = $pdo->prepare("
                INSERT INTO reviews (movie_id, user_id, rating, review_text, is_spoiler, helpful_count, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, 0, NOW(), NOW())
            ");
            $stmt->execute(array($movie_id, $user_id, 5.0, "Test review dari test_connection.php"));
            
            echo "<p class='success'>✅ Dummy review berhasil diinsert!</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        */
        
        ?>
        
        <hr>
        <h2>✅ Kesimpulan</h2>
        <p>Jika semua test di atas berhasil (hijau ✅), maka:</p>
        <ul>
            <li>Database connection sudah OK</li>
            <li>Tabel movies dan reviews sudah ada</li>
            <li>Siap untuk menerima review dari aplikasi</li>
        </ul>
        
        <p><strong>Next Step:</strong> Akses halaman <code>dilan 1990.html</code> dan coba submit review!</p>
    </div>
</body>
</html>