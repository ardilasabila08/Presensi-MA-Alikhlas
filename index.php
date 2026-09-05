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
    <meta name="theme-color" content="#0b4f30">
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

    /* Tambahkan ini agar tampilan login di HP mengecil utuh seperti di laptop */
  @media (max-width: 768px) {
        /* Mengatur agar latar belakang menyesuaikan layar penuh */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 15px;
        }
        /* Mengunci ukuran kotak login agar pas dan berada tepat di tengah HP */
        .login-card {
            width: 100% !important;
            max-width: 400px !important;
            margin: 0 auto !important;
        }
    }
    </style>
    <!-- Tambahkan kode ini di sini -->
    <script>
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js');
      }
    </script>
</head>
<body>

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

                <button type="submit" name="login" class="btn btn-custom w-100 shadow-sm mb-3">
                    Masuk Sistem <i class="bi bi-arrow-right ms-1"></i>
                </button>

                <!-- Tambahan Teks Bantuan Lupa Password ke WhatsApp Admin -->
                <div class="text-center">
                    <p class="text-muted small mb-0">
                        Lupa password akun? 
                        <a href="https://wa.me/6281572517170?text=Halo%20Admin,%20saya%20lupa%20password%20akun%20presensi%20saya.%20Mohon%20bantuannya%20untuk%20reset." 
   target="_blank" 
   class="text-success fw-bold text-decoration-none">
    <i class="bi bi-whatsapp"></i> Hubungi Admin
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
