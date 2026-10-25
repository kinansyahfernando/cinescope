<?php
// File: daftar tonton.php (FIXED)
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$CURRENT_USER_NAME = $_SESSION['username'] ?? 'Guest';
$USER_ID = $_SESSION['user_id'] ?? null;

// Database Configuration
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Error Koneksi Database: " . $e->getMessage());
}

// 🔥 Ambil semua data film dari database untuk mapping poster
$moviesStmt = $pdo->query("SELECT id, title, poster, genre FROM movies");
$allMovies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);

// Buat mapping untuk mudah akses data film
$movieMap = [];
foreach ($allMovies as $movie) {
    $movieMap[$movie['id']] = $movie;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Daftar Tonton Saya - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #e50914;
            --background-dark: #0a0a0a;
            --card-dark: #1a1a1a;
            --text-light: #ffffff;
            --star-gold: #ffc107;
            --gradient-primary: linear-gradient(135deg, #e50914 0%, #c0392b 100%);
            --gradient-dark: linear-gradient(180deg, #1a1a1a 0%, #0a0a0a 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--gradient-dark);
            color: var(--text-light); 
            line-height: 1.6; 
            padding: 20px;
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 30px; 
            background: rgba(26, 26, 26, 0.95);
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .back-link {
            text-decoration: none;
            color: var(--text-light);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #333 0%, #222 100%);
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .back-link:hover {
            background: var(--gradient-primary);
            transform: translateX(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
        }

        .back-link i {
            font-size: 1.1em;
        }

        .header {
            margin-bottom: 35px;
        }

        .header h1 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 2.8em;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 0.9em;
        }

        .watchlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            padding-top: 20px;
        }

        .movie-card {
            background: linear-gradient(145deg, #2a2a2a 0%, #1f1f1f 100%);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
        }

        .movie-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
            border-radius: 12px;
        }

        .movie-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.4);
            border-color: var(--primary-color);
        }

        .movie-card:hover::before {
            opacity: 0.1;
        }

        .poster-container {
            position: relative;
            overflow: hidden;
            height: 330px;
            background: #000;
        }

        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .movie-card:hover .movie-poster {
            transform: scale(1.1);
        }

        .poster-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.8) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .movie-card:hover .poster-overlay {
            opacity: 1;
        }

        .favorite-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--gradient-primary);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 3px 10px rgba(229, 9, 20, 0.5);
        }
        
        .movie-info {
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .movie-info h3 {
            font-size: 1.15em;
            margin-bottom: 10px;
            color: var(--text-light);
            min-height: 50px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            font-weight: 600;
            line-height: 1.4;
        }

        .movie-genre {
            font-size: 0.85em;
            color: #aaa;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .movie-genre i {
            color: var(--star-gold);
        }

        .remove-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            width: 100%;
            padding: 12px 0;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
            border-radius: 8px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .remove-btn:hover {
            background: linear-gradient(135deg, #c0392b 0%, #8b0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(192, 57, 43, 0.4);
        }

        .remove-btn:active {
            transform: translateY(0);
        }
        
        .empty-message {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(145deg, #2a2a2a 0%, #1f1f1f 100%);
            border-radius: 20px;
            color: #ccc;
            border: 2px dashed #444;
        }

        .empty-message i {
            font-size: 4em;
            margin-bottom: 20px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .empty-message h3 {
            font-size: 1.8em;
            color: var(--text-light);
            margin-bottom: 15px;
        }

        .empty-message p {
            font-size: 1.1em;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .watchlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }

            .poster-container {
                height: 240px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="user.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>

        <div class="header">
            <h1><i class="fas fa-bookmark"></i> Daftar Tonton Saya</h1>
        </div>

        <div id="watchlistContainer" class="watchlist-grid">
            <!-- Content akan dimuat via JavaScript -->
        </div>
    </div>

    <script>
        const WATCHLIST_KEY = 'cineScopeWatchlist';
        const CURRENT_USER_EMAIL = localStorage.getItem('cineScopeCurrentUser') || 'guest@cinescope.com';
        const container = document.getElementById('watchlistContainer');

        // 🔥 Movie Database Mapping dari PHP
        const movieDatabase = <?php echo json_encode($movieMap); ?>;

        // 🔥 Movie ID to Poster Mapping (hardcoded fallback)
        const moviePosterMap = {
            'SORE_AADC_3': 'uploads/posters/poster_sore.jpg',
            'MOVIE_2': 'uploads/posters/poster_petaka.jpg',
            'DILAN_1990': 'uploads/posters/poster_dilan.jpg',
            'REST_AREA': 'uploads/posters/poster_restarea.jpg',
            'TUKAR_TAKDIR': 'uploads/posters/poster_tukartakdir.jpg',
            'RANGGA_CINTA': 'uploads/posters/poster_aadc.jpg'
        };

        function getWatchlist() {
            const watchlistData = JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || {};
            return watchlistData[CURRENT_USER_EMAIL] || [];
        }

        function saveWatchlist(watchlist) {
            const watchlistData = JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || {};
            watchlistData[CURRENT_USER_EMAIL] = watchlist;
            localStorage.setItem(WATCHLIST_KEY, JSON.stringify(watchlistData));
        }

        function getPosterPath(movie) {
            // Priority 1: Check if poster exists in movie object
            if (movie.poster && movie.poster !== '' && movie.poster !== 'uploads/posters/default.jpg') {
                return movie.poster;
            }

            // Priority 2: Check moviePosterMap using movie ID
            if (moviePosterMap[movie.id]) {
                return moviePosterMap[movie.id];
            }

            // Priority 3: Check database mapping if movie has numeric ID
            const numericId = parseInt(movie.id);
            if (!isNaN(numericId) && movieDatabase[numericId]) {
                return movieDatabase[numericId].poster || 'uploads/posters/default.jpg';
            }

            // Priority 4: Try to extract from ID string
            const idToPathMap = {
                'SORE': 'poster_sore.jpg',
                'PETAKA': 'poster_petaka.jpg',
                'DILAN': 'poster_dilan.jpg',
                'REST': 'poster_restarea.jpg',
                'TUKAR': 'poster_tukartakdir.jpg',
                'RANGGA': 'poster_aadc.jpg',
                'AADC': 'poster_aadc.jpg'
            };

            for (const [key, filename] of Object.entries(idToPathMap)) {
                if (movie.id && movie.id.includes(key)) {
                    return 'uploads/posters/' + filename;
                }
            }

            // Default fallback
            return 'uploads/posters/default.jpg';
        }

        function removeMovie(movieId) {
            if (!confirm('Apakah Anda yakin ingin menghapus film ini dari Daftar Tonton?')) {
                return;
            }

            let watchlist = getWatchlist();
            const initialLength = watchlist.length;
            
            watchlist = watchlist.filter(movie => movie.id !== movieId);
            
            if (watchlist.length < initialLength) {
                saveWatchlist(watchlist);
                renderWatchlist();
                
                // Show success message
                const notification = document.createElement('div');
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4); z-index: 1000; font-weight: 600;';
                notification.innerHTML = '<i class="fas fa-check-circle"></i> Film berhasil dihapus!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => notification.remove(), 500);
                }, 2000);
            }
        }

        function renderWatchlist() {
            const watchlist = getWatchlist();
            container.innerHTML = '';

            console.log('Rendering watchlist:', watchlist); // Debug

            // Jika kosong
            if (watchlist.length === 0) {
                container.className = '';
                container.innerHTML = `
                    <div class="empty-message">
                        <i class="fas fa-film"></i>
                        <h3>Daftar Tonton Kosong</h3>
                        <p>Anda belum menambahkan film apapun ke daftar tonton.</p>
                        <p style="margin-top: 20px;">
                            <a href="user.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                            </a>
                        </p>
                    </div>
                `;
                return;
            }
            
            container.className = 'watchlist-grid';

            watchlist.forEach(movie => {
                const card = document.createElement('div');
                card.className = 'movie-card';
                
                const posterSrc = getPosterPath(movie);
                console.log('Movie:', movie.title, 'Poster:', posterSrc); // Debug
                
                card.innerHTML = `
                    <div class="poster-container">
                        <span class="favorite-badge"><i class="fas fa-heart"></i> Favorit</span>
                        <img src="${posterSrc}" alt="Poster ${movie.title}" class="movie-poster" onerror="this.src='uploads/posters/default.jpg';">
                        <div class="poster-overlay"></div>
                    </div>
                    <div class="movie-info">
                        <h3>${movie.title}</h3>
                        <p class="movie-genre"><i class="fas fa-tags"></i> ${movie.genre || 'Genre tidak tersedia'}</p>
                        <button class="remove-btn" data-movie-id="${movie.id}">
                            <i class="fas fa-trash-alt"></i> Hapus dari Daftar
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });
            
            // Event listener untuk tombol hapus
            document.querySelectorAll('.remove-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const movieId = this.getAttribute('data-movie-id');
                    removeMovie(movieId);
                });
            });
        }

        // Load watchlist saat halaman dimuat
        document.addEventListener('DOMContentLoaded', renderWatchlist);
    </script>
</body>
</html>