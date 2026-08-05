<?php
session_start();
require_once 'koneksi.php';

// Proses login tetap asli milik Anda
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Cek data user di database
    $query = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE username='$username' AND password='$password'");
    
    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        
        // Simpan data ke session
        $_SESSION['id_user']      = $user['id_user'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['level']        = $user['level'];
        $_SESSION['id_kelas']     = $user['id_kelas'];

        // Alihkan halaman berdasarkan level hak aksesnya
        if ($user['level'] == 'admin') {
            header("Location: admin.php"); // Masuk ke panel rekap admin
            exit;
        } else {
            header("Location: input_presensi.php"); // Masuk ke form absen guru/petugas
            exit;
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Presensi MA Al-Ikhlas Cicalengka</title>
    
    <!-- Meta tag agar web bisa dikenali sebagai Aplikasi HP (PWA) -->
    <meta name="theme-color" content="#0b4f30">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-Presensi Al-Ikhlas">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #0b4f30; 
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden;
        }

        /* Splash Screen Ala Vercel */
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0b4f30 0%, #042f1c 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            z-index: 9999;
            transition: opacity 0.6s ease, visibility 0.6s ease;
            cursor: pointer;
            text-align: center;
            padding: 20px;
        }

        .splash-icon {
            font-size: 65px;
            color: #ffc107;
            margin-bottom: 15px;
            animation: pulse-icon 1.5s infinite alternate;
        }

        @keyframes pulse-icon {
            0% { transform: scale(1); }
            100% { transform: scale(1.08); }
        }

        .splash-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .splash-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Penyesuaian Card Login khusus HP */
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: none;
            margin: 15px;
        }

        @media (max-width: 576px) {
            body {
                background-color: #ffffff;
            }
            .login-card {
                box-shadow: none;
                border-radius: 0;
                height: 100%;
                max-width: 100%;
                margin: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }

        .login-header {
            background-color: #0b4f30;
            color: white;
            text-align: center;
            padding: 30px 20px 20px 20px;
        }
        .login-body {
            padding: 30px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #10b981;
        }
        .btn-custom {
            background-color: #10b981;
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-custom:hover {
            background-color: #059669;
            color: white;
        }
    </style>
</head>
<body>

    <!-- SPLASH SCREEN -->
    <div id="splash-screen" onclick="openApp()">
        <div class="splash-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="splash-title">MA Al-Ikhlas Cicalengka</div>
        <div class="splash-subtitle">Sistem Kehadiran Siswa Digital</div>
        <div class="small text-white-50 mt-4">Ketuk untuk masuk...</div>
    </div>

    <!-- KARTU LOGIN ASLI -->
    <div class="card login-card">
        <div class="login-header">
            <i class="bi bi-mortarboard-fill fs-1 text-warning mb-2 d-block"></i>
            <h5 class="fw-bold mb-1">MA AL IKHLAS CICALENGKA</h5>
            <p class="text-white-50 small mb-0">Sistem Kehadiran Siswa Digital</p>
        </div>

        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger py-2 small text-center mb-3"><?= $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <!-- Input Username -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="username" class="form-control bg-light border-start-0" placeholder="Masukkan username" required>
                    </div>
                </div>

                <!-- Input Password dengan Tombol Mata (Show/Hide) -->
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="passwordInput" class="form-control bg-light border-start-0 border-end-0" placeholder="********" required>
                        <button class="btn btn-light bg-light border border-start-0 text-muted" type="button" id="togglePassword">
                            <i class="bi bi-eye-slash" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-custom w-100 shadow-sm">
                    Masuk Sistem <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fungsi Transisi Splash Screen
    function openApp() {
        const splash = document.getElementById('splash-screen');
        splash.style.opacity = '0';
        setTimeout(() => {
            splash.style.display = 'none';
        }, 600);
    }

    setTimeout(openApp, 2000);

    // Skrip Tombol Mata (Show/Hide Password) Asli Anda
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        if (type === 'text') {
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        } else {
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        }
    });
</script>
</body>
</html>