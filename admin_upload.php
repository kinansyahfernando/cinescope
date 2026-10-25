<?php
// File: admin_upload.php
// Simpan di: C:/xampp/htdocs/movie-review/admin_upload.php
// Akses: http://localhost/movie-review/admin_upload.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$messageType = '';

// Buat folder uploads jika belum ada
$uploadDirs = [
    'uploads',
    'uploads/posters',
    'uploads/videos'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Proses upload jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $host = '127.0.0.1';
    $dbname = 'movie_review_db';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get form data
        $title = $_POST['title'] ?? '';
        $original_title = $_POST['original_title'] ?? '';
        $release_year = $_POST['release_year'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $director = $_POST['director'] ?? '';
        $synopsis = $_POST['synopsis'] ?? '';
        $trailer_url = $_POST['trailer_url'] ?? '';
        
        $posterPath = '';
        $videoPath = '';
        
        // Upload poster
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $posterName = time() . '_' . basename($_FILES['poster']['name']);
            $posterTarget = 'uploads/posters/' . $posterName;
            
            if (move_uploaded_file($_FILES['poster']['tmp_name'], $posterTarget)) {
                $posterPath = $posterTarget;
            }
        }
        
        // Upload video/trailer
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $videoName = time() . '_' . basename($_FILES['video']['name']);
            $videoTarget = 'uploads/videos/' . $videoName;
            
            if (move_uploaded_file($_FILES['video']['tmp_name'], $videoTarget)) {
                $videoPath = $videoTarget;
            }
        }
        
        // Insert ke database
        $sql = "INSERT INTO movies (
                    title, original_title, release_year, duration, 
                    genre, director, synopsis, poster, trailer_url, created_at
                ) VALUES (
                    :title, :original_title, :release_year, :duration,
                    :genre, :director, :synopsis, :poster, :trailer_url, NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':original_title' => $original_title,
            ':release_year' => $release_year,
            ':duration' => $duration,
            ':genre' => $genre,
            ':director' => $director,
            ':synopsis' => $synopsis,
            ':poster' => $posterPath,
            ':trailer_url' => $trailer_url ?: $videoPath
        ]);
        
        $message = "✅ Film '$title' berhasil diupload!";
        $messageType = 'success';
        
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Film - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #667eea;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 1em;
        }
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="url"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            cursor: pointer;
        }
        
        .file-info {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #764ba2;
        }
        
        .preview-area {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
        }
        
        .preview-area.show {
            display: block;
        }
        
        .preview-area img {
            max-width: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .row {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 1.8em;
            }
            
            .form-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Upload Film Baru</h1>
            <p class="subtitle">Tambahkan film ke database Movie Review</p>
        </div>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Judul Film <span style="color:red;">*</span></label>
                    <input type="text" id="title" name="title" required placeholder="Contoh: Pengabdi Setan 2">
                </div>
                
                <div class="form-group">
                    <label for="original_title">Judul Asli (Opsional)</label>
                    <input type="text" id="original_title" name="original_title" placeholder="Contoh: Satan's Slaves 2">
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label for="release_year">Tahun Rilis <span style="color:red;">*</span></label>
                        <input type="number" id="release_year" name="release_year" required min="1900" max="2099" placeholder="2024">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Durasi (menit)</label>
                        <input type="number" id="duration" name="duration" placeholder="120">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="genre">Genre <span style="color:red;">*</span></label>
                    <input type="text" id="genre" name="genre" required placeholder="Contoh: Horror, Thriller, Drama (pisahkan dengan koma)">
                </div>
                
                <div class="form-group">
                    <label for="director">Sutradara</label>
                    <input type="text" id="director" name="director" placeholder="Contoh: Joko Anwar">
                </div>
                
                <div class="form-group">
                    <label for="synopsis">Sinopsis <span style="color:red;">*</span></label>
                    <textarea id="synopsis" name="synopsis" required placeholder="Tuliskan ringkasan cerita film..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="poster">Upload Poster Film</label>
                    <input type="file" id="poster" name="poster" accept="image/*" onchange="previewPoster(this)">
                    <div class="file-info">Format: JPG, PNG, WEBP (Max 5MB)</div>
                    <div id="posterPreview" class="preview-area"></div>
                </div>
                
                <div class="form-group">
                    <label for="trailer_url">URL Trailer YouTube (Opsional)</label>
                    <input type="url" id="trailer_url" name="trailer_url" placeholder="https://youtube.com/watch?v=...">
                </div>
                
                <div class="form-group">
                    <label for="video">Upload Video Trailer (Opsional)</label>
                    <input type="file" id="video" name="video" accept="video/*">
                    <div class="file-info">Format: MP4, MKV, AVI (Max 100MB)</div>
                </div>
                
                <button type="submit" class="btn">📤 Upload Film</button>
            </form>
            
            <a href="index.html" class="back-link">← Kembali ke Beranda</a>
        </div>
    </div>
    
    <script>
        function previewPoster(input) {
            const preview = document.getElementById('posterPreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview Poster">';
                    preview.classList.add('show');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Auto-fill current year
        document.getElementById('release_year').value = new Date().getFullYear();
    </script>
</body>
</html>