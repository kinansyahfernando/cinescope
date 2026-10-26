<?php
// File: fix_reviews.php
// Simpan di: C:/xampp/htdocs/movie-review/fix_reviews.php
// Akses: http://localhost/movie-review/fix_reviews.php
// TOOL UNTUK MEMPERBAIKI DAN CEK DATA REVIEWS

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // nama database di Laragon
$username = 'root';
$password = '';            // default Laragon kosong


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Database Connection Error: " . $e->getMessage());
}

$action = $_GET['action'] ?? 'check';
$message = '';
$messageType = '';

// PROSES ACTIONS
if ($action === 'delete_invalid') {
    // Hapus reviews yang movie_id-nya tidak valid
    $stmt = $pdo->query("DELETE FROM reviews WHERE movie_id NOT IN (SELECT id FROM movies)");
    $deleted = $stmt->rowCount();
    $message = "✅ Berhasil menghapus $deleted review dengan movie_id tidak valid";
    $messageType = 'success';
    $action = 'check'; // Kembali ke check
}

if ($action === 'delete_no_user') {
    // Hapus reviews yang user_id-nya tidak valid
    $stmt = $pdo->query("DELETE FROM reviews WHERE user_id NOT IN (SELECT id FROM users)");
    $deleted = $stmt->rowCount();
    $message = "✅ Berhasil menghapus $deleted review dengan user_id tidak valid";
    $messageType = 'success';
    $action = 'check';
}

if ($action === 'update_ratings') {
    // Update average_rating di tabel movies berdasarkan reviews
    $movies = $pdo->query("SELECT id FROM movies")->fetchAll();
    $updated = 0;
    
    foreach($movies as $movie) {
        $avgRating = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE movie_id = :movie_id");
        $avgRating->execute([':movie_id' => $movie['id']]);
        $result = $avgRating->fetch();
        
        if ($result['avg_rating']) {
            $update = $pdo->prepare("UPDATE movies SET average_rating = :rating WHERE id = :id");
            $update->execute([
                ':rating' => $result['avg_rating'],
                ':id' => $movie['id']
            ]);
            $updated++;
        }
    }
    
    $message = "✅ Berhasil update rating untuk $updated film";
    $messageType = 'success';
    $action = 'check';
}

// AMBIL DATA UNTUK PENGECEKAN
// 1. Total reviews
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

