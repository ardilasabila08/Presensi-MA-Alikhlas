<?php
// Pastikan session sudah aktif dan koneksi terhubung
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Buat folder 'uploads' otomatis jika belum ada
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Proses Upload Foto Jadwal Sekolah
if (isset($_POST['upload_foto'])) {
    $nama_file = $_FILES['foto_jadwal']['name'];
    $tmp_file = $_FILES['foto_jadwal']['tmp_name'];
    $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    
    $ekstensi_diizinkan = array('jpg', 'jpeg', 'png', 'pdf');

    if (in_array($ekstensi, $ekstensi_diizinkan)) {
        // Beri nama unik agar tidak bentrok
        $nama_baru = 'jadwal_sekolah_' . time() . '.' . $ekstensi;
        if (move_uploaded_file($tmp_file, 'uploads/' . $nama_baru)) {
            file_put_contents('uploads/info_jadwal.txt', $nama_baru);
            echo "<script>alert('Foto jadwal berhasil diunggah!'); window.location='admin.php?page=jadwal';</script>";
        } else {
            echo "<script>alert('Gagal mengunggah file!');</script>";
        }
    } else {
        echo "<script>alert('Ekstensi file tidak diizinkan! Harap upload JPG, JPEG, PNG, atau PDF.');</script>";
    }
}

// Proses Hapus Foto Jadwal
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus_foto') {
    if (file_exists('uploads/info_jadwal.txt')) {
        $foto_lama = trim(file_get_contents('uploads/info_jadwal.txt'));
        if (file_exists('uploads/' . $foto_lama)) {
            unlink('uploads/' . $foto_lama);
        }
        unlink('uploads/info_jadwal.txt');
    }
    echo "<script>alert('Foto jadwal berhasil dihapus!'); window.location='admin.php?page=jadwal';</script>";
    exit;
}

// Ambil data foto jadwal yang pernah di-upload
$foto_aktif = file_exists('uploads/info_jadwal.txt') ? trim(file_get_contents('uploads/info_jadwal.txt')) : '';
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 fw-bold text-dark">Kelola Jadwal Pelajaran Sekolah</h2>
    <p class="text-muted">Unggah foto atau dokumen jadwal pelajaran resmi yang akan otomatis tampil di halaman akun setiap guru.</p>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3 text-primary"><i class="bi bi-file-earmark-image me-2"></i>Upload Jadwal Pelajaran</h5>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Pilih File Foto/Gambar Jadwal (JPG, JPEG, PNG)</label>
                            <input type="file" name="foto_jadwal" class="form-control bg-light py-2" required>
                        </div>
                        <button type="submit" name="upload_foto" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-upload me-1"></i> Simpan & Perbarui Jadwal
                        </button>
                    </form>

                    <hr class="my-4">

                    <h5 class="fw-bold text-secondary mb-3 text-center"><i class="bi bi-eye me-2"></i>Preview Jadwal yang Sedang Aktif</h5>
                    <?php if (!empty($foto_aktif) && file_exists('uploads/' . $foto_aktif)): ?>
                        <div class="text-center mt-3 bg-light p-3 rounded border">
                            <a href="lihat_foto.php?file=<?= $foto_aktif; ?>">
                                <img src="uploads/<?= $foto_aktif; ?>" alt="Jadwal Sekolah" class="img-fluid rounded border shadow-sm" style="max-height: 400px;">
                            </a>
                            <p class="small text-muted mt-2 mb-2">Klik gambar untuk melihat ukuran penuh</p>
                            
                            <!-- Tombol Hapus -->
                            <a href="admin.php?page=jadwal&aksi=hapus_foto" class="btn btn-danger btn-sm px-3 fw-bold mt-2" onclick="return confirm('Yakin ingin menghapus foto jadwal ini?')">
                                <i class="bi bi-trash me-1"></i> Hapus Foto Jadwal
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i> Belum ada foto jadwal sekolah yang diunggah. Silakan upload terlebih dahulu di atas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>