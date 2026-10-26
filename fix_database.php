<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database - CineScope</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2.2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        pre {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            overflow-x: auto;
            line-height: 1.6;
            font-size: 0.95em;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
        }
        .step {
            background: #e7f3ff;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #2196F3;
        }
        .step strong {
            color: #2196F3;
            font-size: 1.1em;
        }
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            display: block;
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            box-shadow: 0 4px 15px rgba(56, 239, 125, 0.4);
        }
        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(56, 239, 125, 0.6);
        }
        .mapping-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #e0e0e0;
        }
        .mapping-box h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .check-item {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .icon-success {
            color: #28a745;
            font-size: 1.2em;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Auto-Fix Tool</h1>
        <p class="subtitle">Memperbaiki struktur database dan ID film secara otomatis</p>
        
        <?php
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<div class="step">';
            echo '<strong>📊 Status: Memproses...</strong>';
            echo '</div>';
            
            echo '<pre>';
            
            // 1. Backup data
            echo "1️⃣ Backup data existing...\n";
            $pdo->exec("DROP TABLE IF EXISTS movies_backup");
            $pdo->exec("DROP TABLE IF EXISTS reviews_backup");
            $pdo->exec("CREATE TABLE movies_backup AS SELECT * FROM movies");
            $pdo->exec("CREATE TABLE reviews_backup AS SELECT * FROM reviews");
            echo "   <span style='color:green;'>✅ Backup selesai</span>\n\n";
            
            // 2. Hapus semua data
            echo "2️⃣ Menghapus data lama...\n";
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE review_helpful");
            $pdo->exec("TRUNCATE TABLE reviews");
            $pdo->exec("TRUNCATE TABLE watchlist");
            $pdo->exec("TRUNCATE TABLE movies");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "   <span style='color:green;'>✅ Data lama dihapus</span>\n\n";
            
            // 3. Insert film dengan ID yang benar
            echo "3️⃣ Menambahkan film baru dengan ID yang benar...\n";
            $movies = [
                [1, 'Sore', 'Sore', 2025, 120, 'Drama Romantis-Fantasi Ilmiah', 'Unknown', 'Cerita cinta yang melampaui dimensi'],
                [2, 'Petaka Gunung Gede', 'Petaka Gunung Gede', 2025, 110, 'Horor-Petualangan', 'Unknown', 'Petualangan menakutkan di gunung misteri'],
                [3, 'Dilan 1990', 'Dilan 1990', 2018, 110, 'Documentary, Drama', 'Fajar Bustomi', 'Kisah cinta legendaris di era 90-an'],
                [4, 'Rest Area', 'Rest Area', 2025, 95, 'Horor', 'Unknown', 'Malam mencekam di rest area terisolasi'],
                [5, 'Tukar Takdir', 'Tukar Takdir', 2025, 110, 'Drama', 'Unknown', 'Aplikasi yang bisa menukar nasib'],
                [6, 'Rangga & Cinta', 'Rangga & Cinta', 2025, 120, 'Drama, Romantis', 'Unknown', 'Kisah cinta Rangga dan Cinta']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO movies (id, title, original_title, release_year, duration, genre, director, synopsis, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            foreach ($movies as $movie) {
                $stmt->execute($movie);
                echo "   <span style='color:green;'>✅</span> ID {$movie[0]}: {$movie[1]}\n";
            }
            
            // 4. Insert sample reviews
            echo "\n4️⃣ Menambahkan sample reviews...\n";
            $reviews = [
                [1, 9.0, 'Film yang sangat bagus! Ceritanya menyentuh hati.'],
                [2, 7.5, 'Cukup menegangkan, cocok untuk pecinta horor.'],
                [3, 9.5, 'Masterpiece! Dilan memang legenda.'],
                [4, 7.0, 'Lumayan seram, tapi bisa lebih baik.'],
                [5, 8.5, 'Konsep cerita yang unik dan menarik!'],
                [6, 8.0, 'Romantis banget, bikin baper!']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at) 
                VALUES (?, 1, ?, ?, NOW())
            ");
            
            foreach ($reviews as $review) {
                $stmt->execute($review);
            }
            echo "   <span style='color:green;'>✅ {count($reviews)} sample reviews ditambahkan</span>\n\n";
            
            // 5. Update rating
            echo "5️⃣ Update rating film...\n";
            $pdo->exec("
                UPDATE movies m SET 
                    average_rating = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE movie_id = m.id),
                    total_reviews = (SELECT COUNT(*) FROM reviews WHERE movie_id = m.id)
            ");
            echo "   <span style='color:green;'>✅ Rating berhasil diupdate</span>\n\n";
            
            // 6. Reset auto increment
            echo "6️⃣ Reset auto increment...\n";
            $pdo->exec("ALTER TABLE movies AUTO_INCREMENT = 7");
            $pdo->exec("ALTER TABLE reviews AUTO_INCREMENT = 7");
            echo "   <span style='color:green;'>✅ Auto increment direset</span>\n\n";
            
            echo "</pre>";
            
            echo '<div class="success">';
            echo '<h3 style="margin-bottom:10px;">✅ DATABASE BERHASIL DIPERBAIKI!</h3>';
            echo '<p>Semua film sudah memiliki ID yang benar dan review sample sudah ditambahkan.</p>';
            echo '</div>';
            
            // Mapping info
            echo '<div class="mapping-box">';
            echo '<h3>📋 Movie ID Mapping untuk Auto-Sync:</h3>';
            echo '<div class="check-item"><span class="icon-success">✓</span> ID 1 = Sore</div>';
            echo '<div class="check-item"><span class="icon-success">✓</span> ID 2 = Petaka Gunung Gede</div>';
            echo '<div class="check-item"><span class="icon-success">✓</span> ID 3 = Dilan 1990</div>';
            echo '<div class="check-item"><span class="icon-success">✓</span> ID 4 = Rest Area</div>';
            echo '<div class="check-item"><span class="icon-success">✓</span> ID 5 = Tukar Takdir</div>';
            echo '<div class="check-item"><span class="icon-success">✓</span> ID 6 = Rangga & Cinta</div>';
            echo '</div>';
            
            echo '<div class="step">';
            echo '<strong>📝 Langkah Selanjutnya:</strong><br>';
            echo '1. Clear localStorage browser (buka user.html → F12 → Console → ketik: <code>localStorage.clear()</code>)<br>';
            echo '2. Refresh halaman user.html<br>';
            echo '3. Tulis review di film mana saja<br>';
            echo '4. Review otomatis masuk ke database!';
            echo '</div>';
            
        } catch(PDOException $e) {
            echo '<div class="error">';
            echo '<h3>❌ ERROR</h3>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        ?>
        
        <div class="links">
            <a href="admin.php?action=movies" class="btn">
                🎬 Kelola Film
            </a>
            <a href="admin.php?action=reviews" class="btn">
                ⭐ Kelola Reviews
            </a>
            <a href="user.html" class="btn btn-secondary">
                🌐 Website User
            </a>
        </div>
    </div>
</body>
</html>
```

---

## **STEP 2: Jalankan File PHP**

1. **Simpan file** `fix_database.php` di `C:\xampp\htdocs\movie-review\`

2. **Pastikan Apache & MySQL running** di XAMPP

3. **Buka browser**, ketik:
```
   http://localhost/movie-review/fix_database.php