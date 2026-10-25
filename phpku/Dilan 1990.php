<?php
// File: dilan 1990.php
// Pastikan file ini dijalankan di server lokal (XAMPP/WAMP)

// ==================== KONFIGURASI ====================
// ID Film Dilan 1990 (ID 3, sesuai dengan struktur database Anda)
$MOVIE_ID = 3; 
// ID User (Asumsi User ID 1 adalah default/yang sedang login)
$USER_ID = 1; 
$CURRENT_USER_NAME = 'Pengguna CineScope Baru'; 

// Database Configuration (HARUS SAMA dengan admin.php)
$host = '127.0.0.1';
$dbname = 'movie_review_db';
$username = 'root';
$password = ''; // Pastikan ini kosong jika XAMPP/MySQL Anda tidak memiliki password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Error Koneksi Database. Pastikan XAMPP/WAMP Anda berjalan dan database terhubung.");
}

// ==================== FUNGSI UTILITY ====================

// Fungsi untuk membuat HTML Ulasan Saya (menampilkan SEMUA ulasan dari user ini)
function generate_reviews_html($reviews, $default_user_name) {
    ob_start();
    if (count($reviews) > 0): 
        foreach ($reviews as $review): ?>
            <div class="review-item">
                <div class="rating-display">
                    <?php 
                        $starHtml = '';
                        for ($i = 1; $i <= 5; $i++) {
                            $color = $i <= $review['rating'] ? 'var(--star-gold)' : '#444';
                            $starHtml .= '<i class="fas fa-star" style="color: ' . $color . '; font-size: 1.1rem;"></i>';
                        }
                        echo $starHtml;
                    ?>
                </div>
                <p><?php echo htmlspecialchars($review['review_text'] ?: '(Tidak ada ulasan tertulis)'); ?></p>
                <div class="reviewer-info">
                    Oleh: <?php echo htmlspecialchars($review['full_name'] ?: $review['username'] ?: $default_user_name); ?> 
                    - 
                    <?php echo date('d M Y, H:i', strtotime($review['created_at'])); ?>
                </div>
            </div>
        <?php endforeach;
    else: ?>
        <p style="color:#777; font-style: italic;">Anda belum membuat ulasan untuk film ini. Kirim ulasan Anda di atas!</p>
    <?php endif;
    
    return ob_get_clean();
}

