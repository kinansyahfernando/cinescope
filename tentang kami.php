<?php
// about.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
   <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>CineScope - Tentang Kami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0a0a0a;
            color: #e0e0e0;
            line-height: 1.6;
        }

        header {
            background-color: #1a1a1a;
            padding: 20px 0;
            border-bottom: 2px solid #e63946;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .back-link {
            position: absolute;
            left: 20px;
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #e63946;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #e63946;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .hero-section {
            background-color: #1a1a1a;
            padding: 60px 40px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }

        .hero-section h1 {
            font-size: 3em;
            color: #e63946;
            margin-bottom: 10px;
            text-shadow: 0 0 5px rgba(230, 57, 70, 0.5);
        }

        .hero-section p {
            font-size: 1.2em;
            color: #cccccc;
            max-width: 800px;
            margin: 0 auto;
        }

        .content-section {
            margin-bottom: 50px;
            padding: 30px;
            background-color: #1a1a1a;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .content-section h2 {
            font-size: 2.2em;
            color: #e63946;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .content-section p {
            margin-bottom: 15px;
            color: #cccccc;
        }

        .team-members {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .team-member {
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(230, 57, 70, 0.2);
        }

        .member-avatar {
            font-size: 3em;
            margin-bottom: 10px;
        }

        .member-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #e63946;
            margin-bottom: 5px;
        }

        .member-role {
            font-style: italic;
            color: #aaa;
        }

        footer {
            text-align: center;
            padding: 20px 0;
            background-color: #1a1a1a;
            border-top: 1px solid #333;
            color: #999;
        }

        footer p {
            margin: 5px 0;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali ke Home
            </a>
            <a href="#" class="logo">CineScope</a>
        </div>
    </header>

    <div class="container">
        <div class="hero-section">
            <h1>Mengenal CineScope</h1>
            <p>Platform review film terbaik untuk komunitas pecinta sinema Indonesia. Kami hadir untuk menghubungkan Anda dengan ulasan film yang jujur dan mendalam.</p>
        </div>

        <div class="content-section">
            <h2>Misi Kami</h2>
            <p>Misi kami adalah menjadi sumber tepercaya bagi para penggemar film di Indonesia. Kami berdedikasi untuk menyediakan ulasan yang beragam, informatif, dan menghormati setiap genre film, baik lokal maupun internasional.</p>
            <p>Kami percaya bahwa setiap film memiliki cerita untuk diceritakan, dan setiap penonton memiliki sudut pandang yang berharga. CineScope adalah panggung untuk semua perspektif tersebut.</p>
        </div>

        <div class="content-section">
            <h2>Tim Inti Kami</h2>
            <p>Dibangun oleh tim kecil yang bersemangat (2 Pria, 1 Wanita), didedikasikan untuk sinema Indonesia dan global.</p>
            <div class="team-members">
                <div class="team-member">
                    <div class="member-avatar">👨</div>
                    <div class="member-name">Fernando</div>
                    <div class="member-role">Founder & CEO</div>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👩</div>
                    <div class="member-name">Savannah</div>
                    <div class="member-role">Chief Editor</div>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👨</div>
                    <div class="member-name">Bintang</div>
                    <div class="member-role">Community Manager</div>
                </div>
            </div>
        </div>
        <div class="content-section" style="text-align: center;">
            <h2>Kontribusi Anda</h2>
            <p>Sebagai anggota CineScope, kontribusi Anda dalam bentuk ulasan dan diskusi sangat berharga. Mari kita kembangkan komunitas sinema ini bersama-sama!</p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 CineScope. All rights reserved.</p>
        <p>Platform review film terbaik untuk komunitas pecinta sinema Indonesia</p>
    </footer>
</body>
</html>
