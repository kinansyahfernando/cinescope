<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konfigurasi
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'movie_review_db';
$backupDir = __DIR__ . '/backups/';

// Buat folder backup jika belum ada
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        die('Gagal membuat folder backups. Coba buat manual!');
    }
}

// Cek permission folder
if (!is_writable($backupDir)) {
    die('Folder backups tidak bisa ditulis. Cek permission!');
}

// Nama file backup
$backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';

try {
    // Koneksi ke database
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Buka file untuk write
    $handle = fopen($backupFile, 'w+');
    if (!$handle) {
        die('Gagal membuat file backup!');
    }
    
    // Write header
    $return = "-- Movie Review Database Backup\n";
    $return .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $return .= "SET time_zone = \"+00:00\";\n\n";
    $return .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $return .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $return .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $return .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
    
    fwrite($handle, $return);
    
    // Get all tables
    $result = $conn->query("SHOW TABLES");
    
    if (!$result) {
        die('Gagal mengambil daftar tabel: ' . $conn->error);
    }
    
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    echo "<h2>📦 Database Backup Progress</h2>";
    echo "<pre>";
    
    // Loop through tables
    foreach ($tables as $table) {
        echo "Backing up table: $table ... ";
        
        $return = "\n\n-- --------------------------------------------------------\n";
        $return .= "-- Table structure for table `$table`\n";
        $return .= "-- --------------------------------------------------------\n\n";
        
        // Drop table
        $return .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table
        $result2 = $conn->query("SHOW CREATE TABLE `$table`");
        if ($result2) {
            $row2 = $result2->fetch_row();
            $return .= $row2[1] . ";\n\n";
            
            // Get table data
            $result3 = $conn->query("SELECT * FROM `$table`");
            
            if ($result3 && $result3->num_rows > 0) {
                $return .= "-- Dumping data for table `$table`\n\n";
                
                while ($row3 = $result3->fetch_assoc()) {
                    $return .= "INSERT INTO `$table` VALUES (";
                    
                    $values = [];
                    foreach ($row3 as $value) {
                        if (is_null($value)) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    
                    $return .= implode(', ', $values);
                    $return .= ");\n";
                }
                
                $return .= "\n";
            }
            
            fwrite($handle, $return);
            echo "✓ Done\n";
        } else {
            echo "✗ Failed\n";
        }
    }
    
    // Write footer
    $return = "\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $return .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $return .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    
    fwrite($handle, $return);
    fclose($handle);
    
    $conn->close();
    
    $fileSize = filesize($backupFile);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    echo "\n✅ BACKUP BERHASIL!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "File: " . basename($backupFile) . "\n";
    echo "Size: " . $fileSizeMB . " MB\n";
    echo "Path: " . $backupFile . "\n";
    echo "Tables: " . count($tables) . "\n";
    echo "\n<a href='backups/" . basename($backupFile) . "' download>📥 Download Backup File</a>";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>