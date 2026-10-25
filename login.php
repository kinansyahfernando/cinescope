<?php
session_start();

// Cek jika ada username dari registrasi baru
$welcomeUsername = isset($_GET['welcome']) ? htmlspecialchars($_GET['welcome']) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.35, user-scalable=yes">
    <title>Masuk - CineScope</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #e50914;
            --background-dark: #1a1a1a;
            --card-dark: rgba(0, 0, 0, 0.85);
            --text-light: #ffffff;
            --text-muted: #ccc;
            --error-color: #e74c3c;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
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
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input {
            width: 100%;
            padding: 12px;
            background: #222;
            border: 1px solid #444;
            color: white;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: #b8070f;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .error-box {
            background: #3b0000;
            border-left: 4px solid var(--error-color);
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            color: var(--error-color);
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

        /* Welcome Popup Styles */
        .welcome-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 40px 60px;
            border-radius: 15px;
            box-shadow: 0 10px 50px rgba(229, 9, 20, 0.5);
            z-index: 9999;
            text-align: center;
            border: 2px solid #e50914;
            animation: popupSlideIn 0.5s ease;
            display: none;
        }

        @keyframes popupSlideIn {
            from {
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        @keyframes popupSlideOut {
            from {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
            to {
                transform: translate(-50%, -40%);
                opacity: 0;
            }
        }

        .welcome-popup.hide {
            animation: popupSlideOut 0.5s ease forwards;
        }

        .welcome-popup i {
            font-size: 4em;
            color: #4CAF50;
            margin-bottom: 20px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .welcome-popup h2 {
            font-size: 2em;
            margin-bottom: 10px;
            color: #e50914;
        }

        .welcome-popup p {
            font-size: 1.2em;
            color: #ccc;
        }

        .welcome-popup .username {
            color: #fff;
            font-weight: 700;
            font-size: 1.4em;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9998;
            display: none;
        }
    </style>
</head>
<body>

<!-- Welcome Popup -->
<?php if ($welcomeUsername): ?>
<div class="popup-overlay" id="popupOverlay"></div>
<div class="welcome-popup" id="welcomePopup">
    <i class="fas fa-check-circle"></i>
    <h2>SELAMAT DATANG</h2>
    <p class="username"><?= $welcomeUsername ?></p>
    <p style="margin-top: 10px; font-size: 0.9em; color: #999;">Silakan login untuk melanjutkan</p>
</div>

<script>
    // Show popup
    window.addEventListener('DOMContentLoaded', () => {
        const popup = document.getElementById('welcomePopup');
        const overlay = document.getElementById('popupOverlay');
        
        popup.style.display = 'block';
        overlay.style.display = 'block';

        // Hide popup after 5 seconds
        setTimeout(() => {
            popup.classList.add('hide');
            setTimeout(() => {
                popup.style.display = 'none';
                overlay.style.display = 'none';
            }, 500);
        }, 5000);
    });
</script>
<?php endif; ?>

<a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>

<main class="auth-container">
    <h2 style="text-align:center;color:#e50914;margin-bottom:20px;">Masuk ke CineScope</h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-box"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form method="POST" action="login_action.php" autocomplete="off">
        <!-- Hidden dummy fields untuk trick browser -->
        <input type="text" name="dummy_email" style="position:absolute;top:-9999px;left:-9999px;" tabindex="-1">
        <input type="password" name="dummy_password" style="position:absolute;top:-9999px;left:-9999px;" tabindex="-1">
        
        <div class="form-group">
            <label>Email</label>
            <input 
                required 
                type="text"
                name="email" 
                id="email"
                placeholder="Masukkan Email"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                readonly
                onfocus="this.removeAttribute('readonly');">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input 
                required 
                type="password" 
                name="password"
                id="password" 
                placeholder="Masukkan Password"
                autocomplete="new-password"
                readonly
                onfocus="this.removeAttribute('readonly');">
        </div>

        <button class="login-btn" type="submit">
            <i class="fas fa-sign-in-alt"></i> Masuk
        </button>
    </form>

    <script>
        // Force clear all inputs on page load
        window.addEventListener('load', function() {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
        });

        // Clear inputs on page show (back/forward navigation)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('email').value = '';
                document.getElementById('password').value = '';
            }
        });

        // Prevent browser from caching form data
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

    <div class="form-footer">
        <p>Belum punya akun? <a href="bergabung_sekarang.php">Daftar Sekarang</a></p>
    </div>
</main>

</body>
</html>