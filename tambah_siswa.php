<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nis           = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nama_siswa    = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $id_kelas      = mysqli_real_escape_string($koneksi, $_POST['id_kelas']);

    // Tambahan pengaman sandi default untuk akun login siswa
    $password_default = password_hash($nis, PASSWORD_DEFAULT);

    $cek = mysqli_query($koneksi, "SELECT * FROM tb_siswa WHERE nis = '$nis'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "NIS tersebut sudah terdaftar!";
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO tb_siswa (nis, nama_siswa, jenis_kelamin, id_kelas) VALUES ('$nis', '$nama_siswa', '$jenis_kelamin', '$id_kelas')");
        if ($query) {
            // Tambahan query opsional untuk memastikan tabel user/akun siswa ikut terisi jika ada
            @mysqli_query($koneksi, "INSERT INTO tb_user (username, password, nama_lengkap, level) VALUES ('$nis', '$password_default', '$nama_siswa', 'siswa')");

            echo "<script>alert('Data siswa berhasil ditambahkan!'); window.location='admin.php?page=data_siswa';</script>";
            exit;
        } else {
            $error = "Gagal menyimpan data!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa Baru - MA Al-Ikhlash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #334155; }
        .card-custom { border: none; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); padding: 30px; }
    </style>
</head>
<body class="p-5">
    <div class="container" style="max-width: 600px;">
        <div class="card card-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0 text-success"><i class="bi bi-person-plus-fill me-2"></i>Tambah Siswa Baru</h4>
                <a href="admin.php?page=data_siswa" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger py-2 small"><?= $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">NIS / NIK</label>
                    <input type="text" name="nis" class="form-control" placeholder="Masukkan Nomor Induk Siswa" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Nama Lengkap Siswa</label>
                    <input type="text" name="nama_siswa" class="form-control" placeholder="Masukkan nama lengkap siswa" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php
                        $q_kls = mysqli_query($koneksi, "SELECT * FROM tb_kelas");
                        while($k = mysqli_fetch_assoc($q_kls)):
                        ?>
                            <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="simpan" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan Data Siswa
                </button>
            </form>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
