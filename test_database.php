<?php
// File: test_database.php
// Letakkan file ini di folder yang sama dengan admin.php
// Akses via: http://localhost/test_database.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>🔍 Database Diagnostic Tool</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #fff; padding: 20px; }
        .success { background: #27ae60; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { background: #e74c3c; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { background: #3498db; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #f39c12; padding: 10px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #2d2d2d; }
        th, td { padding: 12px; text-align: left; border: 1px solid #444; }
        th { background: #3498db; }
        .test-section { margin: 30px 0; padding: 20px; background: #2d2d2d; border-radius: 8px; }
        h2 { color: #3498db; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        pre { background: #0a0a0a; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🔍 Database Diagnostic Tool - CineScope</h1>";

// =============================================
// TEST 1: Koneksi Database
// =============================================
echo "<div class='test-section'>";
echo "<h2>TEST 1: Koneksi Database</h2>";

$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Koneksi database BERHASIL!</div>";
    echo "<div class='info'>";
    echo "Host: $host<br>";
    echo "Database: $dbname<br>";
    echo "User: $username<br>";
    echo "Charset: utf8mb4";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ GAGAL koneksi database!</div>";
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>⚠️ Pastikan XAMPP MySQL sudah running!</div>";
    die();
}
echo "</div>";

// =============================================
// TEST 2: Cek Struktur Tabel Reviews
// =============================================
echo "<div class='test-section'>";
echo "<h2>TEST 2: Struktur Tabel Reviews</h2>";

try {
    $stmt = $pdo->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✅ Tabel 'reviews' ditemukan!</div>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Cek kolom yang wajib ada
    $requiredColumns = ['id', 'movie_id', 'user_id', 'rating', 'review_text', 'user_name', 'created_at', 'updated_at'];
    $existingColumns = array_column($columns, 'Field');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if(count($missingColumns) > 0) {
        echo "<div class='error'>❌ KOLOM TIDAK LENGKAP! Missing: " . implode(', ', $missingColumns) . "</div>";
        echo "<div class='warning'>⚠️ Jalankan SQL fix di phpMyAdmin!</div>";
    } else {
        echo "<div class='success'>✅ Semua kolom yang diperlukan ada!</div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Tabel 'reviews' tidak ditemukan!</div>";
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>⚠️ Buat tabel reviews terlebih dahulu!</div>";
}
echo "</div>";

// =============================================
// TEST 3: Cek Data Reviews yang Ada
// =============================================
echo "<div class='test-section'>";
echo "<h2>TEST 3: Data Reviews Saat Ini</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>📊 Total Reviews di Database: <strong>{$count['total']}</strong></div>";
    
    if($count['total'] > 0) {
        $stmt = $pdo->query("SELECT r.*, m.title as movie_title, u.username 
                             FROM reviews r 
                             LEFT JOIN movies m ON r.movie_id = m.id
                             LEFT JOIN users u ON r.user_id = u.id
                             ORDER BY r.created_at DESC 
                             LIMIT 10");
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Movie</th><th>User</th><th>Rating</th><th>Review</th><th>Created</th></tr>";
        foreach($reviews as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['movie_title']}</td>";
            echo "<td>{$r['username']}</td>";
            echo "<td>{$r['rating']}</td>";
            echo "<td>" . substr($r['review_text'], 0, 50) . "...</td>";
            echo "<td>{$r['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Belum ada data reviews di database</div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Error query: " . $e->getMessage() . "</div>";
}
echo "</div>";

// =============================================
// TEST 4: Test INSERT Manual
// =============================================
echo "<div class='test-section'>";
echo "<h2>TEST 4: Test INSERT Review (Manual)</h2>";

try {
    // Cek apakah ada user dan movie
    $userCheck = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch();
    $movieCheck = $pdo->query("SELECT COUNT(*) as total FROM movies")->fetch();
    
    if($userCheck['total'] == 0) {
        echo "<div class='error'>❌ Tidak ada user di database! Insert user dulu.</div>";
    } elseif($movieCheck['total'] == 0) {
        echo "<div class='error'>❌ Tidak ada movie di database! Insert movie dulu.</div>";
    } else {
        echo "<div class='info'>📌 Mencoba INSERT review test...</div>";
        
        // Ambil user dan movie pertama
        $user = $pdo->query("SELECT id, username FROM users LIMIT 1")->fetch();
        $movie = $pdo->query("SELECT id, title FROM movies LIMIT 1")->fetch();
        
        echo "<div class='info'>User: {$user['username']} (ID: {$user['id']})</div>";
        echo "<div class='info'>Movie: {$movie['title']} (ID: {$movie['id']})</div>";
        
        // Cek apakah autocommit aktif
        $autocommit = $pdo->query("SELECT @@autocommit")->fetch();
        echo "<div class='info'>Autocommit Status: " . ($autocommit[0] ? '✅ ON (bagus!)' : '❌ OFF (masalah!)') . "</div>";
        
        // INSERT test
        $testSql = "INSERT INTO reviews (movie_id, user_id, rating, review_text, user_name, created_at, updated_at) 
                    VALUES (:movie_id, :user_id, :rating, :review_text, :user_name, NOW(), NOW())";
        
        $stmt = $pdo->prepare($testSql);
        $result = $stmt->execute([
            ':movie_id' => $movie['id'],
            ':user_id' => $user['id'],
            ':rating' => 5,
            ':review_text' => 'Test review dari diagnostic tool - ' . date('Y-m-d H:i:s'),
            ':user_name' => $user['username']
        ]);
        
        if($result) {
            $insertId = $pdo->lastInsertId();
            echo "<div class='success'>✅ INSERT BERHASIL! Review ID: $insertId</div>";
            
            // Verifikasi data masuk
            $verify = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
            $verify->execute([$insertId]);
            $newReview = $verify->fetch(PDO::FETCH_ASSOC);
            
            if($newReview) {
                echo "<div class='success'>✅ VERIFIKASI: Data berhasil tersimpan di database!</div>";
                echo "<pre>" . print_r($newReview, true) . "</pre>";
            } else {
                echo "<div class='error'>❌ VERIFIKASI GAGAL: Data tidak ditemukan setelah INSERT!</div>";
                echo "<div class='warning'>⚠️ Kemungkinan masalah AUTOCOMMIT atau TRANSACTION!</div>";
            }
        } else {
            echo "<div class='error'>❌ INSERT GAGAL!</div>";
            $errorInfo = $stmt->errorInfo();
            echo "<pre>" . print_r($errorInfo, true) . "</pre>";
        }
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Error saat test INSERT: " . $e->getMessage() . "</div>";
}
echo "</div>";

// =============================================
// TEST 5: Cek PHP Configuration
// =============================================
echo "<div class='test-section'>";
echo "<h2>TEST 5: PHP & MySQL Configuration</h2>";

echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>PDO Drivers</td><td>" . implode(', ', PDO::getAvailableDrivers()) . "</td></tr>";

$stmt = $pdo->query("SELECT VERSION() as version");
$version = $stmt->fetch();
echo "<tr><td>MySQL Version</td><td>{$version['version']}</td></tr>";

$stmt = $pdo->query("SHOW VARIABLES LIKE 'autocommit'");
$autocommit = $stmt->fetch();
echo "<tr><td>MySQL Autocommit</td><td>{$autocommit['Value']}</td></tr>";

$stmt = $pdo->query("SHOW VARIABLES LIKE 'transaction_isolation'");
$isolation = $stmt->fetch();
echo "<tr><td>Transaction Isolation</td><td>{$isolation['Value']}</td></tr>";

echo "</table>";
echo "</div>";

// =============================================
// TEST 6: Rekomendasi Solusi
// =============================================
echo "<div class='test-section'>";
echo "<h2>📋 Rekomendasi Solusi</h2>";

echo "<div class='info'>";
echo "<strong>Jika review tidak masuk ke database, coba:</strong><br><br>";
echo "1. ✅ Pastikan AUTOCOMMIT = ON (lihat TEST 4 & 5)<br>";
echo "2. ✅ Tambahkan <code>\$pdo->commit();</code> setelah execute()<br>";
echo "3. ✅ Cek apakah ada ROLLBACK yang tidak sengaja<br>";
echo "4. ✅ Pastikan tidak ada error di PHP error log<br>";
echo "5. ✅ Restart Apache & MySQL di XAMPP<br>";
echo "6. ✅ Cek file admin.php menggunakan koneksi database yang benar<br>";
echo "</div>";

echo "<div class='warning'>";
echo "<strong>⚠️ PENTING:</strong><br>";
echo "- Jika TEST 4 berhasil = Database OK, masalah di code admin.php<br>";
echo "- Jika TEST 4 gagal = Database config bermasalah<br>";
echo "</div>";

echo "</div>";

echo "<hr>";
echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<p style='color: #3498db;'>🔧 Diagnostic Tool by CineScope | " . date('Y-m-d H:i:s') . "</p>";
echo "<a href='admin.php' style='color: #27ae60; text-decoration: none; font-weight: bold;'>← Kembali ke Admin Panel</a>";
echo "</div>";

echo "</body></html>";
?>