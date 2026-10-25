<?php
// File: user.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database Configuration
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

// Cek login
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserName = $_SESSION['username'] ?? 'Guest';


// =======================
// 🔥 FITUR UPLOAD POSTER - SUPPORT MULTIPLE DILAN
// =======================
if (isset($_POST['uploadPoster'])) {
    $movieId = $_POST['movie_id'];
    $posterFile = $_FILES['poster'];

    // Buat folder uploads/posters/ jika belum ada
    $uploadDir = __DIR__ . "/uploads/posters/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Nama file unik
    $fileName = "poster_" . $movieId . "_" . time() . "_" . basename($posterFile['name']);
    $filePath = $uploadDir . $fileName;

    // Pindahkan file
    if (move_uploaded_file($posterFile['tmp_name'], $filePath)) {

        // Path untuk database (relatif)
        $dbPath = "uploads/posters/" . $fileName;

        // Cek apakah ini film Dilan
        $checkDilan = $pdo->prepare("SELECT title FROM movies WHERE id = ?");
        $checkDilan->execute([$movieId]);
        $movieData = $checkDilan->fetch(PDO::FETCH_ASSOC);
        
        if ($movieData && stripos($movieData['title'], 'Dilan') !== false) {
            // Update SEMUA film Dilan dengan poster yang sama
            $updateStmt = $pdo->prepare("UPDATE movies SET poster = ? WHERE title LIKE '%Dilan%'");
            $updateStmt->execute([$dbPath]);
            echo "<script>alert('Poster berhasil diupload & diperbarui untuk SEMUA film Dilan!');</script>";
        } else {
            // Update hanya film yang dipilih
            $updateStmt = $pdo->prepare("UPDATE movies SET poster = ? WHERE id = ?");
            $updateStmt->execute([$dbPath, $movieId]);
            echo "<script>alert('Poster berhasil diupload & diperbarui!');</script>";
        }
    } else {
        echo "<script>alert('Upload gagal!');</script>";
    }
}


// ======================
// FUNGSI HELPER UNTUK POSTER
// ======================
function getPosterPath($posterFromDB) {
    if (empty($posterFromDB)) {
        return null;
    }
    
    // Cek apakah file ada
    $possiblePaths = [
        __DIR__ . '/' . $posterFromDB,
        $posterFromDB,
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $posterFromDB; // Return path relatif untuk src
        }
    }
    
    return null;
}


// ======================
// QUERY MOVIES
// ======================

