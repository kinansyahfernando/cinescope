<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(isset($_FILES['video'])){
    $targetDir = '../uploads/videos/'; // pastikan folder ini ada
    $targetFile = $targetDir . basename($_FILES['video']['name']);

    if(move_uploaded_file($_FILES['video']['tmp_name'], $targetFile)){
        echo "Sukses upload: " . basename($_FILES['video']['name']);
    } else {
        echo "Gagal upload file.";
    }
} else {
    echo "File tidak terkirim.";
}
?>
