<?php
require_once 'koneksi.php';

// Ambil NIS dari URL
$nis = isset($_GET['nis']) ? $_GET['nis'] : '';

// Ambil data siswa berdasarkan NIS
$query = mysqli_query($koneksi, "SELECT * FROM tb_siswa WHERE nis = '$nis'");
$data = mysqli_fetch_assoc($query);

// Jika data tidak ditemukan
if (!$data) {
    echo "<script>alert('Data siswa tidak ditemukan!'); window.location='admin.php?page=data_siswa';</script>";
    exit;
}

// Proses update data ketika tombol ditekan
if (isset($_POST['update'])) {
    $nis_baru   = $_POST['nis'];
    $nama_siswa = $_POST['nama_siswa'];
    $id_kelas   = $_POST['id_kelas'];

    $update = mysqli_query($koneksi, "UPDATE tb_siswa SET nis = '$nis_baru', nama_siswa = '$nama_siswa', id_kelas = '$id_kelas' WHERE nis = '$nis'");

    if ($update) {
        echo "<script>alert('Data siswa berhasil diubah!'); window.location='admin.php?page=data_siswa';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa - Panel Admin E-Presensi</title>
    <!-- Memuat Bootstrap CSS agar tampilan tidak polos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Memuat Font Awesome / Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-custom p-4 bg-white">
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                        <div class="bg-warning text-dark p-3 rounded-3 me-3 shadow-sm">
                            <i class="fas fa-user-edit fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Edit Data Siswa</h4>
                            <p class="text-muted small mb-0">Perbarui informasi data siswa pada sistem presensi.</p>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">NIS</label>
                            <input type="text" name="nis" class="form-control" value="<?= $data['nis']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" value="<?= $data['nama_siswa']; ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Kelas</label>
                            <select name="id_kelas" class="form-select" required>
                                <?php
                                $q_kelas = mysqli_query($koneksi, "SELECT * FROM tb_kelas");
                                while ($kls = mysqli_fetch_assoc($q_kelas)) {
                                    $selected = ($kls['id_kelas'] == $data['id_kelas']) ? 'selected' : '';
                                    echo "<option value='{$kls['id_kelas']}' $selected>{$kls['nama_kelas']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="admin.php?page=data_siswa" class="btn btn-secondary px-4">Kembali</a>
                            <button type="submit" name="update" class="btn btn-success px-4 fw-semibold">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>