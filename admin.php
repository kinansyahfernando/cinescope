<?php
// admin.php - versi lokal Laragon (PHP 8.4)
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host     = '127.0.0.1';   // atau 'localhost'
$dbname   = 'cinescope';   // SAMA dengan config.php, index.php, user.php (ubah di sini kalau nama databasemu lain)
$username = 'root';
$password = '';            // default Laragon kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Auto migration
try {
    $cols = $pdo->query("SHOW COLUMNS FROM movies")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('status', $cols)) $pdo->exec("ALTER TABLE movies ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'released'");
    if (!in_array('release_date', $cols)) $pdo->exec("ALTER TABLE movies ADD COLUMN release_date DATE NULL");
    if (!in_array('average_rating', $cols)) $pdo->exec("ALTER TABLE movies ADD COLUMN average_rating DECIMAL(3,1) DEFAULT 0");
    if (!in_array('cast', $cols)) $pdo->exec("ALTER TABLE movies ADD COLUMN `cast` VARCHAR(255) NULL");
    
    $rcols = $pdo->query("SHOW COLUMNS FROM reviews")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_name', $rcols)) $pdo->exec("ALTER TABLE reviews ADD COLUMN user_name VARCHAR(100) NULL");
    if (!in_array('created_at', $rcols)) $pdo->exec("ALTER TABLE reviews ADD COLUMN created_at DATETIME NULL");
    if (!in_array('updated_at', $rcols)) $pdo->exec("ALTER TABLE reviews ADD COLUMN updated_at DATETIME NULL");
} catch (PDOException $e) {}

foreach (['uploads', 'uploads/posters', 'uploads/videos'] as $dir) {
    if (!file_exists($dir)) @mkdir($dir, 0777, true);
}

// ============ FILM CONTOH (disimpan di database, jadi muncul di admin, beranda, dan halaman user) ============
function sampleMovieList() {
    return array(
        array('Bayangan Senja',        2024, 112, 'Drama',       'Raka Pradipta',  'Dimas Anggara, Laras Wulandari', 'Seorang fotografer kembali ke kota kecilnya dan menemukan rahasia keluarga yang lama terkubur.'),
        array('Langit Terakhir',       2025, 128, 'Fantasi',     'Maya Kusuma',    'Arka Wijaya, Nadia Safira',      'Sebuah kerajaan di atas awan terancam runtuh, dan hanya seorang anak penjaga mercusuar yang bisa menyelamatkannya.'),
        array('Jejak di Lorong Sunyi', 2023, 105, 'Misteri',     'Bagas Hartono',  'Reza Mahendra, Tika Permata',    'Satu per satu penghuni gang tua menghilang, dan seorang jurnalis muda mulai menyelidikinya.'),
        array('Kota Tanpa Nama',       2024, 118, 'Aksi',        'Yoga Santosa',   'Fajar Nugraha, Sinta Maheswari', 'Mantan agen rahasia dipaksa kembali bertugas ketika kota tempatnya bersembunyi diserang.'),
        array('Rumah di Ujung Jalan',  2022, 98,  'Horor',       'Dewi Anggraini', 'Bimo Setiawan, Citra Ayudia',    'Keluarga yang baru pindah mulai mendengar suara aneh dari rumah kosong di ujung jalan.'),
        array('Cinta Dua Musim',       2025, 109, 'Romantis',    'Intan Maharani', 'Rafi Aditya, Keisha Putri',      'Dua orang asing bertemu di musim hujan dan harus memilih saat kemarau tiba.'),
        array('Tawa di Perantauan',    2023, 101, 'Komedi',      'Hendra Wibowo',  'Joko Prasetyo, Mira Lestari',    'Tiga sahabat merantau ke Jakarta dengan modal nekat dan menghadapi kekacauan demi kekacauan.'),
        array('Samudra Biru',          2024, 121, 'Petualangan', 'Galih Ramadhan', 'Satria Mahesa, Dinda Kirana',    'Ekspedisi mencari pulau legenda membawa sekelompok remaja melewati badai dan teka-teki kuno.'),
    );
}

