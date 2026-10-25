<?php
// File: check_movie_ids.php
// Simpan di: C:/xampp/htdocs/movie-review/check_movie_ids.php
// Akses: http://localhost/movie-review/check_movie_ids.php
// Tool untuk melihat ID film yang sebenarnya di database

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

// Ambil semua film
$stmt = $pdo->query("SELECT id, title, original_title, release_year, genre FROM movies ORDER BY id ASC");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar ID Film - Movie Review</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:hover {
            background: #f5f7fa;
        }
        .id-badge {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            display: inline-block;
            min-width: 40px;
            text-align: center;
        }
        .code-box {
            background: #f5f7fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            overflow-x: auto;
        }
        .code-box pre {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .back-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #5568d3;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="back-btn">← Kembali ke Admin Panel</a>
        
        <h1>🎬 Daftar ID Film di Database</h1>
        
        <div class="info-box">
            <strong>📌 Info:</strong> Gunakan ID ini untuk mapping di file <code>save_review.php</code>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Judul Film</th>
                    <th>Judul Asli</th>
                    <th>Tahun</th>
                    <th>Genre</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($movies as $movie): ?>
                <tr>
                    <td><span class="id-badge"><?php echo $movie['id']; ?></span></td>
                    <td><strong><?php echo htmlspecialchars($movie['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($movie['original_title'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                    <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="code-box">
            <h3>📝 Copy Mapping Ini ke save_review.php:</h3>
            <pre><code>$movieMapping = [
<?php 
foreach($movies as $movie): 
    $title = $movie['title'];
    $id = $movie['id'];
    echo "    '{$title}' => {$id},\n";
    if (!empty($movie['original_title']) && $movie['original_title'] !== $title) {
        echo "    '{$movie['original_title']}' => {$id},\n";
    }
endforeach; 
?>];</code></pre>
        </div>

        <div class="info-box" style="margin-top: 20px; background: #fff3cd; border-color: #856404;">
            <strong>⚠️ Langkah Selanjutnya:</strong><br>
            1. Copy mapping di atas<br>
            2. Buka file <code>save_review.php</code><br>
            3. Ganti bagian <code>$movieMapping = [...]</code> dengan mapping yang baru<br>
            4. Save file dan test lagi
        </div>
    </div>
</body>
</html>