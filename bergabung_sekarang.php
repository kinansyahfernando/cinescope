<?php ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Daftar - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #e50914;
            --background-dark: #1a1a1a;
            --card-dark: rgba(0, 0, 0, 0.85);
            --text-light: #ffffff;
            --text-muted: #ccc;
            --input-bg: #222;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-dark) 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-light);
        }

        .auth-container {
            width: 90%;
            max-width: 600px;
            background-color: var(--card-dark);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .auth-header h2 {
            margin-bottom: 20px;
            text-align: center;
            color: var(--primary-color);
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input {
            width: 100%;
            padding: 12px;
            background-color: var(--input-bg);
            border: 1px solid #333;
            border-radius: 5px;
            color: var(--text-light);
            font-size: 1em;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: var(--text-light);
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .register-btn:hover {
            background-color: #b8070f;
        }

        .alert-success {
            display: <?= isset($_GET['success']) ? 'block' : 'none' ?>;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #0b3a1d;
            color: var(--success-color);
            border: 1px solid var(--success-color);
            text-align: center;
        }

        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 10px;
            display: <?= isset($_GET['error']) ? 'block' : 'none' ?>;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: #e50914;
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: #e50914;
            padding: 8px 14px;
            border-radius: 6px;
            color: #ffffff;
            font-size: 0.9em;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #b8070f;
        }
    </style>
</head>
<body>

<a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>

<main class="auth-container">

    <div class="auth-header">
        <h2>Daftar Akun CineScope</h2>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">Pendaftaran berhasil! Anda akan diarahkan ke halaman login.</div>
        <script>
            setTimeout(() => { window.location.href = "login.php"; }, 2000);
        </script>
    <?php endif; ?>

    <form action="register_action.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label>Username</label>
            <input 
                required 
                type="text" 
                name="username" 
                placeholder="Nama pengguna"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input 
                required 
                type="email" 
                name="email" 
                placeholder="Email"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input 
                required 
                type="password" 
                name="password" 
                placeholder="Minimal 6 karakter"
                autocomplete="new-password">
        </div>

        <button type="submit" class="register-btn">
            <i class="fas fa-user-plus"></i> Gabung Sekarang
        </button>
    </form>

    <div class="form-footer">
        <p>Sudah punya akun? <a href="login.php">Masuk</a></p>
    </div>
</main>

</body>
</html>