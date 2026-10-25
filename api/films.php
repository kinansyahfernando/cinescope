<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Pastikan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit;
}

// Ambil data form
$title = $_POST['title'] ?? '';
$year = $_POST['year'] ?? '';
$genre = $_POST['genre'] ?? '';
$director = $_POST['director'] ?? '';
$synopsis = $_POST['synopsis'] ?? '';

if(!$title || !$year || !$genre || !$director || !$synopsis){
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

// Upload poster
if(!isset($_FILES['poster'])){
    echo json_encode(['success' => false, 'message' => 'Poster tidak ditemukan']);
    exit;
}
$poster = $_FILES['poster'];
$posterName = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($poster['name']));
$posterPath = '../uploads/posters/' . $posterName;
if(!move_uploaded_file($poster['tmp_name'], $posterPath)){
    echo json_encode(['success' => false, 'message' => 'Gagal mengupload poster']);
    exit;
}

// Upload video
if(!isset($_FILES['video'])){
    echo json_encode(['success' => false, 'message' => 'File film tidak ditemukan']);
    exit;
}
$video = $_FILES['video'];
$videoName = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($video['name']));
$videoPath = '../uploads/videos/' . $videoName;
if(!move_uploaded_file($video['tmp_name'], $videoPath)){
    echo json_encode(['success' => false, 'message' => 'Gagal mengupload film']);
    exit;
}

// Simpan ke JSON
$jsonFile = '../films.json';
if(!file_exists($jsonFile)) file_put_contents($jsonFile, json_encode([]));

$films = json_decode(file_get_contents($jsonFile), true);
$films[] = [
    'title' => $title,
    'year' => $year,
    'genre' => $genre,
    'director' => $director,
    'synopsis' => $synopsis,
    'poster' => 'uploads/posters/' . $posterName,
    'video' => 'uploads/videos/' . $videoName
];

file_put_contents($jsonFile, json_encode($films, JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'message' => 'Film berhasil diupload!']);
