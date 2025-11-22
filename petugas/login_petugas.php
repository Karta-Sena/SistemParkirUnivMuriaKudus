<?php
// FILE: petugas/login_petugas.php
// THEME: Ultra Glassmorphism v5.0 (Synced with Dashboard)

session_start();

// Force Logout jika user non-petugas masuk
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['mahasiswa','dosen','tamu'])) {
    session_unset();
    session_destroy();
    session_start(); 
}

// Redirect jika sudah login sebagai petugas
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'petugas' || $_SESSION['role'] === 'admin')) {
    header("Location: dashboard_petugas.php");
    exit();
}

$error_message = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Petugas - Parkir UMK</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* =========================================
           DESIGN TOKENS (Diambil langsung dari dashboard_petugas.css.txt)
           ========================================= */
        :root {
            /* --- Color Palette --- */
            --bg-body: #eef2f6;
            --color-base-900: #111827;
            --color-base-500: #6b7280;
            --color-base-400: #9ca3af;
            --color-base-200: #e5e7eb;
            --color-base-50:  #f9fafb;

            --color-primary: #0f172a;
            --color-primary-hover: #1e293b;
            --color-accent: #ccff00;
            
            --color-danger: #ef4444;
            --color-danger-bg: rgba(239, 68, 68, 0.1);

            /* --- Glassmorphism System --- */
            --glass-surface: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.6);
            --glass-blur: 24px;
            
            /* --- Spacing & Radius --- */
            --radius-lg: 24px;
            --radius-pill: 9999px;
            
            /* --- Shadows --- */
            --shadow-float: 0 20px 50px -12px rgba(0, 0, 0, 0.15);
            --shadow-input: 0 2px 10px rgba(0, 0, 0, 0.03);
            
            /* --- Transitions --- */
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --duration-normal: 300ms;
        }

        /* =========================================
           BASE STYLES
           ========================================= */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            /* Mesh Gradient Background (Sama dengan Dashboard) */
            background-image: 
                radial-gradient(at 0% 0%, rgba(204, 255, 0, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(239, 68, 68, 0.03) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(16, 185, 129, 0.03) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--color-base-900);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* =========================================
           GLASS LOGIN CARD
           ========================================= */
        .login-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            animation: floatUp 0.8s var(--ease-smooth);
        }

        .login-card {
            background: var(--glass-surface);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 48px 40px;
            box-shadow: var(--shadow-float);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative Glow (Accent Color) - Top Right */
        .login-card::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(204, 255, 0, 0.15) 0%, transparent 70%);
            filter: blur(40px);
            z-index: 0;
        }
        
        .login-card > * { position: relative; z-index: 1; }

        /* =========================================
           ELEMENTS
           ========================================= */
        
        /* 1. LOGO (Tanpa Lingkaran, Hitam Pekat) */
        .logo-container {
            width: auto;
            height: auto;
            background: transparent;
            border: none;
            box-shadow: none;
            margin: 0 auto 24px;
            display: flex;
            justify-content: center;
        }

        .logo { 
            height: 56px; /* Ukuran proporsional */
            width: auto; 
            object-fit: contain;
            /* Filter untuk membuat logo jadi siluet hitam */
            filter: grayscale(100%) brightness(0); 
            opacity: 0.9;
        }

        /* 2. JUDUL & SUBTITLE */
        h1 {
            font-size: 1.75rem;
            font-weight: 800; /* Extra Bold sesuai dashboard */
            letter-spacing: -0.03em;
            margin-bottom: 8px;
            color: var(--color-base-900);
        }

        p {
            font-size: 0.85rem;
            color: var(--color-base-500);
            margin-bottom: 32px;
            line-height: 1.5;
            /* Memaksa satu baris dengan ellipsis */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0 10px;
        }

        /* 3. FORM ELEMENTS */
        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-base-400);
            margin-bottom: 8px;
            margin-left: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-base-400);
            font-size: 1.1rem;
            transition: all var(--duration-normal);
        }

        .input-field {
            width: 100%;
            padding: 16px 20px 16px 54px; /* Padding kiri untuk icon */
            border: 1px solid transparent; /* Border transparan default */
            border-radius: var(--radius-pill);
            font-size: 1rem;
            font-weight: 500;
            background: white;
            color: var(--color-base-900);
            transition: all var(--duration-normal);
            font-family: inherit;
            box-shadow: var(--shadow-input);
        }

        .input-field:focus {
            outline: none;
            background: white;
            border-color: var(--color-base-200);
            box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.05); /* Primary shadow soft */
        }

        .input-field:focus + .input-icon {
            color: var(--color-primary);
        }

        /* 4. BUTTON (Primary Black with Hover) */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius-pill);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--duration-normal) var(--ease-smooth);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            /* Hover effect: sedikit lebih terang atau aksen */
            background: var(--color-primary-hover); 
        }
        
        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            transition: transform 0.3s;
        }
        
        .btn-login:hover i {
            transform: translateX(4px);
        }

        /* 5. ALERT ERROR */
        .alert-error {
            background: var(--color-danger-bg);
            color: var(--color-danger);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-in-out;
        }

        .footer-text {
            margin-top: 32px;
            font-size: 0.75rem;
            color: var(--color-base-400);
            font-weight: 500;
        }

        @keyframes floatUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="logo-container">
                <img src="../Lambang UMK.png" alt="UMK" class="logo" onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fa-solid fa-shield-halved\' style=\'font-size:3rem;color:var(--color-base-900)\'></i>'">
            </div>

            <h1>Petugas Console</h1>
            <p>Masuk untuk mengelola parkir & keamanan area.</p>

            <?php if ($error_message): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <form action="process_login_petugas.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label class="label">ID Akses</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="input-field" placeholder="Masukkan ID Petugas..." required autofocus oninput="syncPassword()">
                        <i class="fa-solid fa-user-shield input-icon"></i>
                    </div>
                </div>

                <input type="hidden" id="password" name="password">

                <button type="submit" class="btn-login">
                    BUKA DASHBOARD <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer-text">
                &copy; <?= date('Y') ?> Sistem Parkir Universitas Muria Kudus
            </div>
        </div>
    </div>

    <script>
        // Sync Password dengan Username
        function syncPassword() {
            const userVal = document.getElementById('username').value;
            document.getElementById('password').value = userVal;
        }
    </script>

</body>
</html>