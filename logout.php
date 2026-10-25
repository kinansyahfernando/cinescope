<?php
session_start();

// Jika belum login, redirect
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil data dari session
$username  = $_SESSION['username'] ?? "Pengguna";
$email     = $_SESSION['email'] ?? "Tidak diketahui";
$fullName  = $_SESSION['full_name'] ?? $username;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Menu Profil Pengguna - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #555;
            --background-dark: #0a0a0a;
            --card-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-muted: #ccc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-dark);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 800px;
            width: 90%;
        }

        .profile-card {
            background-color: var(--card-dark);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            text-align: center;
            position: relative;
        }

        .back-btn-top {
            position: absolute;
            top: 20px;
            left: 20px;
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.1em;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .back-btn-top:hover {
            background-color: #333;
            color: var(--primary-color);
        }

        .avatar-img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            object-fit: cover;
            margin-bottom: 20px;
        }

        .profile-header {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .profile-header h2 {
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .profile-detail {
            font-size: 1.1em;
            margin: 15px 0;
            color: var(--text-muted);
            text-align: left;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }

        .profile-detail span {
            color: var(--text-light);
            font-weight: 500;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 25px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background-color: #b80710;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-card">

        <!-- Tombol Kembali -->
        <button onclick="history.back()" class="back-btn-top">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>

        <!-- Foto Profil Default -->
        <div class="profile-avatar">
            <img src="https://cdn.pixabay.com/photo/2021/07/25/08/03/account-6491185_960_720.png"
                 class="avatar-img">
        </div>

        <!-- Header Profil -->
        <div class="profile-header">
            <h2>Selamat Datang, <?= htmlspecialchars($fullName) ?></h2>
            <p>Ini adalah halaman profil Anda.</p>
        </div>

        <!-- Detail User -->
        <div class="profile-details">

            <p class="profile-detail">
                <i class="fas fa-user-tag"></i> Username:
                <span><?= htmlspecialchars($username) ?></span>
            </p>

            <p class="profile-detail">
                <i class="fas fa-envelope"></i> Email:
                <span><?= htmlspecialchars($email) ?></span>
            </p>

            <p class="profile-detail">
                <i class="fas fa-calendar-alt"></i> Status:
                <span>Aktif (Sejak Login)</span>
            </p>

        </div>

        <!-- Tombol Logout -->
        <!-- Tombol Logout -->
<button type="button" onclick="confirmDelete()" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i> Keluar (Log Out)
</button>

<!-- Popup Konfirmasi -->
<div id="confirmPopup" 
     style="
        display:none; 
        position:fixed;
        top:0;left:0;width:100%;height:100%;
        background:rgba(0,0,0,0.7);
        justify-content:center;
        align-items:center;
     ">
    <div style="
        background:#1a1a1a;
        padding:25px;
        border-radius:10px;
        width:90%;
        max-width:350px;
        text-align:center;
        color:white;
        box-shadow:0 0 15px black;
    ">
        <h3 style="margin-bottom:10px;">Hapus Akun?</h3>
        <p style="margin-bottom:20px;">Yakin ingin menghapus akun Anda?</p>

        <button onclick="deleteAccount()" 
                style="background:#e50914;border:none;padding:10px 20px;
                color:white;border-radius:6px;font-weight:600;cursor:pointer;">
            IYA
        </button>

        <button onclick="closePopup()" 
                style="background:#444;border:none;padding:10px 20px;
                color:white;border-radius:6px;font-weight:600;cursor:pointer;margin-left:10px;">
            BATAL
        </button>
    </div>
</div>

<script>
function confirmDelete() {
    document.getElementById("confirmPopup").style.display = "flex";
}
function closePopup() {
    document.getElementById("confirmPopup").style.display = "none";
}
function deleteAccount() {
    window.location.href = "logout_delete.php";
}
</script>