function seedSampleMovies(PDO $pdo) {
    $cols  = $pdo->query("SHOW COLUMNS FROM movies")->fetchAll(PDO::FETCH_COLUMN);
    $old   = '2000-01-01 00:00:00';   // tanggal lama, supaya tidak menyalip film asli yang baru diupload
    foreach (sampleMovieList() as $d) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE title = ?");
        $check->execute(array($d[0]));
        if ((int)$check->fetchColumn() > 0) continue;   // judul sudah ada -> lewati

        $data = array(
            'title' => $d[0], 'release_year' => $d[1], 'duration' => $d[2], 'genre' => $d[3],
            'director' => $d[4], 'cast' => $d[5], 'synopsis' => $d[6],
            'poster' => '', 'trailer_url' => '', 'status' => 'released',
            'average_rating' => 0, 'created_at' => $old, 'released_at' => $old,
        );
        $data = array_intersect_key($data, array_flip($cols));   // hanya kolom yang benar-benar ada

        $names = array();
        foreach (array_keys($data) as $c) $names[] = '`' . $c . '`';
        $sql = 'INSERT INTO movies (' . implode(',', $names) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')';
        $pdo->prepare($sql)->execute(array_values($data));
    }
}

// Isi otomatis SEKALI saat admin.php pertama dibuka (penanda: uploads/.sample_seeded).
// Film yang kamu hapus lewat admin tidak akan muncul lagi.
$sampleFlag = __DIR__ . '/uploads/.sample_seeded';
if (!file_exists($sampleFlag)) {
    try {
        seedSampleMovies($pdo);
        @file_put_contents($sampleFlag, date('c'));
    } catch (PDOException $e) {
        // gagal (mis. tabel movies belum ada) -> coba lagi di akses berikutnya
    }
}

// Bersihkan penanda teks lama dari sinopsis (dari versi sebelumnya), sekali saja.
$cleanFlag = __DIR__ . '/uploads/.sample_cleaned';
if (!file_exists($cleanFlag)) {
    try {
        $pdo->exec("UPDATE movies SET synopsis = TRIM(REPLACE(synopsis, '[DUMMY]', '')) WHERE synopsis LIKE '%[DUMMY]%'");
        @file_put_contents($cleanFlag, date('c'));
    } catch (PDOException $e) {}
}

$message = '';
$messageType = '';
$action = $_GET['action'] ?? 'dashboard';

function normalizeStatus($s) { return $s === 'coming_soon' ? 'coming_soon' : 'released'; }

function updateMovieAverageRating(PDO $pdo, int $movieId) {
    if ($movieId <= 0) return;
    try {
        $stmt = $pdo->prepare("UPDATE movies SET average_rating = (SELECT COALESCE(AVG(rating),0) FROM reviews WHERE movie_id = ?) WHERE id = ?");
        $stmt->execute([$movieId, $movieId]);
    } catch (PDOException $e) {}
}

