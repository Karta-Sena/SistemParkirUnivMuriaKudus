<?php
// FILE: petugas/login_petugas.php
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'petugas') {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #E85D3B;
            --bg-color: #F1F5F9;
            --text-main: #1E293B;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 360px;
            text-align: center;
        }
        .logo { height: 50px; margin-bottom: 20px; }
        h1 { font-size: 1.25rem; color: var(--text-main); margin-bottom: 8px; }
        p { font-size: 0.875rem; color: #64748B; margin-bottom: 24px; }
        
        .input-group { margin-bottom: 16px; text-align: left; }
        .label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; }
        
        .input-field {
            width: 100%;
            padding: 12px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center; /* Center text biar kayak PIN */
            letter-spacing: 1px;
        }
        .input-field:focus { border-color: var(--primary); outline: none; }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-login:hover { background: #d64d2e; }

        .alert-error {
            background: #FEE2E2; color: #EF4444;
            padding: 10px; border-radius: 6px;
            font-size: 0.8rem; margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <img src="../Lambang UMK.png" alt="Logo" class="logo" onerror="this.style.display='none'">
        <h1>Akses Petugas</h1>
        <p>Masukkan ID Petugas untuk memulai sesi.</p>

        <?php if ($error_message): ?>
            <div class="alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="process_login_petugas.php" method="POST" autocomplete="off">
            <div class="input-group">
                <label class="label">ID PETUGAS</label>
                <input type="text" id="username" name="username" class="input-field" placeholder="Contoh: petugas" required autofocus oninput="syncPassword()">
            </div>

            <input type="hidden" id="password" name="password">

            <button type="submit" class="btn-login">BUKA CONSOLE</button>
        </form>
    </div>

    <script>
        // Script untuk menyamakan Password dengan Username secara otomatis
        function syncPassword() {
            const userVal = document.getElementById('username').value;
            document.getElementById('password').value = userVal;
        }
    </script>

</body>
</html>