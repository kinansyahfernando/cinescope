<?php
// check_database.php - Tool untuk cek database
header("Content-Type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Database Check Tool</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
    h1 { color: #e50914; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #333; padding: 10px; text-align: left; }
    th { background: #333; }
    .success { color: #0f0; }
    .error { color: #f00; }
    .warning { color: #ff0; }
    pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// Test Koneksi
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=movie_review_db;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='success'>✅ Database Connected Successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Connection Failed: " . $e->getMessage() . "</p>";
    exit;
}

// 1. Cek Tabel movies
echo "<h2>📽️ Movies Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE movies");
    $columns = $stmt->fetchAll();
    
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // List movies
    $stmt = $pdo->query("SELECT id, title FROM movies LIMIT 10");
    $movies = $stmt->fetchAll();
    
    echo "<h3>Available Movies:</h3><ul>";
    foreach ($movies as $movie) {
        echo "<li>[ID: {$movie['id']}] {$movie['title']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// 2. Cek Tabel users
echo "<h2>👤 Users Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $count = $stmt->fetch()['total'];
    echo "<p>Total Users: <strong>$count</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// 3. Cek Tabel reviews
echo "<h2>⭐ Reviews Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll();
    
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // List recent reviews
    $stmt = $pdo->query("
        SELECT r.id, r.rating, r.review_text, r.created_at, 
               m.title as movie_title, u.username
        FROM reviews r
        LEFT JOIN movies m ON r.movie_id = m.id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $reviews = $stmt->fetchAll();
    
    echo "<h3>Latest Reviews:</h3>";
    if (count($reviews) > 0) {
        echo "<table><tr><th>ID</th><th>Movie</th><th>User</th><th>Rating</th><th>Review</th><th>Date</th></tr>";
        foreach ($reviews as $rev) {
            echo "<tr>";
            echo "<td>{$rev['id']}</td>";
            echo "<td>{$rev['movie_title']}</td>";
            echo "<td>{$rev['username']}</td>";
            echo "<td>{$rev['rating']}</td>";
            echo "<td>" . substr($rev['review_text'], 0, 50) . "...</td>";
            echo "<td>{$rev['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No reviews found in database</p>";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $count = $stmt->fetch()['total'];
    echo "<p>Total Reviews: <strong>$count</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// 4. Test Insert (Dry Run)
echo "<h2>🧪 Test Insert Review</h2>";
echo "<pre>";
echo "Test Query:\n";
echo "INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at, updated_at)\n";
echo "VALUES (3, 1, 5, 'Test review', NOW(), NOW())\n\n";

try {
    // Cek apakah movie_id 3 ada
    $stmt = $pdo->prepare("SELECT id, title FROM movies WHERE id = 3");
    $stmt->execute();
    $movie = $stmt->fetch();
    
    if ($movie) {
        echo "✅ Movie ID 3 exists: {$movie['title']}\n";
    } else {
        echo "❌ Movie ID 3 NOT FOUND\n";
        echo "Available movie IDs: ";
        $stmt = $pdo->query("SELECT id FROM movies ORDER BY id");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo implode(", ", $ids) . "\n";
    }
    
    // Cek apakah user_id 1 ada
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User ID 1 exists: {$user['username']}\n";
    } else {
        echo "⚠️ User ID 1 NOT FOUND (will be auto-created)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";

// 5. Check Log File
echo "<h2>📋 Recent Logs</h2>";
$logFile = __DIR__ . '/review_log.txt';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recent = array_slice($lines, -20);
    echo "<pre>" . implode("\n", $recent) . "</pre>";
} else {
    echo "<p class='warning'>⚠️ No log file found yet. Logs will be created when first review is submitted.</p>";
}

echo "<hr><p>Done! Check the information above to diagnose database issues.</p>";
?>