// ============ CREATE REVIEW ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    try {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        $user_id  = (int)($_POST['user_id'] ?? 0);
        $rating   = (float)($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? '');

        if ($movie_id <= 0) throw new Exception("Film harus dipilih!");
        if ($user_id <= 0)  throw new Exception("User harus dipilih!");
        if ($rating < 1 || $rating > 5) throw new Exception("Rating harus 1-5!");

        $s = $pdo->prepare("SELECT id, title FROM movies WHERE id = ?");
        $s->execute([$movie_id]);
        $movieData = $s->fetch(PDO::FETCH_ASSOC);
        if (!$movieData) throw new Exception("Film tidak ditemukan!");

        $s = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
        $s->execute([$user_id]);
        $userData = $s->fetch(PDO::FETCH_ASSOC);
        if (!$userData) throw new Exception("User tidak ditemukan!");

        $userName = !empty($userData['full_name']) ? $userData['full_name'] : $userData['username'];
        $reviewTextVal = !empty($review_text) ? $review_text : null;

        $ins = $pdo->prepare("INSERT INTO reviews (movie_id, user_id, rating, review_text, user_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $ins->execute([$movie_id, $user_id, $rating, $reviewTextVal, $userName]);

        updateMovieAverageRating($pdo, $movie_id);
        $_SESSION['success_message'] = "Review berhasil ditambahkan untuk: " . htmlspecialchars($movieData['title']);
        header("Location: admin.php?action=reviews");
        exit;
    } catch(Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// ============ CREATE MOVIE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_movie'])) {
    $posterPath = '';
    if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $posterName = time() . '_' . basename($_FILES['poster']['name']);
        $posterTarget = 'uploads/posters/' . $posterName;
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $posterTarget)) $posterPath = $posterTarget;
    }
    $status = normalizeStatus($_POST['status'] ?? 'released');
    $releaseDate = !empty($_POST['release_date']) ? $_POST['release_date'] : null;

    try {
        $sql = "INSERT INTO movies (title, release_year, duration, genre, director, `cast`, synopsis, poster, trailer_url, status, release_date, average_rating, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['title'], $_POST['release_year'], $_POST['duration'],
            $_POST['genre'], $_POST['director'], $_POST['cast'] ?? '',
            $_POST['synopsis'], $posterPath, $_POST['trailer_url'],
            $status, $releaseDate
        ]);
    } catch (PDOException $e) {
        $sql = "INSERT INTO movies (title, release_year, duration, genre, director, synopsis, poster, trailer_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['title'], $_POST['release_year'], $_POST['duration'], $_POST['genre'], $_POST['director'], $_POST['synopsis'], $posterPath, $_POST['trailer_url']]);
    }
    header("Location: admin.php?action=movies&msg=created");
    exit;
}

// ============ UPDATE MOVIE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_movie'])) {
    $movieId = (int)$_POST['movie_id'];
    $posterPath = $_POST['existing_poster'] ?? '';

    if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $posterName = time() . '_' . basename($_FILES['poster']['name']);
        $posterTarget = 'uploads/posters/' . $posterName;
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $posterTarget)) $posterPath = $posterTarget;
    }

    try {
        $sql = "UPDATE movies SET title=?, release_year=?, duration=?, genre=?, director=?, `cast`=?, synopsis=?, poster=?, trailer_url=?, status=?, release_date=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['title'], $_POST['release_year'], $_POST['duration'],
            $_POST['genre'], $_POST['director'], $_POST['cast'] ?? '',
            $_POST['synopsis'], $posterPath, $_POST['trailer_url'],
            normalizeStatus($_POST['status'] ?? 'released'),
            !empty($_POST['release_date']) ? $_POST['release_date'] : null,
            $movieId
        ]);
    } catch (PDOException $e) {
        $sql = "UPDATE movies SET title=?, release_year=?, duration=?, genre=?, director=?, synopsis=?, poster=?, trailer_url=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['title'], $_POST['release_year'], $_POST['duration'], $_POST['genre'], $_POST['director'], $_POST['synopsis'], $posterPath, $_POST['trailer_url'], $movieId]);
    }

    updateMovieAverageRating($pdo, $movieId);
    header("Location: admin.php?action=movies&msg=updated");
    exit;
}

// ============ SET STATUS ============
if (isset($_GET['set_status'], $_GET['id']) && is_numeric($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE movies SET status=? WHERE id=?");
        $stmt->execute([normalizeStatus($_GET['set_status']), (int)$_GET['id']]);
    } catch (PDOException $e) {}
    header("Location: admin.php?action=movies&msg=updated");
    exit;
}

// ============ DELETE MOVIE ============
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM movies WHERE id=?")->execute([(int)$_GET['delete']]);
    header("Location: admin.php?action=movies&msg=deleted");
    exit;
}

// ============ DELETE REVIEW ============
if (isset($_GET['delete_review']) && is_numeric($_GET['delete_review'])) {
    $s = $pdo->prepare("SELECT movie_id FROM reviews WHERE id=?");
    $s->execute([(int)$_GET['delete_review']]);
    $r = $s->fetch();
    $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([(int)$_GET['delete_review']]);
    if ($r) updateMovieAverageRating($pdo, (int)$r['movie_id']);
    header("Location: admin.php?action=reviews&msg=review_deleted");
    exit;
}

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $messageType = 'success';
    unset($_SESSION['success_message']);
}

