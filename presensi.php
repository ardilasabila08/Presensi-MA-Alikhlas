<?php
require_once '../koneksi.php';

if (!isset($_SESSION['level']) || ($_SESSION['level'] !== 'guru' && $_SESSION['level'] !== 'ketua_kelas')) {
    header('Location: ../index.php');[cite: 2]
    exit;
}

$id_kelas = $_SESSION['id_kelas'];
$tanggal_hari_ini = date('Y-m-d');

$query_kelas = "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = '$id_kelas'";
$res_kelas = mysqli_query($koneksi, $query_kelas);
$data_kelas = mysqli_fetch_assoc($res_kelas);
$nama_kelas = $data_kelas ? $data_kelas['nama_kelas'] : 'Belum Ditentukan';

$query_siswa = "SELECT * FROM tb_siswa WHERE id_kelas = '$id_kelas' ORDER BY nama_siswa ASC";
$res_siswa = mysqli_query($koneksi, $query_siswa);

if (isset($_POST['simpan_absen'])) {
    foreach ($_POST['status'] as $nis => $status) {
        $cek = mysqli_query($koneksi, "SELECT id_presensi FROM tb_presensi WHERE nis = '$nis' AND tanggal = '$tanggal_hari_ini'");
        if (mysqli_num_rows($cek) > 0) {
            mysqli_query($koneksi, "UPDATE tb_presensi SET status = '$status' WHERE nis = '$nis' AND tanggal = '$tanggal_hari_ini'");
        } else {
            mysqli_query($koneksi, "INSERT INTO tb_presensi (nis, tanggal, status) VALUES ('$nis', '$tanggal_hari_ini', '$status')");
        }
    }
    $success_msg = "Data kehadiran hari ini berhasil disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Presensi - MA Al Ikhlas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; }
        .navbar-custom { background-color: #022c22; }
        .status-radio input[type="radio"] { display: none; }
        .status-radio label { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; border: 2px solid #e2e8f0; margin-right: 4px; }
        input[value="Hadir"]:checked + label { background-color: #d1fae5; color: #065f46; border-color: #10b981; }
        input[value="Sakit"]:checked + label { background-color: #fef3c7; color: #92400e; border-color: #f59e0b; }
        input[value="Izin"]:checked + label { background-color: #e0f2fe; color: #0369a1; border-color: #0ea5e9; }
        input[value="Alpa"]:checked + label { background-color: #fee2e2; color: #991b1b; border-color: #ef4444; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark navbar-custom px-3 shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-warning" href="#">E-PRESENSI</a>
        <a href="../logout.php" class="btn btn-sm btn-outline-danger border-0 rounded-pill"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
    </div>
</nav>

<div class="container my-5">
    <h3>Form Kehadiran Harian Siswa</h3>
    <p class="text-secondary">Kelas: <strong><?= $nama_kelas ?></strong> | Tanggal: <strong><?= date('d F Y') ?></strong></p>

    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm border-0">
        <form action="" method="POST">
            <table class="table align-middle">
                <thead>
                    <tr class="text-center">
                        <th width="5%">No</th>
                        <th width="45%" class="text-start">Nama Siswa</th>
                        <th width="50%">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while($siswa = mysqli_fetch_assoc($res_siswa)): 
                        $current_nis = $siswa['nis'];
                        $cek_p = mysqli_query($koneksi, "SELECT status FROM tb_presensi WHERE nis = '$current_nis' AND tanggal = '$tanggal_hari_ini'");
                        $d_p = mysqli_fetch_assoc($cek_p);
                        $status_sekarang = $d_p ? $d_p['status'] : 'Hadir';
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="fw-bold"><?= $siswa['nama_siswa'] ?></td>
                        <td>
                            <div class="d-flex justify-content-center status-radio">
                                <input type="radio" name="status[<?= $siswa['nis'] ?>]" id="H-<?= $siswa['nis'] ?>" value="Hadir" <?= $status_sekarang == 'Hadir' ? 'checked' : '' ?>><label for="H-<?= $siswa['nis'] ?>">Hadir</label>
                                <input type="radio" name="status[<?= $siswa['nis'] ?>]" id="S-<?= $siswa['nis'] ?>" value="Sakit" <?= $status_sekarang == 'Sakit' ? 'checked' : '' ?>><label for="S-<?= $siswa['nis'] ?>">Sakit</label>
                                <input type="radio" name="status[<?= $siswa['nis'] ?>]" id="I-<?= $siswa['nis'] ?>" value="Izin" <?= $status_sekarang == 'Izin' ? 'checked' : '' ?>><label for="I-<?= $siswa['nis'] ?>">Izin</label>
                                <input type="radio" name="status[<?= $siswa['nis'] ?>]" id="A-<?= $siswa['nis'] ?>" value="Alpa" <?= $status_sekarang == 'Alpa' ? 'checked' : '' ?>><label for="A-<?= $siswa['nis'] ?>">Alpa</label>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit" name="simpan_absen" class="btn btn-success float-end px-5 rounded-pill">Simpan Absensi</button>
        </form>
    </div>
</div>
</body>
</html>