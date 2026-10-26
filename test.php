<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "Test 1: OK<br>";

// Test koneksi DB

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    echo "Test 2: DB Connected OK<br>";
    
    $movies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
    echo "Test 3: Movies count = $movies<br>";
    
    $reviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    echo "Test 4: Reviews count = $reviews<br>";
    
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Test 5: Users count = $users<br>";
    
    echo "<br>✅ ALL TESTS PASSED";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}