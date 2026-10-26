<?php
// File: test_rating.php
// Script untuk testing dan debugging rating system

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully<br><br>";
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineScope - Rating System Test</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0a0a0a;
            color: #00ff00;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #e50914;
            border-bottom: 2px solid #e50914;
            padding-bottom: 10px;
        }
        h2 {
            color: #ff6b6b;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #1a1a1a;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #333;
        }
        th {
            background: #e50914;
            color: white;
        }
        tr:nth-child(even) {
            background: #151515;
        }
        .success {
            color: #4CAF50;
        }
        .warning {
            color: #ff9800;
        }
        .error {
            color: #f44336;
        }
        .info {
            background: #1a1a1a;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            color: #ccc;
        }
        .query-box {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 15px;
            margin: 10px 0;
            overflow-x: auto;
        }
        .query-box pre {
            margin: 0;
            color: #00ff00;
        }
    </style>
</head>
<body>

<h1>🎬 CineScope - Rating System Test & Debug</h1>

<div class="info">
    <strong>📌 Informasi:</strong><br>
    Script ini membantu Anda mengecek apakah rating system sudah bekerja dengan benar.<br>
    Jalankan di browser: <code>http://localhost/test_rating.php</code>
</div>

<h2>📊 1. Data Review Per Film (ID 1-6)</h2>
<?php
$stmt = $pdo->query("
    SELECT 
        m.id,
        m.title,
        COUNT(r.id) as review_count,
        COALESCE(AVG(r.rating), 0) as avg_rating
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.id <= 6
    GROUP BY m.id
    ORDER BY m.id
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Judul Film</th>
            <th>Jumlah Review</th>
            <th>Rata-rata Rating</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($movies as $movie): ?>
        <tr>
            <td><?= $movie['id'] ?></td>
            <td><?= htmlspecialchars($movie['title']) ?></td>
            <td><?= $movie['review_count'] ?></td>
            <td><?= number_format($movie['avg_rating'], 1) ?>/5</td>
            <td>
                <?php if ($movie['review_count'] > 0): ?>
                    <span class="success">✅ Ada review</span>
                <?php else: ?>
                    <span class="warning">⚠️ Belum ada review</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>🔥 2. Top 3 Trending Movies</h2>
<?php
$trendingStmt = $pdo->query("
    SELECT m.*, 
           COALESCE(AVG(r.rating), 0) as avg_rating, 
           COUNT(r.id) as review_count
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.id <= 6
    GROUP BY m.id
    HAVING review_count > 0
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 3
");
$trending = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="query-box">
    <strong>Query yang digunakan:</strong>
    <pre>SELECT m.*, 
       COALESCE(AVG(r.rating), 0) as avg_rating, 
       COUNT(r.id) as review_count
FROM movies m
LEFT JOIN reviews r ON m.id = r.movie_id
WHERE m.id <= 6
GROUP BY m.id
HAVING review_count > 0
ORDER BY avg_rating DESC, review_count DESC
LIMIT 3</pre>
</div>

<table>
    <thead>
        <tr>
            <th>Ranking</th>
            <th>Judul Film</th>
            <th>Rating</th>
            <th>Jumlah Review</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($trending) > 0): ?>
            <?php foreach ($trending as $index => $movie): ?>
            <tr>
                <td><strong>🔥 Top <?= $index + 1 ?></strong></td>
                <td><?= htmlspecialchars($movie['title']) ?></td>
                <td><?= number_format($movie['avg_rating'], 1) ?>/5</td>
                <td><?= $movie['review_count'] ?> ulasan</td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="warning">⚠️ Belum ada film trending (belum ada review)</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>📝 3. Detail Review untuk Setiap Film</h2>
<?php
foreach ($movies as $movie):
    $reviewStmt = $pdo->prepare("
        SELECT username, rating, comment, created_at
        FROM reviews
        WHERE movie_id = ?
        ORDER BY created_at DESC
    ");
    $reviewStmt->execute([$movie['id']]);
    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <h3><?= htmlspecialchars($movie['title']) ?> (ID: <?= $movie['id'] ?>)</h3>
    
    <?php if (count($reviews) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?= htmlspecialchars($review['username']) ?></td>
                    <td><?= $review['rating'] ?>/5</td>
                    <td><?= htmlspecialchars($review['comment']) ?></td>
                    <td><?= $review['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="info">Belum ada review untuk film ini.</div>
    <?php endif; ?>
<?php endforeach; ?>

<h2>🔧 4. Cek Trigger MySQL</h2>
<?php
$triggerStmt = $pdo->query("SHOW TRIGGERS WHERE `Table` = 'reviews'");
$triggers = $triggerStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (count($triggers) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Trigger Name</th>
                <th>Event</th>
                <th>Timing</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($triggers as $trigger): ?>
            <tr>
                <td><?= $trigger['Trigger'] ?></td>
                <td><?= $trigger['Event'] ?></td>
                <td><?= $trigger['Timing'] ?></td>
                <td><span class="success">✅ Active</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php
    $expectedTriggers = [
        'update_movie_rating_after_insert',
        'update_movie_rating_after_update',
        'update_movie_rating_after_delete'
    ];
    $foundTriggers = array_column($triggers, 'Trigger');
    $missingTriggers = array_diff($expectedTriggers, $foundTriggers);
    
    if (empty($missingTriggers)):
    ?>
        <div class="info success">✅ Semua trigger sudah terpasang dengan benar!</div>
    <?php else: ?>
        <div class="info error">
            ❌ Trigger yang hilang: <?= implode(', ', $missingTriggers) ?><br>
            Jalankan script database_fix.sql untuk menginstall trigger.
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="info error">
        ❌ Tidak ada trigger terpasang!<br>
        Jalankan script database_fix.sql untuk menginstall trigger.
    </div>
<?php endif; ?>

<h2>🧪 5. Test Konsistensi Data</h2>
<?php
$consistencyStmt = $pdo->query("
    SELECT 
        m.id,
        m.title,
        m.avg_rating as stored_rating,
        m.review_count as stored_count,
        COALESCE(AVG(r.rating), 0) as actual_rating,
        COUNT(r.id) as actual_count,
        CASE 
            WHEN ABS(m.avg_rating - COALESCE(AVG(r.rating), 0)) < 0.1 
            AND m.review_count = COUNT(r.id) 
            THEN 'OK' 
            ELSE 'INCONSISTENT' 
        END as status
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.id <= 6
    GROUP BY m.id
");
$consistency = $consistencyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Film</th>
            <th>Stored Rating</th>
            <th>Actual Rating</th>
            <th>Stored Count</th>
            <th>Actual Count</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($consistency as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= number_format($row['stored_rating'], 1) ?></td>
            <td><?= number_format($row['actual_rating'], 1) ?></td>
            <td><?= $row['stored_count'] ?></td>
            <td><?= $row['actual_count'] ?></td>
            <td>
                <?php if ($row['status'] === 'OK'): ?>
                    <span class="success">✅ Konsisten</span>
                <?php else: ?>
                    <span class="error">❌ Tidak Konsisten</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$inconsistentCount = count(array_filter($consistency, function($row) {
    return $row['status'] === 'INCONSISTENT';
}));

if ($inconsistentCount > 0):
?>
    <div class="info error">
        <strong>❌ Ada <?= $inconsistentCount ?> film dengan data tidak konsisten!</strong><br>
        Solusi: Jalankan query berikut di MySQL:
        <div class="query-box">
            <pre>UPDATE movies m
LEFT JOIN (
    SELECT movie_id, AVG(rating) as avg_rating, COUNT(*) as review_count
    FROM reviews
    GROUP BY movie_id
) r ON m.id = r.movie_id
SET 
    m.avg_rating = COALESCE(r.avg_rating, 0),
    m.review_count = COALESCE(r.review_count, 0);</pre>
        </div>
    </div>
<?php else: ?>
    <div class="info success">
        <strong>✅ Semua data konsisten!</strong><br>
        Rating dan review count sudah sinkron antara tabel movies dan reviews.
    </div>
<?php endif; ?>

<h2>📋 6. Summary & Recommendations</h2>
<div class="info">
    <strong>Status Sistem:</strong><br>
    <ul>
        <li>Total Film (ID 1-6): <strong><?= count($movies) ?></strong></li>
        <li>Film dengan Review: <strong><?= count(array_filter($movies, function($m) { return $m['review_count'] > 0; })) ?></strong></li>
        <li>Trending Films: <strong><?= count($trending) ?></strong></li>
        <li>Trigger Installed: <strong><?= count($triggers) ?>/3</strong></li>
        <li>Data Consistency: <strong><?= (count($movies) - $inconsistentCount) ?>/<?= count($movies) ?> OK</strong></li>
    </ul>
    
    <?php if ($inconsistentCount === 0 && count($triggers) === 3): ?>
        <p class="success"><strong>🎉 Sistem rating berfungsi dengan baik!</strong></p>
    <?php else: ?>
        <p class="warning"><strong>⚠️ Ada beberapa masalah yang perlu diperbaiki. Lihat detail di atas.</strong></p>
    <?php endif; ?>
</div>

<div class="info">
    <strong>🔗 Quick Actions:</strong><br>
    <a href="index.php" style="color: #e50914;">➜ Buka index.php</a> |
    <a href="user.php" style="color: #e50914;">➜ Buka user.php</a> |
    <a href="test_rating.php" style="color: #e50914;">➜ Refresh Test</a>
</div>

<br><br>
<div style="text-align: center; color: #666; border-top: 1px solid #333; padding-top: 20px;">
    <p>CineScope Rating System Test v2.0 | Last Run: <?= date('Y-m-d H:i:s') ?></p>
</div>

</body>
</html>