// ==================== PROSES AJAX SUBMIT ULASAN (POST) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_ajax']) && $_POST['is_ajax'] == 1) {
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];
    $rating = (float)$_POST['rating'] ?? 0;
    $reviewText = trim($_POST['reviewText'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $response['message'] = 'Rating harus diisi antara 1 sampai 5 bintang!';
        echo json_encode($response);
        exit;
    }

    try {
        // 🔥 PERBAIKAN: SELALU INSERT REVIEW BARU (TIDAK CEK DUPLICATE)
        $sql = "INSERT INTO reviews (movie_id, user_id, rating, review_text, created_at, updated_at) 
                VALUES (:movie_id, :user_id, :rating, :reviewText, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':movie_id' => $MOVIE_ID, 
            ':user_id' => $USER_ID, 
            ':rating' => $rating, 
            ':reviewText' => $reviewText
        ]);
        
        $response['success'] = true;
        $response['message'] = 'Ulasan Anda berhasil ditambahkan!';

    } catch (PDOException $e) {
        $response['message'] = 'Error Database saat menyimpan ulasan: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
// ==================== END AJAX SUBMIT ====================


// ==================== FETCH DATA ULASAN (AJAX GET & LOAD AWAL) ====================

// Ambil SEMUA review dari user ini untuk film ini
$myReviewsStmt = $pdo->prepare("SELECT r.*, u.full_name, u.username 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.movie_id = :movie_id AND r.user_id = :user_id 
    ORDER BY r.created_at DESC");
$myReviewsStmt->execute([':movie_id' => $MOVIE_ID, ':user_id' => $USER_ID]);
$myReviews = $myReviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$allReviewsStmt = $pdo->prepare("SELECT COUNT(*) AS total_reviews, AVG(rating) AS average_rating 
    FROM reviews 
    WHERE movie_id = :movie_id");
$allReviewsStmt->execute([':movie_id' => $MOVIE_ID]);
$stats = $allReviewsStmt->fetch(PDO::FETCH_ASSOC);

$totalReviews = $stats['total_reviews'] ?? 0;
$averageRating = $stats['average_rating'] ?? 0;
$formattedAvgRating = number_format($averageRating, $averageRating > 0 ? 1 : 0);

// Jika ini adalah permintaan FETCH DATA dari AJAX, kirim JSON dan keluar
if (isset($_GET['fetch_data']) && $_GET['fetch_data'] == 1) {
    header('Content-Type: application/json');
    $response = [
        'averageRating' => $formattedAvgRating,
        'totalReviews' => $totalReviews,
        'myReviewsHtml' => generate_reviews_html($myReviews, $CURRENT_USER_NAME),
    ];
    echo json_encode($response);
    exit;
}
// ==================== END FETCH DATA ====================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Review Film Dilan 1990 - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* ===========================
           COPY-PASTE STYLE CSS LENGKAP
        =========================== */
        :root {
            --primary-color: #e50914; /* Merah CineScope */
            --background-dark: #0a0a0a;
            --card-dark: #1a1a1a;
            --text-light: #ffffff;
            --star-gold: #ffc107;
            --input-bg: #2d2d2d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--background-dark); color: var(--text-light); line-height: 1.6; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; background-color: var(--card-dark); border-radius: 12px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.5); }
        
        .back-to-home { display: inline-block; background-color: #333; color: var(--text-light); padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-bottom: 20px; transition: background-color 0.3s; cursor: pointer; border: none; font-weight: 600; }
        .back-to-home:hover { background-color: #555; }

        .movie-header { display: flex; gap: 20px; margin-bottom: 30px; align-items: flex-start; }
        .movie-poster { width: 150px; height: 225px; object-fit: cover; border-radius: 8px; }
        .movie-info h1 { color: var(--primary-color); margin-bottom: 5px; font-size: 2em; }
        .movie-info p { color: #ccc; margin-bottom: 10px; }

        .rating-summary { display: flex; align-items: center; gap: 15px; margin-top: 15px; padding: 10px 0; border-top: 1px solid #333; }
        .rating-box { background-color: #333; padding: 8px 15px; border-radius: 5px; text-align: center; }
        .rating-box .score { font-size: 1.8em; font-weight: 700; color: var(--star-gold); line-height: 1; }
        .rating-box .count { font-size: 0.8em; color: #aaa; margin-top: 2px; }

        .review-form-section { margin-bottom: 40px; padding: 20px; border: 1px solid #333; border-radius: 8px; }
        .review-form-section h2 { font-size: 1.8em; color: var(--primary-color); margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-light); }
        
        textarea { width: 100%; min-height: 120px; padding: 10px; border-radius: 4px; border: 1px solid #444; background-color: var(--input-bg); color: var(--text-light); resize: vertical; }

        .submit-btn { background-color: var(--primary-color); color: var(--text-light); padding: 10px 25px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .submit-btn:hover { background-color: #f00; }

        .like-btn { background-color: #3498db; transition: background-color 0.3s; border: none; padding: 10px 15px; }
        .like-btn:hover { background-color: #2980b9; }
        .liked { background-color: var(--primary-color); }
        .liked:hover { background-color: #c0392b; }
        
        .review-link { background-color: #2ecc71; }
        .review-link:hover { background-color: #27ae60; }

        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5em; color: #444; cursor: pointer; padding: 0 5px; transition: color 0.2s; }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label { color: var(--star-gold); }
        
        .my-reviews-section { margin-bottom: 40px; }
        .my-reviews-section h2 { font-size: 1.8em; color: var(--text-light); margin-bottom: 25px; border-bottom: 2px solid var(--star-gold); padding-bottom: 5px; }

        .review-item { background-color: #333; padding: 15px 20px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid var(--primary-color); }
        .rating-display { margin-bottom: 10px; }
        .review-item p { margin-bottom: 10px; font-size: 1em; color: #e0e0e0; }
        .reviewer-info { font-size: 0.85em; color: #aaa; text-align: right; border-top: 1px dashed #555; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        
        <button class="back-to-home" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        
        <section class="movie-header">
            <img src="uploads/posters/1763311429.png" alt="Poster Film Dilan 1990" class="movie-poster"> 
            <div class="movie-info">
                <h1>Dilan 1990</h1>
                <p>2018 • Drama, Romantis • 1h 50m</p>
                <p>Sutradara: Fajar Bustomi • Pemain: Iqbaal Ramadhan, Vanesha Prescilla</p>
                
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <button id="likeButton" class="submit-btn like-btn" data-movie-id="DILAN_1990">
                        <i class="fas fa-thumbs-up"></i> <span id="likeText">Suka</span>
                    </button>
                    <a href="c:\Users\Admin\Downloads\Official Trailer Dilan 1990.mp4" class="submit-btn" style="text-decoration: none; display: inline-block; background-color: #333;"><i class="fas fa-video"></i> Trailer</a>
                    
                    <a href="#my-reviews-display" class="submit-btn review-link" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-eye"></i> Lihat Ulasan Saya
                    </a>
                </div>
                
                <div class="rating-summary" style="margin-top: 25px;">
                    <div class="rating-box">
                        <div class="score" id="averageRating"><?php echo $formattedAvgRating; ?>/5</div>
                        <div class="count">Rata-Rata</div>
                    </div>
                    <div class="rating-box">
                        <div class="score" id="reviewCount"><?php echo $totalReviews; ?></div>
                        <div class="count">Total Ulasan</div>
                    </div>
                    <a href="daftar tonton.html" class="submit-btn review-link" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-bookmark"></i> Daftar Tonton
                    </a>
                </div>
            </div>
        </section>

        <section class="review-form-section">
            <h2><i class="fas fa-edit"></i> Berikan Ulasan dan Rating Anda</h2>
            <form id="reviewForm">
                
                <div class="form-group">
                    <label>Rating Anda (1-5 Bintang)</label>
                    <div class="star-rating" id="ratingInput">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Sangat Suka"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Bagus"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Lumayan"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Kurang"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Buruk"><i class="fas fa-star"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reviewText">Ulasan Anda:</label>
                    <textarea id="reviewText" name="reviewText" placeholder="Tuliskan ulasan Anda tentang Dilan 1990 di sini..."></textarea>
                </div>
                
                <button type="submit" class="submit-btn" id="submitButton">
                    <i class="fas fa-paper-plane"></i> Kirim Ulasan
                </button>
            </form>
        </section>
        
        <section class="my-reviews-section">
            <h2><i class="fas fa-user-check"></i> Ulasan Saya</h2>
            <div id="my-reviews-display">
                <?php echo generate_reviews_html($myReviews, $CURRENT_USER_NAME); ?>
            </div>
        </section>
        
    </div>

   <script>
    const MOVIE_DATA = { id: 'DILAN_1990', title: 'Dilan 1990', poster: '...', genre: '...' };
    const WATCHLIST_KEY = 'cineScopeWatchlist';
    const CURRENT_USER_EMAIL = localStorage.getItem('cineScopeCurrentUser') || 'guest@cinescope.com'; 

    const likeButton = document.getElementById('likeButton');
    const likeText = document.getElementById('likeText');
    const reviewForm = document.getElementById('reviewForm');
    const submitButton = document.getElementById('submitButton');
    const averageRatingSpan = document.getElementById('averageRating');
    const reviewCountSpan = document.getElementById('reviewCount');

    // --- Watchlist Logic (Menggunakan LocalStorage) ---
    function getWatchlist() {
        const watchlistData = JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || {};
        return watchlistData[CURRENT_USER_EMAIL] || [];
    }

    function saveWatchlist(watchlist) {
        const watchlistData = JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || {};
        watchlistData[CURRENT_USER_EMAIL] = watchlist;
        localStorage.setItem(WATCHLIST_KEY, JSON.stringify(watchlistData));
    }

    function updateLikeButtonState(watchlist) {
        const isLiked = watchlist.some(movie => movie.id === MOVIE_DATA.id);
        if (isLiked) {
            likeButton.classList.add('liked');
            likeText.textContent = 'Disukai';
            likeButton.querySelector('i').className = 'fas fa-heart';
        } else {
            likeButton.classList.remove('liked');
            likeText.textContent = 'Suka';
            likeButton.querySelector('i').className = 'fas fa-thumbs-up';
        }
    }
    
    likeButton.addEventListener('click', function() {
        let watchlist = getWatchlist();
        const isLiked = watchlist.some(movie => movie.id === MOVIE_DATA.id);

        if (isLiked) {
            watchlist = watchlist.filter(movie => movie.id !== MOVIE_DATA.id);
            alert(`Film "${MOVIE_DATA.title}" dihapus dari Daftar Tonton.`);
        } else {
            watchlist.push(MOVIE_DATA);
            alert(`Film "${MOVIE_DATA.title}" ditambahkan ke Daftar Tonton!`);
        }

        saveWatchlist(watchlist);
        updateLikeButtonState(watchlist);
    });

    const initialWatchlist = getWatchlist();
    updateLikeButtonState(initialWatchlist);

    
    // ===========================================
    // 💡 FUNGSI AJAX UNTUK KIRIM & REFRESH ULASAN 💡
    // ===========================================

    // Fungsi untuk memuat ulang statistik dan ulasan tanpa reload halaman
    function loadReviewsAndStats() {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
        
        fetch('dilan 1990.php?fetch_data=1')
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memuat data statistik terbaru. Status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // 1. Update Statistik
            averageRatingSpan.textContent = data.averageRating + '/5';
            reviewCountSpan.textContent = data.totalReviews;

            // 2. Update Bagian Ulasan Saya
            document.getElementById('my-reviews-display').innerHTML = data.myReviewsHtml;

            // 3. Enable tombol kembali
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';
        })
        .catch(error => {
            console.error('❌ Error saat memuat ulang data:', error);
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';
        });
    }

    // Event listener saat formulir disubmit (AJAX)
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault(); // 🛑 Mencegah page reload

        const selectedRating = document.querySelector('input[name="rating"]:checked');
        if (!selectedRating) {
            alert("Harap berikan rating (bintang) sebelum mengirim ulasan!");
            return;
        }

        const formData = new URLSearchParams();
        formData.append('is_ajax', 1); // Penanda untuk PHP
        formData.append('rating', selectedRating.value);
        formData.append('reviewText', document.getElementById('reviewText').value);

        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

        fetch('dilan 1990.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             if (!response.ok) {
                throw new Error('Gagal mengirim ulasan. Status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Tampilkan popup sesuai permintaan user
                alert('✅ Review berhasil ditambahkan!'); 
                
                // 🔥 Reset Form: Kosongkan textarea dan uncheck semua radio
                document.getElementById('reviewText').value = ''; 
                document.querySelectorAll('input[name="rating"]').forEach(input => {
                    input.checked = false;
                });
                
                // Muat ulang data (Review dan Statistik)
                loadReviewsAndStats();
            } else {
                alert('❌ Gagal menyimpan ulasan: ' + data.message);
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan'; 
            }
        })
        .catch(error => {
            console.error('❌ Error submitting review:', error);
            alert('❌ Terjadi kesalahan jaringan saat mengirim ulasan. Cek console log.');
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Ulasan';
        });
    });
    </script>
</body>
</html>