// Rekomendasi
$recommendedStmt = $pdo->query("
    SELECT m.*, 
           COALESCE(AVG(r.rating),0) as avg_rating, 
           COUNT(r.id) as review_count
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.id IN (1,2,3,4,5,6)
    GROUP BY m.id
    ORDER BY FIELD(m.id,1,2,3,4,5,6)
");
$recommendedMovies = $recommendedStmt->fetchAll(PDO::FETCH_ASSOC);

// Film terbaru
$newMoviesStmt = $pdo->query("
    SELECT m.*, 
           COALESCE(AVG(r.rating),0) as avg_rating, 
           COUNT(r.id) as review_count
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.id NOT IN (1,2,3,4,5,6)
    GROUP BY m.id
    ORDER BY m.created_at DESC
    LIMIT 6
");
$newMovies = $newMoviesStmt->fetchAll(PDO::FETCH_ASSOC);

// Trending
$trendingStmt = $pdo->query("
    SELECT m.*, 
           COALESCE(AVG(r.rating),0) as avg_rating, 
           COUNT(r.id) as review_count
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    GROUP BY m.id
    HAVING review_count > 0
    ORDER BY review_count DESC, avg_rating DESC
    LIMIT 3
");
$trendingMovies = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);


// Generate link film - SEMUA DILAN KE FILE YANG SAMA
function generateMovieCardLink($movie) {
    // Jika judulnya mengandung "Dilan", redirect ke dilan 1990.php
    if (stripos($movie['title'], 'Dilan') !== false) {
        return 'dilan 1990.php';
    }
    
    // Mapping film lainnya
    $movieLinks = [
        1 => 'film.sore.html',
        2 => 'Petaka Gunung Gede.php',
        3 => 'Dilan 1990.php',
        4 => 'rest area.html',
        5 => 'tukar takdir.html',
        6 => 'rangga & cinta.html'
    ];
    return $movieLinks[$movie['id']] ?? '#';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CineScope - Movie Review Community</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- ========================== -->
<!-- 🔥 FORM UPLOAD POSTER -->
<!-- ========================== -->
<?php if ($isLoggedIn): ?>
<div style="padding:20px;margin:20px;background:#f1f1f1;border-radius:10px;">
  <h2>Upload Poster Film</h2>
  <p style="color:#666;margin-bottom:15px;">
    <i class="fas fa-info-circle"></i> 
    <strong>Catatan:</strong> Upload poster untuk film Dilan akan otomatis mengupdate semua seri Dilan (1990, 1991, dll).
  </p>

  <form action="" method="POST" enctype="multipart/form-data">
      <label>Pilih Film:</label><br>
      <select name="movie_id" required style="width:100%;padding:10px;margin:10px 0;border-radius:5px;border:1px solid #ccc;">
          <?php
          $moviesList = $pdo->query("SELECT id, title FROM movies ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
          foreach ($moviesList as $m):
          ?>
            <option value="<?= $m['id'] ?>">
                <?= htmlspecialchars($m['title']) ?>
                <?php if (stripos($m['title'], 'Dilan') !== false): ?>
                    ⭐ (Akan update semua Dilan)
                <?php endif; ?>
            </option>
          <?php endforeach; ?>
      </select>

      <br>

      <label>Upload Poster (JPG/PNG):</label><br>
      <input type="file" name="poster" accept="image/*" required style="margin:10px 0;">

      <br><br>

      <button type="submit" name="uploadPoster" style="background:#007bff;color:white;padding:12px 24px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;">
          <i class="fas fa-upload"></i> Upload Poster
      </button>
  </form>
</div>
<?php endif; ?>


<!-- ========================================= -->
<!-- NAVBAR -->
<!-- ========================================= -->

<nav class="navbar">
  <div class="nav-container">
    <div class="nav-brand"><h1>CineScope</h1></div>

    <div class="nav-search">
      <input type="text" id="searchInput" placeholder="Cari film..." class="search-input">
      <button class="search-btn" id="searchBtn"><i class="fas fa-search"></i></button>
    </div>

    <div class="nav-menu">
      <a href="jelajahi.html" class="nav-link">Jelajahi</a>
      <a href="daftar tonton.html" class="nav-link">Daftar Tonton</a>
      <a href="artikel.html" class="nav-link">Artikel</a>

      <?php if ($isLoggedIn): ?>
        <a href="logout.php" class="nav-link">Log Out</a>
      <?php else: ?>
        <a href="login.html" class="nav-link">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>


<!-- ============================== -->
<!-- TOP TRENDING -->
<!-- ============================== -->
<?php if (count($trendingMovies) > 0): ?>
<section class="section trending-section">
  <div class="container">
    <h2 class="section-title"><i class="fas fa-fire"></i> Top 3 Film Trending</h2>

    <div class="movies-grid">
      <?php foreach ($trendingMovies as $index => $movie): ?>
        <div class="movie-card">
          <a href="<?php echo generateMovieCardLink($movie); ?>">

            <?php 
            $posterPath = getPosterPath($movie['poster']);
            if ($posterPath): ?>
              <img src="<?php echo htmlspecialchars($posterPath); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
            <?php else: ?>
              <div style="height:300px;background:#666;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;">
                  🎬
              </div>
            <?php endif; ?>

            <div class="trending-badge">🔥 Top <?= $index+1 ?></div>
          </a>

          <div class="movie-info">
            <div class="movie-title"><?= htmlspecialchars($movie['title']); ?></div>
            <div class="movie-genre"><?= htmlspecialchars($movie['genre']); ?></div>
            <div class="movie-rating">
                ⭐ <?= number_format($movie['avg_rating'],1) ?> (<?= $movie['review_count'] ?> ulasan)
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- ========================= -->
<!-- REKOMENDASI -->
<!-- ========================= -->
<section class="section new-section">
  <div class="container">
    <h2 class="section-title"><i class="fas fa-thumbs-up"></i> Rekomendasi</h2>

    <div class="movies-grid">
      <?php foreach ($recommendedMovies as $movie): ?>
        <div class="movie-card"
          data-title="<?= strtolower(htmlspecialchars($movie['title'])) ?>"
          data-genre="<?= strtolower(htmlspecialchars($movie['genre'])) ?>">

          <a href="<?= generateMovieCardLink($movie); ?>">

            <?php 
            $posterPath = getPosterPath($movie['poster']);
            if ($posterPath): ?>
              <img src="<?= htmlspecialchars($posterPath) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            <?php else: ?>
              <div style="height:300px;background:#0277bd;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;">
                🎬
              </div>
            <?php endif; ?>

            <div class="new-badge"><i class="fas fa-star"></i> Rekomendasi</div>
          </a>

          <div class="movie-info">
            <div class="movie-title"><?= htmlspecialchars($movie['title']); ?></div>
            <div class="movie-genre"><?= htmlspecialchars($movie['genre']); ?></div>
            <div class="movie-rating">⭐ <?= number_format($movie['avg_rating'],1) ?>/5</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ======================= -->
<!-- RILIS TERBARU -->
<!-- ======================= -->
<section class="section new-section">
  <div class="container">
    <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Rilis Terbaru</h2>

    <div class="movies-grid">
      <?php foreach ($newMovies as $movie): ?>
        <div class="movie-card"
          data-title="<?= strtolower(htmlspecialchars($movie['title'])) ?>"
          data-genre="<?= strtolower(htmlspecialchars($movie['genre'])) ?>">

          <a href="<?= generateMovieCardLink($movie); ?>">

            <?php 
            $posterPath = getPosterPath($movie['poster']);
            if ($posterPath): ?>
              <img src="<?= htmlspecialchars($posterPath) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            <?php else: ?>
              <div style="height:300px;background:#558b2f;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;">
                🎬
              </div>
            <?php endif; ?>

            <div class="new-badge"><i class="fas fa-star"></i> Baru</div>
          </a>

          <div class="movie-info">
            <div class="movie-title"><?= htmlspecialchars($movie['title']); ?></div>
            <div class="movie-genre"><?= htmlspecialchars($movie['genre']); ?></div>
            <div class="movie-rating">⭐ <?= number_format($movie['avg_rating'],1) ?>/5</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<script>
// FITUR SEARCH
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const movieCards = document.querySelectorAll(".movie-card");

  searchInput.addEventListener("keyup", () => {
      const q = searchInput.value.toLowerCase();
      movieCards.forEach(card => {
          const title = card.getAttribute("data-title");
          const genre = card.getAttribute("data-genre");

          card.style.display = 
             title.includes(q) || genre.includes(q) ? "" : "none";
      });
  });
});
</script>

</body>
</html>