<?php
// File: test-connection.php
// Simpan di: C:/xampp/htdocs/movie-review/test-connection.php
// Akses: http://localhost/movie-review/test-connection.php

header("Content-Type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Database Connection</title>
    <style>
        body { 
            font-family: monospace; 
            background: #0a0a0a; 
            color: #fff; 
            padding: 20px; 
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #e50914; }
        .success { 
            background: #155724; 
            color: #d4edda; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        .error { 
            background: #721c24; 
            color: #f8d7da; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #1a1a1a;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            background: #1a1a1a;
        }
        th, td { 
            border: 1px solid #333; 
            padding: 10px; 
            text-align: left; 
        }
        th { background: #333; }
        .test-btn {
            background: #e50914;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .test-btn:hover { background: #f00; }
        pre {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<h1>🔧 Test Database Connection & API</h1>";

// STEP 1: Test Database Connection
echo "<h2>1️⃣ Test Database Connection</h2>";

$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='success'>✅ Database Connected Successfully!</div>";
    
    // STEP 2: Cek Tabel Movies
    echo "<h2>2️⃣ Check Movies Table</h2>";
    $stmt = $pdo->query("SELECT id, title, genre, average_rating FROM movies ORDER BY id LIMIT 10");
    $movies = $stmt->fetchAll();
    
    if (count($movies) > 0) {
        echo "<div class='success'>✅ Found " . count($movies) . " movies in database</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Genre</th><th>Rating</th></tr>";
        foreach ($movies as $movie) {
            echo "<tr>";
            echo "<td>{$movie['id']}</td>";
            echo "<td>{$movie['title']}</td>";
            echo "<td>{$movie['genre']}</td>";
            echo "<td>{$movie['average_rating']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Cek apakah Dilan 1990 ada
        $stmt = $pdo->prepare("SELECT id, title FROM movies WHERE title LIKE '%Dilan%'");
        $stmt->execute();
        $dilan = $stmt->fetch();
        
        if ($dilan) {
            echo "<div class='success'>✅ Film 'Dilan 1990' ditemukan dengan ID: {$dilan['id']}</div>";
            echo "<div class='info'><strong>PENTING:</strong> Update MOVIE_ID di file HTML menjadi: <code>{$dilan['id']}</code></div>";
        } else {
            echo "<div class='error'>❌ Film 'Dilan 1990' tidak ditemukan. Tambahkan dulu via admin panel!</div>";
        }
    } else {
        echo "<div class='error'>❌ No movies found. Add movies via admin panel first!</div>";
    }
    
    // STEP 3: Cek Tabel Reviews
    echo "<h2>3️⃣ Check Reviews Table</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $total = $stmt->fetch()['total'];
    
    echo "<div class='info'>Total Reviews in Database: <strong>$total</strong></div>";
    
    if ($total > 0) {
        $stmt = $pdo->query("
            SELECT r.id, r.rating, r.review_text, m.title as movie_title, u.username, r.created_at
            FROM reviews r
            LEFT JOIN movies m ON r.movie_id = m.id
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        $reviews = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Movie</th><th>User</th><th>Rating</th><th>Review</th><th>Date</th></tr>";
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>{$review['id']}</td>";
            echo "<td>{$review['movie_title']}</td>";
            echo "<td>{$review['username']}</td>";
            echo "<td>{$review['rating']}</td>";
            echo "<td>" . substr($review['review_text'], 0, 50) . "...</td>";
            echo "<td>{$review['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // STEP 4: Test API Files
    echo "<h2>4️⃣ Test API Files</h2>";
    
    $api_files = [
        'api/submit_review.php',
        'api/get_reviews.php'
    ];
    
    foreach ($api_files as $file) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ File exists: $file</div>";
        } else {
            echo "<div class='error'>❌ File NOT FOUND: $file</div>";
        }
    }
    
    // STEP 5: Test API Endpoint
    echo "<h2>5️⃣ Test API Endpoints</h2>";
    
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                "://{$_SERVER['HTTP_HOST']}" . 
                dirname($_SERVER['REQUEST_URI']);
    
    echo "<div class='info'>";
    echo "<p><strong>Base URL:</strong> $base_url</p>";
    echo "<p><strong>API Get Reviews:</strong> <a href='{$base_url}/api/get_reviews.php?movie_id=3' target='_blank' class='test-btn'>Test GET Reviews</a></p>";
    echo "</div>";
    
    // STEP 6: Configuration Summary
    echo "<h2>6️⃣ Configuration for HTML File</h2>";
    echo "<pre>// Update these values in your dilan 1990.html:

const MOVIE_ID = {$dilan['id']}; // Film Dilan 1990
const API_BASE_URL = 'api'; // Relative path
// OR
const API_BASE_URL = '{$base_url}/api'; // Full URL

// Test URLs:
// Get Reviews: {$base_url}/api/get_reviews.php?movie_id={$dilan['id']}
// Submit Review: {$base_url}/api/submit_review.php (POST)
</pre>";
    
    // STEP 7: Quick Actions
    echo "<h2>7️⃣ Quick Actions</h2>";
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='admin.php' class='test-btn'>Open Admin Panel</a> ";
    echo "<a href='dilan 1990.html' class='test-btn'>Open Dilan 1990 Page</a> ";
    echo "<a href='http://localhost/phpmyadmin' class='test-btn' target='_blank'>Open phpMyAdmin</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Connection Failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure XAMPP Apache and MySQL are running</li>";
    echo "<li>Check if database 'movie_review_db' exists in phpMyAdmin</li>";
    echo "<li>Verify database credentials are correct</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align:center; color:#666; margin-top:30px;'>Test completed at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>