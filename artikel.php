<?php ?>
<!-- ========================= -->
<!-- BAGIAN ARTIKEL -->
<!-- ========================= -->
 
<section id="artikel" style="padding: 60px; background-color: #111; color: white;">
  <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
   <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Kode Tombol Kembali Saja</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Variabel warna untuk konsistensi */
        :root {
            --primary-color: #e50914; /* Merah CineScope */
            --background-dark: #0a0a0a;
            --text-light: #ffffff;
        }

        /* Hanya style yang terkait tombol kembali */
        .back-link-container {
            margin-bottom: 20px; /* Jarak di bawah tombol */
        }

        .back-link {
            display: inline-flex; /* Membuat ikon dan teks sejajar */
            align-items: center; /* Menyejajarkan secara vertikal di tengah */
            gap: 8px; /* Jarak antara ikon dan teks */
            text-decoration: none; /* Menghilangkan garis bawah tautan */
            color: #ccc; /* Warna teks default */
            font-size: 1rem;
            padding: 8px 15px; /* Padding di sekitar tombol */
            border: 1px solid #333; /* Border abu-abu gelap */
            border-radius: 5px; /* Sudut membulat */
            transition: all 0.3s ease; /* Transisi untuk efek hover */
            background-color: #1a1a1a; /* Latar belakang tombol */
        }

        .back-link i {
            font-size: 1.1rem; /* Ukuran ikon panah */
        }

        /* Efek Hover */
        .back-link:hover {
            color: var(--primary-color); /* Teks berubah menjadi merah */
            background-color: #222; /* Latar belakang sedikit lebih gelap */
            border-color: var(--primary-color); /* Border berubah menjadi merah */
        }

        /* Contoh Style Body agar tombol terlihat */
        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-dark);
            color: var(--text-light);
            padding: 20px;
        }
    </style>
</head>
<body>

    <div class="back-link-container">
        <a href="user.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali 
        </a>
    </div>
</body>
</html>
  <h2 style="text-align: center; font-size: 2em; margin-bottom: 30px;">Artikel & Berita Film</h2>
  <p style="text-align: center; color: #bbb; margin-bottom: 40px;">
    Dapatkan berita terbaru, ulasan, dan wawasan menarik dari dunia perfilman.
  </p>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
    
    <!-- Artikel 1 -->
    <div style="background-color: #222; border-radius: 10px; overflow: hidden;">
      <img src="https://static1.srcdn.com/wordpress/wp-content/uploads/2023/05/oppenheimer-poster.jpg" alt="Oppenheimer" style="width:100%;">
      <div style="padding: 20px;">
        <h3>Oppenheimer: Film Epik tentang Sang Bapak Bom Atom</h3>
        <p style="color:#bbb; font-size: 14px;">
          Disutradarai oleh Christopher Nolan, film ini menyoroti konflik moral dan ilmiah di balik penciptaan bom atom. 
          Visual dan narasi yang mendalam membuatnya jadi kandidat kuat Oscar 2025.
        </p>
        <a href="artikel 1.html" style="color:#e50914; text-decoration:none;">Baca Selengkapnya →</a>
      </div>
    </div>

    <!-- Artikel 2 -->
    <div style="background-color: #222; border-radius: 10px; overflow: hidden;">
      <img src="https://image.tmdb.org/t/p/w780/sv1xJUazXeYqALzczSZ3O6nkH75.jpg" alt="Black Panther" style="width:100%;">
      <div style="padding: 20px;">
        <h3>Black Panther: Warisan Budaya dan Kekuatan Sinematik</h3>
        <p style="color:#bbb; font-size: 14px;">
          Sekuel Wakanda Forever bukan sekadar aksi superhero, tetapi juga penghormatan emosional pada Chadwick Boseman dan nilai-nilai Afrika yang kuat.
        </p>
        <a href="artikel 2.html" style="color:#e50914; text-decoration:none;">Baca Selengkapnya →</a>
      </div>
    </div>

    <!-- Artikel 3 -->
    <div style="background-color: #222; border-radius: 10px; overflow: hidden;">
      <img src="https://tse3.mm.bing.net/th/id/OIP.epjFJueo-VyGiI7iM_P7OQHaLG?cb=12&rs=1&pid=ImgDetMain&o=7&rm=3" alt="Inside Out 2" style="width:100%;">
      <div style="padding: 20px;">
        <h3>Inside Out 2: Petualangan Emosi yang Semakin Dalam</h3>
        <p style="color:#bbb; font-size: 14px;">
          Pixar kembali menghadirkan kisah penuh makna dengan memperkenalkan emosi baru seperti Cemas dan Iri, membuat film ini semakin relevan dengan remaja masa kini.
        </p>
        <a href="artikel 3.html" style="color:#e50914; text-decoration:none;">Baca Selengkapnya →</a>
      </div>
    </div>

  </div>
</section>
