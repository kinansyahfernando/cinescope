<?php
/**
 * Database Configuration
 * Simpan file ini di: C:\xampp\htdocs\movie-review\config\database.php
 */

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'movie_review_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Fungsi untuk mendapatkan koneksi database
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ));
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Tidak dapat terhubung ke database",
                "error" => $e->getMessage()
            ));
            exit;
        }
    }
    
    return $pdo;
}

/**
 * Fungsi helper untuk mengirim JSON response
 */
function sendResponse($success, $data = null, $message = "", $code = 200) {
    http_response_code($code);
    
    $response = array("success" => $success);
    
    if ($message) {
        $response["message"] = $message;
    }
    
    if ($data !== null) {
        $response["data"] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Set CORS headers untuk allow cross-origin requests
 */
function setCORSHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
?>