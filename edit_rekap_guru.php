<?php
// Tangkap ID dari POST jika form disubmit, atau dari GET jika baru pertama kali dibuka
$id_val = isset($_POST['id_record']) ? mysqli_real_escape_string($koneksi, $_POST['id_record']) : (isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '');

$q_cek = mysqli_query($koneksi, "SELECT * FROM tb_kehadiran_guru WHERE id = '$id_val'");
$r = mysqli_fetch_assoc($q_cek);

if (!isset($r['id'])) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='admin.php?page=rekap_guru';</script>";
    exit;
}

if (isset($_POST['update_absen_guru'])) {
    $tanggal_guru         = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $hari_guru            = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $nama_guru            = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
    $status_guru          = mysqli_real_escape_string($koneksi, $_POST['status']);
    $jam_pelajaran_guru   = mysqli_real_escape_string($koneksi, $_POST['jam_pelajaran']);

   $update_query = "UPDATE tb_kehadiran_guru SET 
                 tanggal = '$tanggal_guru', 
                 hari = '$hari_guru', 
                 nama_guru = '$nama_guru', 
                 status = '$status_guru', 
                 keterangan = '$jam_pelajaran_guru',
                 jam_pelajaran = '$jam_pelajaran_guru' 
                 WHERE id = '$id_val'";
    if (mysqli_query($koneksi, $update_query)) {
        echo "<script>alert('Data kehadiran berhasil diperbarui!'); window.location='admin.php?page=rekap_guru';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-custom p-4 bg-white shadow-sm" style="border: none; border-radius: 12px;">
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <div class="bg-success text-white p-3 rounded-3 me-3 shadow-sm">
                        <i class="fas fa-user-edit fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1">Edit Kehadiran Guru & Petugas</h4>
                        <p class="text-muted small mb-0">Perbarui informasi data kehadiran pada sistem presensi.</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <!-- TAMBAHKAN INPUT HIDDEN INI AGAR ID TIDAK HILANG SAAT DISUBMIT -->
                    <input type="hidden" name="id_record" value="<?= $r['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">TANGGAL:</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $r['tanggal']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">HARI:</label>
                        <select name="hari" class="form-select" required>
                            <?php $h_val = $r['hari'] ?? 'SENIN'; ?>
                            <option value="SENIN" <?= ($h_val == 'SENIN') ? 'selected' : ''; ?>>SENIN</option>
                            <option value="SELASA" <?= ($h_val == 'SELASA') ? 'selected' : ''; ?>>SELASA</option>
                            <option value="RABU" <?= ($h_val == 'RABU') ? 'selected' : ''; ?>>RABU</option>
                            <option value="KAMIS" <?= ($h_val == 'KAMIS') ? 'selected' : ''; ?>>KAMIS</option>
                            <option value="JUMAT" <?= ($h_val == 'JUMAT') ? 'selected' : ''; ?>>JUMAT</option>
                            <option value="SABTU" <?= ($h_val == 'SABTU') ? 'selected' : ''; ?>>SABTU</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">NAMA GURU / PETUGAS:</label>
                        <select name="nama_guru" class="form-select" required>
                            <?php
                            $q_petugas_edit = mysqli_query($koneksi, "SELECT * FROM petugas");
                            while ($ptg = mysqli_fetch_assoc($q_petugas_edit)) {
                                $selected = ($ptg['nama_lengkap'] == $r['nama_guru']) ? 'selected' : '';
                                echo "<option value='".$ptg['nama_lengkap']."' $selected>".$ptg['nama_lengkap']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">STATUS KEHADIRAN:</label>
                        <select name="status" class="form-select" required>
                            <option value="Hadir" <?= ($r['status'] == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                            <option value="Izin" <?= ($r['status'] == 'Izin') ? 'selected' : ''; ?>>Izin</option>
                            <option value="Sakit" <?= ($r['status'] == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">JAM PELAJARAN / SESI:</label>
                        <select name="jam_pelajaran" class="form-select" required>
                            <?php 
                            $jam_list = [
                                "Piket Pagi / Masuk (07:00 - 12:00)",
                                "Piket Siang / Pulang (12:00 - 15:00)",
                                "Full Day (07:00 - 15:00)",
                                "06.20 - 07.00 (Sholat Dhuha & Tadarus)",
                                "07.00 - 12.00 (Sesi Pagi)",
                                "12.00 - 15.00 (Sesi Siang/Pulang)",
                                "Ekstrakurikuler",
                                "Absen Mandiri",
                                "Diluar Jam Absen"
                            ];
                            foreach($jam_list as $jl) {
                                $sel_jl = (isset($r['jam_pelajaran']) && $r['jam_pelajaran'] == $jl) ? 'selected' : '';
                                echo "<option value='$jl' $sel_jl>$jl</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="admin.php?page=rekap_guru" class="btn btn-secondary px-4">Kembali</a>
                        <button type="submit" name="update_absen_guru" class="btn btn-success px-4 fw-semibold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>