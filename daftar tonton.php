<?php
// File: daftar_tonton.php
// Daftar Tonton milik user (disimpan di database, tabel `watchlist`):
// - Film masuk otomatis ketika user menekan tombol "Daftar Tonton" di halaman ulasan film (ulasan_*.php)
// - Tombol "Hapus dari Daftar" mengeluarkan film dari daftar ini
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Hanya user yang sudah login yang punya Daftar Tonton
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?error=' . urlencode('Silakan login dulu untuk melihat Daftar Tonton'));
    exit;
}
// Catat nama file halaman ini supaya tombol "Kembali" di halaman ulasan selalu mengarah ke sini (apa pun nama filenya)
$_SESSION['watchlist_page'] = basename($_SERVER['SCRIPT_NAME']);

$USER_ID           = (int)$_SESSION['user_id'];
$CURRENT_USER_NAME = $_SESSION['username'] ?? 'Guest';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

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

// ---------- Helper ----------
function cs_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function cs_columnExists(PDO $pdo, $table, $column) {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// Cari tabel daftar tonton yang sudah ada; jika belum ada, buat tabel `watchlist` otomatis
// (logika yang sama dengan halaman ulasan_*.php supaya keduanya memakai tabel yang sama)
function cs_watchlistTable(PDO $pdo) {
    foreach (array('watchlist', 'daftar_tonton', 'watchlists') as $tbl) {
        if (cs_columnExists($pdo, $tbl, 'user_id') && cs_columnExists($pdo, $tbl, 'movie_id')) return $tbl;
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `watchlist` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_watch_user_movie (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return 'watchlist';
    } catch (PDOException $e) {
        return null;
    }
}

// Halaman ulasan tiap film (ID film di tabel movies => file ulasan)
function cs_reviewPage($movieId) {
    $map = array(
        1 => 'ulasan_sore.php',
        2 => 'ulasan_petaka_gunung_gede.php',
        3 => 'ulasan_dilan1990.php',
        4 => 'ulasan_rest_area.php',
        5 => 'ulasan_tukartakdir.php',
        6 => 'ulasan_rangga%26cinta.php',
    );
    // Film baru dari admin dibuka lewat ulasan_sore.php?id=ID_FILM
    $url = $map[(int)$movieId] ?? ('ulasan_sore.php?id=' . (int)$movieId);
    // from=watchlist: supaya tombol "Kembali" di halaman ulasan kembali ke Daftar Tonton ini
    return $url . (strpos($url, '?') === false ? '?' : '&') . 'from=watchlist';
}

$wlTable = cs_watchlistTable($pdo);
$pageUrl = rawurlencode(basename($_SERVER['SCRIPT_NAME']));   // nama file ini sendiri (otomatis)

// ---------- Hapus film dari daftar (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        header('Location: ' . $pageUrl . '?msg=csrf');
        exit;
    }
    try {
        if ($wlTable) {
            $movieId = (int)($_POST['movie_id'] ?? 0);
            $pdo->prepare("DELETE FROM `$wlTable` WHERE user_id = ? AND movie_id = ?")->execute([$USER_ID, $movieId]);
        }
        header('Location: ' . $pageUrl . '?msg=removed');
    } catch (PDOException $e) {
        error_log('daftar_tonton.php: ' . $e->getMessage());
        header('Location: ' . $pageUrl . '?msg=error');
    }
    exit;
}

