<?php
header('Content-Type: application/json');

// Folder upload
$posterDir = '../uploads/posters/';
$videoDir  = '../uploads/videos/';

// Buat folder kalau belum ada
if (!is_dir($posterDir)) mkdir($posterDir, 0777, true);
if (!is_dir($videoDir))  mkdir($videoDir, 0777, true);

// Ambil data dari form
$title    = $_POST['title'] ?? '';
$year     = $_POST['year'] ?? '';
$genre    = $_POST['genre'] ?? '';
$director = $_POST['director'] ?? '';
$synopsis = $_POST['synopsis'] ?? '';

// Validasi
if (!$title || !$year || !$genre || !$director || !$synopsis) {
    echo json_encode(['success'=>false, 'message'=>'Data film tidak lengkap']);
    exit;
}

// Ambil file
$posterFile = $_FILES['poster'] ?? null;
$videoFile  = $_FILES['video'] ?? null;

if (!$posterFile || !$videoFile) {
    echo json_encode(['success'=>false, 'message'=>'Poster atau video tidak ditemukan']);
    exit;
}

// Nama file unik (hindari overwrite)
$posterName = time() . '_' . basename($posterFile['name']);
$videoName  = time() . '_' . basename($videoFile['name']);

$posterPath = $posterDir . $posterName;
$videoPath  = $videoDir . $videoName;

// Pindahkan file
if (!move_uploaded_file($posterFile['tmp_name'], $posterPath) ||
    !move_uploaded_file($videoFile['tmp_name'], $videoPath)) {
    echo json_encode(['success'=>false, 'message'=>'Gagal upload file']);
    exit;
}

// Update films.json
$jsonFile = '../films.json';
$films = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

$films[] = [
    'title'    => $title,
    'year'     => $year,
    'genre'    => $genre,
    'director' => $director,
    'synopsis' => $synopsis,
    'poster'   => 'uploads/posters/' . $posterName,
    'video'    => 'uploads/videos/' . $videoName,
    'rating'   => 0
];

// Simpan kembali
file_put_contents($jsonFile, json_encode($films, JSON_PRETTY_PRINT));

echo json_encode(['success'=>true, 'message'=>'Film berhasil diupload dan ditambahkan ke frontend']);
?>
