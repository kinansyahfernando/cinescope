<?php
// privacy.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
   <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>CineScope - Kebijakan Privasi</title>
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
        <h1>Kebijakan Privasi</h1>
        <p>Berlaku efektif sejak 23 Oktober 2024</p>
        
        <div class="section" id="pengantar">
            <h2>1. Pengantar</h2>
            <p>Kebijakan Privasi ini menjelaskan bagaimana CineScope ("kami," "milik kami," atau "Platform") mengumpulkan, menggunakan, mengungkapkan, dan melindungi informasi pribadi Anda ("Informasi Pribadi") ketika Anda menggunakan layanan kami, termasuk situs web, aplikasi, dan layanan terkait lainnya.</p>
        </div>

        <div class="section" id="informasi-yang-kami-kumpulkan">
            <h2>2. Informasi yang Kami Kumpulkan</h2>
            <h3>Informasi yang Anda Berikan Secara Langsung:</h3>
            <ul>
                <li><strong>Informasi Pendaftaran:</strong> Nama, username, alamat email, dan password terenkripsi saat Anda mendaftar akun.</li>
                <li><strong>Konten Pengguna:</strong> Ulasan, rating, komentar, dan preferensi yang Anda posting di Platform.</li>
                <li><strong>Komunikasi:</strong> Korespondensi yang Anda kirimkan kepada kami (misalnya, melalui email atau formulir kontak).</li>
            </ul>
            <h3>Informasi yang Dikumpulkan Secara Otomatis:</h3>
            <ul>
                <li><strong>Data Penggunaan:</strong> Halaman yang Anda kunjungi, film yang Anda cari, waktu yang dihabiskan di Platform, dan interaksi Anda dengan layanan kami.</li>
                <li><strong>Informasi Perangkat:</strong> Jenis perangkat, sistem operasi, alamat IP, dan data log browser.</li>
            </ul>
        </div>
        
        <div class="section" id="penggunaan-informasi">
            <h2>3. Bagaimana Kami Menggunakan Informasi Anda</h2>
            <p>Kami menggunakan Informasi Pribadi Anda untuk tujuan-tujuan berikut:</p>
            <ul>
                <li>Untuk menyediakan dan memelihara layanan kami, termasuk mengelola akun Anda.</li>
                <li>Untuk mempersonalisasi pengalaman Anda, seperti menyarankan film atau ulasan yang relevan.</li>
                <li>Untuk mempublikasikan ulasan dan *rating* yang Anda kirimkan (dengan nama pengguna Anda).</li>
                <li>Untuk berkomunikasi dengan Anda mengenai pembaruan layanan, Syarat, atau Kebijakan.</li>
                <li>Untuk menganalisis penggunaan dan meningkatkan kualitas Platform dan fitur-fitur baru.</li>
            </ul>
        </div>
        
        <div class="section" id="pengungkapan">
            <h2>4. Pengungkapan Informasi</h2>
            <p>Kami tidak menjual Informasi Pribadi Anda kepada pihak ketiga. Kami dapat membagikan informasi Anda dalam situasi berikut:</p>
            <ul>
                <li><strong>Dengan Persetujuan Anda:</strong> Kami dapat mengungkapkan informasi Anda untuk tujuan lain dengan persetujuan Anda.</li>
                <li><strong>Penyedia Layanan:</strong> Kami dapat mempekerjakan perusahaan dan individu pihak ketiga untuk memfasilitasi layanan kami (misalnya, *hosting*). Mereka hanya memiliki akses ke Informasi Pribadi Anda sejauh yang diperlukan untuk menjalankan tugas-tugas tersebut.</li>
                <li><strong>Kewajiban Hukum:</strong> Jika diwajibkan oleh hukum, peraturan, atau proses hukum yang sah.</li>
            </ul>
        </div>

        <div class="section" id="keamanan">
            <h2>5. Keamanan Data</h2>
            <p>Kami mengambil langkah-langkah keamanan teknis dan organisasi yang wajar untuk melindungi Informasi Pribadi Anda dari akses, pengungkapan, atau modifikasi yang tidak sah. Namun, tidak ada metode transmisi melalui Internet yang 100% aman.</p>
        </div>
        
        <div class="section" id="hak-anda">
            <h2>6. Hak-Hak Privasi Anda</h2>
            <p>Anda memiliki hak untuk mengakses, memperbaiki, menghapus, atau membatasi penggunaan Informasi Pribadi Anda. Untuk menggunakan hak-hak ini, silakan hubungi kami melalui email yang tertera di bagian kontak.</p>
        </div>

        <div class="section" id="cookie">
            <h2>7. Cookie dan Teknologi Pelacakan</h2>
            <p>Kami menggunakan *cookie* dan teknologi pelacakan serupa untuk melacak aktivitas di Platform kami dan menyimpan informasi tertentu. Anda dapat menginstruksikan *browser* Anda untuk menolak semua *cookie* atau untuk menunjukkan kapan *cookie* sedang dikirim.</p>
        </div>

        <div class="section" id="link-pihak-ketiga">
            <h2>8. Tautan Pihak Ketiga</h2>
            <p>Platform kami mungkin berisi tautan ke situs web pihak ketiga yang tidak dioperasikan oleh kami. Kami tidak bertanggung jawab atas konten atau praktik privasi situs pihak ketiga mana pun.</p>
        </div>

        <div class="section" id="kontak">
            <h2>9. Hubungi Kami</h2>
            <p>Jika Anda memiliki pertanyaan atau kekhawatiran tentang Kebijakan Privasi kami, atau ingin menggunakan hak-hak privasi Anda, silakan hubungi kami:</p>
            <div class="contact-info">
                <p><strong>Email:</strong> privacy@cinescope.com</p>
                <p><strong>Alamat:</strong> Jl. Film Indonesia No. 123, Jakarta Selatan 12345, Indonesia</p>
                <p><strong>Telepon:</strong> +62-21-XXXX-XXXX</p>
                <p><strong>Form Kontak:</strong> Silakan kunjungi halaman Kontak kami untuk mengirimkan pertanyaan</p>
            </div>
        </div>

        <div class="section">
            <h3>Perubahan Kebijakan Privasi</h3>
            <p>Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu untuk mencerminkan perubahan praktik kami atau peraturan. Kami akan memberitahu Anda tentang perubahan signifikan melalui email atau pemberitahuan di situs. Penggunaan berkelanjutan Anda terhadap layanan kami setelah perubahan tersebut berarti Anda menerima pembaruan kebijakan.</p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 CineScope. All rights reserved.</p>
        <p>Platform review film terbaik untuk komunitas pecinta sinema Indonesia</p>
    </footer>
</body>
</html>
