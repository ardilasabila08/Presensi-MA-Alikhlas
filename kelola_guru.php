<?php
// 1. Proses Tambah Petugas/Guru ke tb_user
if (isset($_POST['tambah_guru'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $level = 'petugas'; // Diset sebagai petugas/guru agar bisa masuk ke input_presensi.php

    $query = "INSERT INTO tb_user (username, password, nama_lengkap, level) VALUES ('$username', '$password', '$nama_lengkap', '$level')";
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data berhasil ditambahkan!'); window.location='admin.php?page=kelola_guru';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data!');</script>";
    }
}

// 2. Proses Edit Petugas/Guru
if (isset($_POST['edit_guru'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    if (!empty($password)) {
        $query_edit = "UPDATE tb_user SET username='$username', nama_lengkap='$nama_lengkap', password='$password' WHERE id_user='$id'";
    } else {
        $query_edit = "UPDATE tb_user SET username='$username', nama_lengkap='$nama_lengkap' WHERE id_user='$id'";
    }

    if (mysqli_query($koneksi, $query_edit)) {
        echo "<script>alert('Data berhasil diubah!'); window.location='admin.php?page=kelola_guru';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
}

// 3. Proses Hapus Petugas/Guru
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    mysqli_query($koneksi, "DELETE FROM tb_user WHERE id_user = '$id'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='admin.php?page=kelola_guru';</script>";
    exit;
}

$modal_array = [];
?>

<div class="container-fluid px-4">
    <div class="card card-custom p-4 mb-4">
        <h4 class="fw-bold mb-3 text-success"><i class="bi bi-person-plus-fill me-2"></i> Kelola Data Guru & Petugas</h4>
        
        <!-- Form Tambah Petugas -->
        <form action="" method="POST" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Username / NIP:</label>
                <input type="text" name="username" class="form-control" placeholder="Contoh: budi123" required>
            </div>
           
            <div class="col-md-4">
                <label class="form-label fw-semibold">Nama Lengkap & Gelar:</label>
                <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Sudarsono, S.Pd." required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Password:</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password akun" required>
            </div>

            <div class="col-12">
                <button type="submit" name="tambah_guru" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Simpan Data Guru
                </button>
            </div>
        </form>

        <!-- Tabel Daftar Petugas -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-success">
                    <tr>
                        <th width="5%">NO</th>
                        <th>USERNAME</th>
                        <th>NAMA LENGKAP</th>
                        <th width="20%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    // Mengambil data dari tb_user khusus level petugas/guru
                    $data_guru = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE level != 'admin'");
                    while ($row = mysqli_fetch_assoc($data_guru)) :
                        $id_val = $row['id_user'];
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="text-center">
                            <!-- Tombol Edit Manual -->
                            <button type="button" class="btn btn-warning btn-sm text-white btn-edit-manual" data-id="<?= $id_val; ?>">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>

                            <!-- Tombol Hapus -->
                            <a href="admin.php?page=kelola_guru&aksi=hapus&id=<?= $id_val; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data <?= $row['nama_lengkap']; ?>?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php 
                        $modal_array[] = $row;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- RENDER POP-UP MODAL MANUAL -->
<?php foreach ($modal_array as $m): ?>
<div class="custom-modal-backdrop" id="editModal<?= $m['id_user']; ?>" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; overflow-y: auto;">
    <div class="modal-dialog" style="max-width: 500px; margin: 50px auto;">
        <div class="modal-content shadow-lg bg-white rounded border">
            <div class="modal-header bg-warning text-white px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Data Petugas</h5>
                <button type="button" class="btn-close btn-close-manual" data-id="<?= $m['id_user']; ?>" style="background: transparent; border: 0; font-size: 1.5rem; cursor: pointer; color: white;">&times;</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="id" value="<?= $m['id_user']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username / NIP:</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($m['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap & Gelar:</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($m['nama_lengkap']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru:</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin ganti password">
                        <small class="text-muted">* Isi hanya jika ingin mengganti password</small>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light border-top d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm btn-close-manual" data-id="<?= $m['id_user']; ?>">Batal</button>
                    <button type="submit" name="edit_guru" class="btn btn-warning btn-sm text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const editBtns = document.querySelectorAll('.btn-edit-manual');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const modal = document.getElementById('editModal' + id);
            if (modal) {
                modal.style.display = 'block';
            }
        });
    });

    const closeBtns = document.querySelectorAll('.btn-close-manual');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const modal = document.getElementById('editModal' + id);
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
});
</script>