// 2. Reviews dengan movie_id tidak valid
$invalidMovieReviews = $pdo->query("SELECT r.*, m.id as valid_movie 
    FROM reviews r 
    LEFT JOIN movies m ON r.movie_id = m.id 
    WHERE m.id IS NULL")->fetchAll();

// 3. Reviews dengan user_id tidak valid
$invalidUserReviews = $pdo->query("SELECT r.*, u.id as valid_user 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE u.id IS NULL")->fetchAll();

// 4. Daftar film yang ada
$movies = $pdo->query("SELECT id, title FROM movies ORDER BY title")->fetchAll();

// 5. Daftar user yang ada
$users = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username")->fetchAll();

// 6. Reviews yang VALID
$validReviews = $pdo->query("SELECT r.*, m.title as movie_title, u.username, u.full_name 
    FROM reviews r 
    INNER JOIN movies m ON r.movie_id = m.id
    INNER JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC")->fetchAll();

// 7. Statistik per film
$movieStats = $pdo->query("SELECT m.id, m.title, m.average_rating,
    COUNT(r.id) as review_count,
    AVG(r.rating) as calculated_avg
    FROM movies m
    LEFT JOIN reviews r ON m.id = r.movie_id
    GROUP BY m.id
    ORDER BY review_count DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Reviews - Diagnostic Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        .message {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-card.danger .stat-number {
            color: #dc3545;
        }
        
        .stat-card.success .stat-number {
            color: #28a745;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 12px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Admin Panel
        </a>
        
        <div class="header">
            <h1>🔧 Review Diagnostic Tool</h1>
            <p>Tool untuk memeriksa dan memperbaiki data reviews</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- STATISTIK OVERVIEW -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-comments"></i> Total Reviews</h3>
                <div class="stat-number"><?php echo $totalReviews; ?></div>
            </div>
            <div class="stat-card success">
                <h3><i class="fas fa-check-circle"></i> Reviews Valid</h3>
                <div class="stat-number"><?php echo count($validReviews); ?></div>
            </div>
            <div class="stat-card danger">
                <h3><i class="fas fa-exclamation-triangle"></i> Movie ID Invalid</h3>
                <div class="stat-number"><?php echo count($invalidMovieReviews); ?></div>
            </div>
            <div class="stat-card danger">
                <h3><i class="fas fa-user-times"></i> User ID Invalid</h3>
                <div class="stat-number"><?php echo count($invalidUserReviews); ?></div>
            </div>
        </div>
        
        <!-- ACTION BUTTONS -->
        <?php if (count($invalidMovieReviews) > 0 || count($invalidUserReviews) > 0): ?>
            <div class="card">
                <h2>⚡ Quick Actions</h2>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Ditemukan data review yang tidak valid. Gunakan tombol di bawah untuk memperbaiki.
                </div>
                <div class="action-buttons">
                    <?php if (count($invalidMovieReviews) > 0): ?>
                        <a href="?action=delete_invalid" class="btn btn-danger" onclick="return confirm('Hapus <?php echo count($invalidMovieReviews); ?> review dengan movie_id tidak valid?')">
                            <i class="fas fa-trash"></i> Hapus Review dengan Movie ID Invalid (<?php echo count($invalidMovieReviews); ?>)
                        </a>
                    <?php endif; ?>
                    
                    <?php if (count($invalidUserReviews) > 0): ?>
                        <a href="?action=delete_no_user" class="btn btn-danger" onclick="return confirm('Hapus <?php echo count($invalidUserReviews); ?> review dengan user_id tidak valid?')">
                            <i class="fas fa-user-times"></i> Hapus Review dengan User ID Invalid (<?php echo count($invalidUserReviews); ?>)
                        </a>
                    <?php endif; ?>
                    
                    <a href="?action=update_ratings" class="btn btn-success" onclick="return confirm('Update rating semua film berdasarkan reviews?')">
                        <i class="fas fa-sync"></i> Update Semua Rating Film
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- STATISTIK PER FILM -->
        <div class="card">
            <h2>📊 Statistik Reviews Per Film</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Film</th>
                        <th>Judul Film</th>
                        <th>Rating di DB</th>
                        <th>Jumlah Reviews</th>
                        <th>Rata-rata dari Reviews</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($movieStats as $stat): ?>
                    <tr>
                        <td><?php echo $stat['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($stat['title']); ?></strong></td>
                        <td><span class="badge badge-info">★ <?php echo number_format($stat['average_rating'], 1); ?></span></td>
                        <td><span class="badge badge-warning"><?php echo $stat['review_count']; ?> reviews</span></td>
                        <td>
                            <?php if ($stat['calculated_avg']): ?>
                                <span class="badge badge-success">★ <?php echo number_format($stat['calculated_avg'], 1); ?></span>
                            <?php else: ?>
                                <span class="badge" style="background:#ddd;color:#666;">Belum ada review</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($stat['review_count'] == 0) {
                                echo '<span class="badge" style="background:#ddd;color:#666;">Belum direview</span>';
                            } elseif (abs($stat['average_rating'] - $stat['calculated_avg']) > 0.1) {
                                echo '<span class="badge badge-warning">⚠️ Perlu update</span>';
                            } else {
                                echo '<span class="badge badge-success">✓ OK</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- REVIEWS VALID -->
        <div class="card">
            <h2>✅ Reviews Valid (<?php echo count($validReviews); ?>)</h2>
            <?php if (count($validReviews) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Film</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Review Text</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($validReviews as $review): ?>
                        <tr>
                            <td><?php echo $review['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($review['movie_title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($review['full_name'] ?? $review['username']); ?></td>
                            <td><span class="badge badge-warning">★ <?php echo number_format($review['rating'], 1); ?></span></td>
                            <td style="max-width:300px;"><?php echo substr(htmlspecialchars($review['review_text']), 0, 100); ?>...</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum Ada Review Valid</h3>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- REVIEWS INVALID - MOVIE ID -->
        <?php if (count($invalidMovieReviews) > 0): ?>
        <div class="card">
            <h2>❌ Reviews dengan Movie ID Tidak Valid (<?php echo count($invalidMovieReviews); ?>)</h2>
            <div class="alert alert-warning">
                <strong>Masalah:</strong> Review ini memiliki movie_id yang tidak ada di tabel movies. 
                Kemungkinan film sudah dihapus tapi reviewnya masih ada.
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Review ID</th>
                        <th>Movie ID (Invalid)</th>
                        <th>User ID</th>
                        <th>Rating</th>
                        <th>Review Text</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invalidMovieReviews as $review): ?>
                    <tr>
                        <td><?php echo $review['id']; ?></td>
                        <td><span class="badge badge-danger"><?php echo $review['movie_id']; ?> (Tidak ditemukan)</span></td>
                        <td><?php echo $review['user_id']; ?></td>
                        <td><span class="badge badge-warning">★ <?php echo number_format($review['rating'], 1); ?></span></td>
                        <td style="max-width:300px;"><?php echo substr(htmlspecialchars($review['review_text']), 0, 100); ?>...</td>
                        <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- REVIEWS INVALID - USER ID -->
        <?php if (count($invalidUserReviews) > 0): ?>
        <div class="card">
            <h2>❌ Reviews dengan User ID Tidak Valid (<?php echo count($invalidUserReviews); ?>)</h2>
            <div class="alert alert-warning">
                <strong>Masalah:</strong> Review ini memiliki user_id yang tidak ada di tabel users. 
                Kemungkinan user sudah dihapus tapi reviewnya masih ada.
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Review ID</th>
                        <th>Movie ID</th>
                        <th>User ID (Invalid)</th>
                        <th>Rating</th>
                        <th>Review Text</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invalidUserReviews as $review): ?>
                    <tr>
                        <td><?php echo $review['id']; ?></td>
                        <td><?php echo $review['movie_id']; ?></td>
                        <td><span class="badge badge-danger"><?php echo $review['user_id']; ?> (Tidak ditemukan)</span></td>
                        <td><span class="badge badge-warning">★ <?php echo number_format($review['rating'], 1); ?></span></td>
                        <td style="max-width:300px;"><?php echo substr(htmlspecialchars($review['review_text']), 0, 100); ?>...</td>
                        <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- REFERENSI FILM -->
        <div class="card">
            <h2>🎬 Daftar Film yang Tersedia (<?php echo count($movies); ?>)</h2>
            <div class="alert alert-info">
                Hanya film-film ini yang valid untuk dijadikan referensi dalam reviews.
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Movie ID</th>
                        <th>Judul Film</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($movies as $movie): ?>
                    <tr>
                        <td><span class="badge badge-info"><?php echo $movie['id']; ?></span></td>
                        <td><?php echo htmlspecialchars($movie['title']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- REFERENSI USER -->
        <div class="card">
            <h2>👥 Daftar User yang Tersedia (<?php echo count($users); ?>)</h2>
            <div class="alert alert-info">
                Hanya user-user ini yang valid untuk dijadikan referensi dalam reviews.
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><span class="badge badge-info"><?php echo $user['id']; ?></span></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align:center; padding:30px; color:white; margin-top:30px;">
            <p>© 2024 Movie Review Diagnostic Tool</p>
            <p style="margin-top:10px;">Made with ❤️ for debugging</p>
        </div>
    </div>
</body>
</html>