if (isset($_GET['msg'])) {
    $msgs = [
        'created' => 'Film berhasil ditambahkan!',
        'updated' => 'Film berhasil diupdate!',
        'deleted' => 'Film berhasil dihapus!',
        'review_deleted' => 'Review berhasil dihapus!'
    ];
    if (isset($msgs[$_GET['msg']])) {
        $message = $msgs[$_GET['msg']];
        $messageType = 'success';
    }
}

// ============ FETCH DATA ============
$movies = $pdo->query("SELECT m.*, (SELECT COUNT(*) FROM reviews WHERE movie_id = m.id) as review_count, COALESCE((SELECT AVG(rating) FROM reviews WHERE movie_id = m.id), 0) as calc_avg_rating FROM movies m ORDER BY m.id ASC")->fetchAll(PDO::FETCH_ASSOC);
$reviews = $pdo->query("SELECT r.*, m.title as movie_title, u.username, u.full_name FROM reviews r LEFT JOIN movies m ON r.movie_id = m.id LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['all', 'released', 'coming_soon'])) $statusFilter = 'all';

$countReleased = 0;
$countComing = 0;
foreach ($movies as $m) {
    if (($m['status'] ?? 'released') === 'coming_soon') $countComing++;
    else $countReleased++;
}

$filteredMovies = [];
foreach ($movies as $m) {
    $s = ($m['status'] ?? 'released') === 'coming_soon' ? 'coming_soon' : 'released';
    if ($statusFilter === 'all' || $s === $statusFilter) $filteredMovies[] = $m;
}

$moviesList = $pdo->query("SELECT id, title FROM movies ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$usersList = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

$totalMovies = count($movies);
$totalReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avgRating = $pdo->query("SELECT AVG(average_rating) FROM movies WHERE average_rating > 0")->fetchColumn();

$editMovie = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $s = $pdo->prepare("SELECT * FROM movies WHERE id=?");
    $s->execute([(int)$_GET['id']]);
    $editMovie = $s->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Panel - CineScope</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa}
.sidebar{position:fixed;left:0;top:0;width:260px;height:100vh;background:linear-gradient(180deg,#667eea,#764ba2);color:#fff;padding:30px 20px;overflow-y:auto}
.sidebar h1{font-size:1.5em;margin-bottom:30px;text-align:center}
.nav-menu{list-style:none}
.nav-menu li{margin-bottom:10px}
.nav-menu a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#fff;text-decoration:none;border-radius:8px;transition:.3s}
.nav-menu a:hover,.nav-menu a.active{background:rgba(255,255,255,.2)}
.main-content{margin-left:260px;padding:30px;min-height:100vh}
.header{background:#fff;padding:25px 30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);margin-bottom:30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}
.header h2{color:#333}
.btn{padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:.3s;font-size:14px;color:#fff;background:#667eea}
.btn:hover{opacity:.9}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}
.btn-success{background:#28a745}
.btn-danger{background:#dc3545}
.btn-warning{background:#ffc107;color:#333}
.btn-info{background:#17a2b8}
.btn-sm{padding:6px 12px;font-size:13px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
.stat-card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.stat-card h3{color:#666;font-size:.9em;margin-bottom:10px}
.stat-number{font-size:2.5em;font-weight:bold;color:#667eea}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);padding:30px;margin-bottom:20px}
.card h3{margin-bottom:20px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-weight:600;margin-bottom:8px}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;font-family:inherit}
.form-group textarea{resize:vertical;min-height:100px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}
table{width:100%;border-collapse:collapse}
table th{background:#f8f9fa;padding:15px;text-align:left;border-bottom:2px solid #e0e0e0}
table td{padding:15px;border-bottom:1px solid #e0e0e0}
.movie-poster-thumb{width:60px;height:90px;object-fit:cover;border-radius:6px}
.rating-badge{background:#ffc107;color:#333;padding:5px 12px;border-radius:15px;font-weight:bold;font-size:.9em}
.message{padding:15px 20px;border-radius:8px;margin-bottom:20px;font-weight:500}
.message.success{background:#d4edda;color:#155724;border-left:4px solid #28a745}
.message.error{background:#f8d7da;color:#721c24;border-left:4px solid #dc3545}
.empty-state{text-align:center;padding:60px 20px;color:#999}
.empty-state i{font-size:4em;margin-bottom:20px;color:#ddd;display:block}
.status-tabs{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.status-tabs a{padding:10px 20px;background:#fff;color:#667eea;border-radius:25px;text-decoration:none;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.status-tabs a.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}
.rating-radio-group{display:flex;gap:15px;flex-wrap:wrap}
.rating-option{padding:8px 15px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.rating-option input{display:inline}
@media(max-width:768px){.sidebar{width:100%;height:auto;position:relative}.main-content{margin-left:0;padding:15px}.form-row,.form-row-3{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="sidebar">
<h1>🎬 Admin Panel</h1>
<ul class="nav-menu">
<li><a href="?action=dashboard" class="<?= $action === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
<li><a href="?action=movies&status=all" class="<?= $action === 'movies' ? 'active' : '' ?>"><i class="fas fa-film"></i> Daftar Film</a></li>
<li><a href="?action=add" class="<?= $action === 'add' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Tambah Film</a></li>
<li><a href="?action=reviews" class="<?= $action === 'reviews' ? 'active' : '' ?>"><i class="fas fa-star"></i> Kelola Reviews</a></li>
<li><a href="?action=add_review" class="<?= $action === 'add_review' ? 'active' : '' ?>"><i class="fas fa-comment-dots"></i> Tambah Review</a></li>
</ul>
</div>

<div class="main-content">
<?php if ($action !== 'dashboard'): ?>
<a href="?action=<?= ($action === 'edit' || $action === 'add' || $action === 'add_review') ? 'movies' : 'dashboard' ?>" class="btn btn-primary" style="margin-bottom:20px"><i class="fas fa-arrow-left"></i> Kembali</a>
<?php endif; ?>

<?php if ($message): ?><div class="message <?= $messageType ?>"><?= $message ?></div><?php endif; ?>

<?php if ($action === 'dashboard'): ?>
<div class="header"><h2>📊 Dashboard</h2></div>
<div class="stats-grid">
<div class="stat-card"><h3>Total Film</h3><div class="stat-number"><?= $totalMovies ?></div></div>
<div class="stat-card"><h3>Coming Soon</h3><div class="stat-number"><?= $countComing ?></div></div>
<div class="stat-card"><h3>Total Reviews</h3><div class="stat-number"><?= $totalReviews ?></div></div>
<div class="stat-card"><h3>Rating Rata-rata</h3><div class="stat-number"><?= $avgRating ? number_format($avgRating, 1) : '0.0' ?></div></div>
</div>
<div class="card"><h3>🎥 Daftar Film</h3>
<table><thead><tr><th>ID</th><th>Poster</th><th>Judul</th><th>Tahun</th><th>Rating</th><th>Reviews</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($movies as $m): ?>
<tr>
<td><?= $m['id'] ?></td>
<td><?php if(!empty($m['poster'])): ?><img src="<?= htmlspecialchars($m['poster']) ?>" class="movie-poster-thumb"><?php endif; ?></td>
<td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
<td><?= htmlspecialchars($m['release_year'] ?? '') ?></td>
<td><span class="rating-badge">⭐ <?= number_format($m['calc_avg_rating'], 1) ?></span></td>
<td><?= $m['review_count'] ?></td>
<td><a href="?action=edit&id=<?= $m['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<?php elseif ($action === 'movies'): ?>
<div class="header"><h2>🎥 Kelola Film</h2><a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Film</a></div>
<div class="status-tabs">
<a href="?action=movies&status=all" class="<?= $statusFilter === 'all' ? 'active' : '' ?>">Semua (<?= count($movies) ?>)</a>
<a href="?action=movies&status=released" class="<?= $statusFilter === 'released' ? 'active' : '' ?>">Daftar Film (<?= $countReleased ?>)</a>
<a href="?action=movies&status=coming_soon" class="<?= $statusFilter === 'coming_soon' ? 'active' : '' ?>">Coming Soon (<?= $countComing ?>)</a>
</div>
<div class="card">
<?php if (count($filteredMovies) === 0): ?>
<div class="empty-state"><i class="fas fa-film"></i><h3>Belum ada film</h3></div>
<?php else: ?>
<table><thead><tr><th>ID</th><th>Poster</th><th>Judul</th><th>Genre</th><th>Tahun</th><th>Rating</th><th>Reviews</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($filteredMovies as $m): ?>
<tr>
<td><?= $m['id'] ?></td>
<td><?php if(!empty($m['poster'])): ?><img src="<?= htmlspecialchars($m['poster']) ?>" class="movie-poster-thumb"><?php endif; ?></td>
<td><strong><?= htmlspecialchars($m['title']) ?></strong>
<?php if (($m['status'] ?? 'released') === 'coming_soon'): ?>
<br><span class="rating-badge" style="background:#17a2b8;color:#fff;font-size:.75em">⏳ Coming Soon</span>
<?php else: ?>
<br><span class="rating-badge" style="background:#28a745;color:#fff;font-size:.75em">🎬 Daftar Film</span>
<?php endif; ?>
</td>
<td><?= htmlspecialchars($m['genre'] ?? '') ?></td>
<td><?= htmlspecialchars($m['release_year'] ?? '') ?></td>
<td><span class="rating-badge">⭐ <?= number_format($m['calc_avg_rating'], 1) ?></span></td>
<td><?= $m['review_count'] ?></td>
<td>
<a href="?action=edit&id=<?= $m['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
<?php if (($m['status'] ?? 'released') === 'coming_soon'): ?>
<a href="?set_status=released&id=<?= $m['id'] ?>" class="btn btn-success btn-sm" title="Pindah ke Daftar Film" onclick="return confirm('Pindahkan ke Daftar Film?')"><i class="fas fa-check"></i></a>
<?php else: ?>
<a href="?set_status=coming_soon&id=<?= $m['id'] ?>" class="btn btn-info btn-sm" title="Pindah ke Coming Soon" onclick="return confirm('Pindahkan ke Coming Soon?')"><i class="fas fa-clock"></i></a>
<?php endif; ?>
<a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus film ini?')"><i class="fas fa-trash"></i></a>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<div class="header"><h2><?= $action === 'edit' ? '✏️ Edit Film' : '➕ Tambah Film' ?></h2></div>
<div class="card">
<form method="POST" enctype="multipart/form-data">
<?php if ($action === 'edit'): ?>
<input type="hidden" name="movie_id" value="<?= $editMovie['id'] ?>">
<input type="hidden" name="existing_poster" value="<?= htmlspecialchars($editMovie['poster'] ?? '') ?>">
<?php endif; ?>

<div class="form-group">
<label>Judul Film *</label>
<input type="text" name="title" required value="<?= htmlspecialchars($editMovie['title'] ?? '') ?>">
</div>

<div class="form-row-3">
<div class="form-group"><label>Tahun *</label><input type="number" name="release_year" required value="<?= htmlspecialchars($editMovie['release_year'] ?? date('Y')) ?>"></div>
<div class="form-group"><label>Durasi (menit)</label><input type="number" name="duration" value="<?= htmlspecialchars($editMovie['duration'] ?? '') ?>"></div>
<div class="form-group"><label>Genre *</label><input type="text" name="genre" required value="<?= htmlspecialchars($editMovie['genre'] ?? '') ?>"></div>
</div>

<div class="form-row">
<div class="form-group">
<label>Tampilkan di *</label>
<?php $curStatus = $editMovie['status'] ?? 'released'; ?>
<select name="status" required>
<option value="released" <?= $curStatus !== 'coming_soon' ? 'selected' : '' ?>>🎬 Daftar Film (sudah tayang)</option>
<option value="coming_soon" <?= $curStatus === 'coming_soon' ? 'selected' : '' ?>>⏳ Coming Soon (segera tayang)</option>
</select>
</div>
<div class="form-group">
<label>Tanggal Rilis (opsional, untuk Coming Soon)</label>
<input type="date" name="release_date" value="<?= htmlspecialchars($editMovie['release_date'] ?? '') ?>">
</div>
</div>

<div class="form-group"><label>Sutradara</label><input type="text" name="director" value="<?= htmlspecialchars($editMovie['director'] ?? '') ?>"></div>
<div class="form-group"><label>Pemain (Cast)</label><input type="text" name="cast" value="<?= htmlspecialchars($editMovie['cast'] ?? '') ?>" placeholder="Pisahkan dengan koma"></div>
<div class="form-group"><label>Sinopsis *</label><textarea name="synopsis" required><?= htmlspecialchars($editMovie['synopsis'] ?? '') ?></textarea></div>
<div class="form-group"><label>Upload Poster</label><input type="file" name="poster" accept="image/*"><?php if(!empty($editMovie['poster'])): ?><img src="<?= $editMovie['poster'] ?>" style="max-width:150px;margin-top:10px;border-radius:8px"><?php endif; ?></div>
<div class="form-group"><label>URL Trailer</label><input type="url" name="trailer_url" value="<?= htmlspecialchars($editMovie['trailer_url'] ?? '') ?>"></div>

<div style="display:flex;gap:10px;margin-top:20px">
<button type="submit" name="<?= $action === 'edit' ? 'update_movie' : 'create_movie' ?>" class="btn btn-primary"><i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Simpan' ?></button>
<a href="?action=movies" class="btn btn-danger"><i class="fas fa-times"></i> Batal</a>
</div>
</form>
</div>

<?php elseif ($action === 'add_review'): ?>
<div class="header"><h2>➕ Tambah Review Manual</h2></div>
<div class="card">
<form method="POST">
<div class="form-row">
<div class="form-group"><label>Pilih Film *</label><select name="movie_id" required><option value="">-- Pilih --</option><?php foreach($moviesList as $m): ?><option value="<?= $m['id'] ?>">[<?= $m['id'] ?>] <?= htmlspecialchars($m['title']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Pilih User *</label><select name="user_id" required><option value="">-- Pilih --</option><?php foreach($usersList as $u): ?><option value="<?= $u['id'] ?>">[<?= $u['id'] ?>] <?= htmlspecialchars($u['username']) ?><?= $u['full_name'] ? ' ('.htmlspecialchars($u['full_name']).')' : '' ?></option><?php endforeach; ?></select></div>
</div>
<div class="form-group"><label>Rating (1-5) *</label>
<div class="rating-radio-group">
<?php for($i=1;$i<=5;$i++): ?><label class="rating-option"><input type="radio" name="rating" value="<?= $i ?>" required> <?= $i ?> <i class="fas fa-star" style="color:#ffc107"></i></label><?php endfor; ?>
</div></div>
<div class="form-group"><label>Teks Review</label><textarea name="review_text"></textarea></div>
<div style="display:flex;gap:10px;margin-top:20px">
<button type="submit" name="submit_review" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</button>
<a href="?action=reviews" class="btn btn-danger">Batal</a>
</div>
</form>
</div>

<?php elseif ($action === 'reviews'): ?>
<div class="header"><h2>⭐ Kelola Reviews</h2><a href="?action=add_review" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Review</a></div>
<div class="card">
<?php if (count($reviews) > 0): ?>
<table><thead><tr><th>ID</th><th>Film</th><th>User</th><th>Rating</th><th>Review</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($reviews as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><strong><?= htmlspecialchars($r['movie_title'] ?? '?') ?></strong></td>
<td><?= htmlspecialchars($r['full_name'] ?? $r['username'] ?? 'Anon') ?></td>
<td><span class="rating-badge">⭐ <?= number_format($r['rating'], 1) ?></span></td>
<td><?= htmlspecialchars(substr($r['review_text'] ?? '', 0, 80)) ?><?= strlen($r['review_text'] ?? '') > 80 ? '...' : '' ?></td>
<td><?= $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-' ?></td>
<td><a href="?action=reviews&delete_review=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')"><i class="fas fa-trash"></i></a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?>
<div class="empty-state"><i class="fas fa-comments"></i><h3>Belum ada review</h3><a href="?action=add_review" class="btn btn-primary" style="margin-top:20px"><i class="fas fa-plus"></i> Tambah</a></div>
<?php endif; ?>
</div>
<?php endif; ?>

<div style="text-align:center;padding:30px;color:#999;margin-top:50px">© 2024 CineScope Admin</div>
</div>
</body>
</html>