// ---------- Ambil film di Daftar Tonton ----------
$watchlist = array();
if ($wlTable) {
    $order = cs_columnExists($pdo, $wlTable, 'created_at') ? 'ORDER BY w.created_at DESC' : '';
    $st = $pdo->prepare("SELECT m.id, m.title, m.poster, m.genre
                         FROM `$wlTable` w
                         INNER JOIN movies m ON m.id = w.movie_id
                         WHERE w.user_id = ? $order");
    $st->execute([$USER_ID]);
    $watchlist = $st->fetchAll(PDO::FETCH_ASSOC);
}

$messages = array(
    'removed' => array('success', 'Film berhasil dihapus!'),
    'csrf'    => array('error',   'Sesi tidak valid, silakan coba lagi.'),
    'error'   => array('error',   'Terjadi kesalahan, silakan coba lagi.'),
);
$flash = $messages[$_GET['msg'] ?? ''] ?? null;
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

        .back-link i { font-size: 1.1em; }

        .header { margin-bottom: 35px; }

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
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
            border-radius: 12px;
            pointer-events: none;
        }

        .movie-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.4);
            border-color: var(--primary-color);
        }

        .movie-card:hover::before { opacity: 0.1; }

        .poster-container {
            position: relative;
            overflow: hidden;
            height: 330px;
            background: #000;
            display: block;
        }

        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .movie-card:hover .movie-poster { transform: scale(1.1); }

        .poster-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.8) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .movie-card:hover .poster-overlay { opacity: 1; }

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

        .movie-info h3 a { color: inherit; text-decoration: none; }
        .movie-info h3 a:hover { color: var(--star-gold); }

        .movie-genre {
            font-size: 0.85em;
            color: #aaa;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .movie-genre i { color: var(--star-gold); }

        .remove-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            width: 100%;
            padding: 12px 0;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
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

        .remove-btn:active { transform: translateY(0); }

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

        .empty-message h3 { font-size: 1.8em; color: var(--text-light); margin-bottom: 15px; }
        .empty-message p { font-size: 1.1em; line-height: 1.8; margin-bottom: 10px; }

        /* Notifikasi */
        .toast {
            position: fixed; top: 20px; right: 20px; color: white;
            padding: 15px 25px; border-radius: 10px; z-index: 1000; font-weight: 600;
            transition: opacity 0.5s ease;
        }
        .toast.success { background: linear-gradient(135deg, #27ae60, #229954); box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4); }
        .toast.error   { background: linear-gradient(135deg, #e50914, #8b0000); box-shadow: 0 5px 20px rgba(229, 9, 20, 0.4); }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .header h1 { font-size: 2em; }
            .watchlist-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
            .poster-container { height: 240px; }
        }
    </style>
</head>
<body>
    <?php if ($flash): ?>
        <div class="toast <?= $flash[0] ?>" id="toast">
            <i class="fas <?= $flash[0] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> <?= cs_e($flash[1]) ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <a href="user.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>

        <div class="header">
            <h1><i class="fas fa-bookmark"></i> Daftar Tonton Saya</h1>
        </div>

        <?php if (count($watchlist) === 0): ?>
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
        <?php else: ?>
            <div id="watchlistContainer" class="watchlist-grid">
                <?php foreach ($watchlist as $movie):
                    $poster  = !empty($movie['poster']) ? $movie['poster'] : 'uploads/posters/default.jpg';
                    $filmUrl = cs_reviewPage($movie['id']);
                ?>
                    <div class="movie-card">
                        <a class="poster-container" href="<?= cs_e($filmUrl) ?>">
                            <span class="favorite-badge"><i class="fas fa-heart"></i> Favorit</span>
                            <img src="<?= cs_e($poster) ?>" alt="Poster <?= cs_e($movie['title']) ?>" class="movie-poster" onerror="this.onerror=null;this.src='uploads/posters/default.jpg';">
                            <div class="poster-overlay"></div>
                        </a>
                        <div class="movie-info">
                            <h3><a href="<?= cs_e($filmUrl) ?>"><?= cs_e($movie['title']) ?></a></h3>
                            <p class="movie-genre"><i class="fas fa-tags"></i> <?= cs_e(!empty($movie['genre']) ? $movie['genre'] : 'Genre tidak tersedia') ?></p>
                            <form method="post" action="<?= cs_e($pageUrl) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus film ini dari Daftar Tonton?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="csrf" value="<?= cs_e($csrf) ?>">
                                <input type="hidden" name="movie_id" value="<?= (int)$movie['id'] ?>">
                                <button type="submit" class="remove-btn"><i class="fas fa-trash-alt"></i> Hapus dari Daftar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Sembunyikan notifikasi otomatis
        var toast = document.getElementById('toast');
        if (toast) {
            setTimeout(function () {
                toast.style.opacity = '0';
                setTimeout(function () { toast.remove(); }, 500);
            }, 2000);
        }
    </script>
</body>
</html>