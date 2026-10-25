<?php
// terms.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
   <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>CineScope - Syarat dan Ketentuan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===========================
           STYLE CSS UTAMA
        =========================== */
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
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #e63946;
            justify-self: end;
        }

        .back-link {
            color: #ccc;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            padding: 8px 10px;
            border: 1px solid transparent; 
            border-radius: 4px;
            transition: all 0.3s ease;
            justify-self: start;
        }

        .back-link i {
            font-size: 1.1em;
        }

        .back-link:hover {
            color: #e63946;
            border-color: #e63946;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background-color: #1a1a1a;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #e63946;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #222;
            border-radius: 8px;
        }

        .section h2 {
            color: #e63946;
            margin-bottom: 10px;
            font-size: 1.8em;
            border-bottom: 1px dashed #444;
            padding-bottom: 5px;
        }
        
        .section h3 {
            color: #e0e0e0;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        p, li {
            color: #ccc;
        }

        ul, ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        a {
            color: #e63946;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
        
        .contact-info p {
            margin-bottom: 5px;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #0a0a0a;
            border-top: 1px solid #333;
            color: #777;
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
            
            <div class="logo">CineScope</div>
        </div>
    </header>

    <div class="container">
        <h1>Syarat dan Ketentuan</h1>
        
        <p>Dengan mengakses atau menggunakan platform CineScope, Anda menyetujui semua Syarat dan Ketentuan yang dijelaskan di bawah ini. Harap baca dengan seksama.</p>

        <div class="section" id="penerimaan">
            <h2>1. Penerimaan Syarat</h2>
            <p>Syarat dan Ketentuan ini ("Syarat") merupakan perjanjian yang mengikat secara hukum antara Anda dan CineScope. Jika Anda tidak setuju dengan Syarat ini, Anda tidak diperbolehkan menggunakan Platform kami.</p>
        </div>

        <div class="section" id="layanan">
            <h2>2. Layanan yang Disediakan</h2>
            <p>CineScope adalah platform review film yang menyediakan:</p>
            <ul>
                <li>Informasi dan detail film.</li>
                <li>Sistem *rating* dan ulasan dari komunitas.</li>
                <li>Fitur untuk pengguna terdaftar (profil, daftar tonton, posting ulasan).</li>
            </ul>
        </div>
        
        <div class="section" id="akun">
            <h2>3. Akun Pengguna</h2>
            <ul>
                <li>Anda harus berusia minimal 13 tahun untuk membuat akun.</li>
                <li>Anda bertanggung jawab penuh atas kerahasiaan *password* dan aktivitas yang terjadi di bawah akun Anda.</li>
                <li>CineScope berhak menangguhkan atau menghentikan akun jika terdapat aktivitas yang melanggar Syarat ini.</li>
            </ul>
        </div>
        
        <div class="section" id="konten">
            <h2>4. Konten Pengguna (Ulasan & Rating)</h2>
            <p>Anda bertanggung jawab penuh atas semua konten (ulasan, *rating*, komentar) yang Anda publikasikan di Platform. Konten tidak boleh:</p>
            <ol>
                <li>Melanggar hak cipta, merek dagang, atau hak properti intelektual pihak ketiga.</li>
                <li>Mengandung konten ilegal, cabul, mengancam, memfitnah, atau diskriminatif.</li>
                <li>Berisi spam atau promosi komersial yang tidak diminta.</li>
            </ol>
            <p>CineScope memiliki hak untuk menghapus konten yang melanggar tanpa pemberitahuan sebelumnya.</p>
        </div>

        <div class="section" id="hak-cipta">
            <h2>5. Hak Properti Intelektual</h2>
            <p>Semua konten dan elemen desain pada Platform (kecuali konten pengguna) adalah milik CineScope atau pihak lisensornya dan dilindungi oleh undang-undang hak cipta.</p>
        </div>

        <div class="section" id="privasi">
            <h2>6. Kebijakan Privasi</h2>
            <p>Penggunaan Anda atas Platform juga diatur oleh <a href="kebijakan privasi.php">Kebijakan Privasi</a> kami, yang menjelaskan bagaimana kami mengumpulkan dan menggunakan data pribadi Anda.</p>
        </div>

        <div class="section" id="batasan">
            <h2>7. Batasan Tanggung Jawab</h2>
            <p>CineScope tidak menjamin bahwa Platform akan selalu bebas dari kesalahan atau tanpa gangguan. Kami tidak bertanggung jawab atas kerugian atau kerusakan yang timbul dari penggunaan atau ketidakmampuan untuk menggunakan Platform.</p>
        </div>
        
        <div class="section" id="ganti-rugi">
            <h2>8. Ganti Rugi</h2>
            <p>Anda setuju untuk mengganti rugi dan membebaskan CineScope dari setiap klaim atau tuntutan, termasuk biaya pengacara, yang timbul dari pelanggaran Anda terhadap Syarat ini.</p>
        </div>

        <div class="section" id="perubahan">
            <h2>9. Perubahan Syarat</h2>
            <p>Kami berhak mengubah Syarat ini kapan saja. Perubahan akan segera berlaku setelah diposting di halaman ini. Penggunaan berkelanjutan Anda atas Platform berarti Anda menerima Syarat yang telah direvisi.</p>
        </div>

        <div class="section" id="hukum">
            <h2>10. Hukum yang Mengatur</h2>
            <p>Syarat ini akan diatur oleh dan ditafsirkan sesuai dengan hukum yang berlaku di Republik Indonesia.</p>
        </div>

        <div class="section" id="kontak">
            <h2>11. Hubungi Kami</h2>
            <p>Jika Anda memiliki pertanyaan tentang Syarat dan Ketentuan ini, silakan hubungi kami di:</p>
            <div class="contact-info">
                <p><strong>Email:</strong> support@cinescope.com</p>
            </div>
        </div>

        <div class="section">
            <h3>Kesimpulan</h3>
            <p>Terima kasih telah menjadi bagian dari komunitas CineScope!</p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 CineScope. All rights reserved.</p>
        <p>Platform review film terbaik untuk komunitas pecinta sinema Indonesia</p>
    </footer>
</body>
</html>
