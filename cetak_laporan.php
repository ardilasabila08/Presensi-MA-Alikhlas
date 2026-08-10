<?php
session_start();
require_once 'koneksi.php';

// Cek hak akses admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Cek parameter kelas dan filter
$id_kelas = '';
$kelas = '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

if (isset($_GET['kelas'])) {
    $kelas = $_GET['kelas'];
    $q_cari = mysqli_query($koneksi, "SELECT id_kelas FROM tb_kelas WHERE nama_kelas = '$kelas'");
    if ($d_cari = mysqli_fetch_assoc($q_cari)) {
        $id_kelas = $d_cari['id_kelas'];
    }
} elseif (isset($_GET['id_kelas'])) {
    $id_k = mysqli_real_escape_string($koneksi, $_GET['id_kelas']);
    $id_kelas = $id_k;
    $q_k = mysqli_query($koneksi, "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = '$id_k'");
    if ($q_k && mysqli_num_rows($q_k) > 0) {
        $d_k = mysqli_fetch_assoc($q_k);
        $kelas = $d_k['nama_kelas'];
    }
}

if (empty($kelas)) {
    echo "Kelas tidak valid!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Presensi - <?= htmlspecialchars($kelas); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; color: #000; background: #fff; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container mt-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-0">MA AL-IKHLASH CICALENGKA</h3>
            <p class="text-muted">Laporan Rekapitulasi Presensi Siswa <?= htmlspecialchars($kelas); ?> (Filter: <?= ucfirst(str_replace('_', ' ', $filter)); ?>)</p>
            <hr>
        </div>

        <div class="mb-3 no-print">
            <button onclick="window.print()" class="btn btn-success btn-sm">Cetak Ulang</button>
            <a href="admin.php?page=rekap_presensi&id_kelas=<?= $id_kelas; ?>&filter=<?= $filter; ?>" class="btn btn-secondary btn-sm">Kembali</a>
        </div>

        <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th width="5%">NO</th>
                    <th width="12%">NIS</th>
                    <th class="text-start">NAMA SISWA</th>
                    <th>KELAS</th>
                    <th>HARI</th>
                    <th>TANGGAL</th>
                    <th>HADIR</th>
                    <th>IZIN</th>
                    <th>SAKIT</th>
                    <th>ALPA</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $filter_sql = "";
                if ($filter == 'hari_ini') {
                    $tgl_sekarang = date('Y-m-d');
                    $filter_sql = " AND p.tanggal = '$tgl_sekarang'";
                } elseif ($filter == 'minggu_ini') {
                    $filter_sql = " AND YEARWEEK(p.tanggal, 1) = YEARWEEK(CURDATE(), 1)";
                } elseif ($filter == 'bulan_ini') {
                    $filter_sql = " AND MONTH(p.tanggal) = MONTH(CURDATE()) AND YEAR(p.tanggal) = YEAR(CURDATE())";
                }

                $q_rekap = mysqli_query($koneksi, "
                    SELECT s.nis, s.nama_siswa, k.nama_kelas, p.tanggal,
                    SUM(CASE WHEN p.status = 'Hadir' THEN 1 ELSE 0 END) as total_hadir,
                    SUM(CASE WHEN p.status = 'Izin' THEN 1 ELSE 0 END) as total_izin,
                    SUM(CASE WHEN p.status = 'Sakit' THEN 1 ELSE 0 END) as total_sakit,
                    SUM(CASE WHEN p.status = 'Alpa' THEN 1 ELSE 0 END) as total_alpa
                    FROM tb_siswa s
                    JOIN tb_kelas k ON s.id_kelas = k.id_kelas
                    LEFT JOIN tb_presensi p ON s.nis = p.nis
                    WHERE s.id_kelas = '$id_kelas' $filter_sql
                    GROUP BY s.nis, s.nama_siswa, k.nama_kelas, p.tanggal
                    ORDER BY p.tanggal ASC, s.nama_siswa ASC
                ");

                $no = 1;
                if($q_rekap && mysqli_num_rows($q_rekap) > 0) {
                    while($r = mysqli_fetch_assoc($q_rekap)) {
                        $tanggal_presensi = !empty($r['tanggal']) ? $r['tanggal'] : date('Y-m-d');
                        $days_map = [
                            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                        ];
                        $eng_day = date('l', strtotime($tanggal_presensi));
                        $hari_indo = isset($days_map[$eng_day]) ? $days_map[$eng_day] : $eng_day;
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $r['nis']; ?></td>
                    <td class="text-start"><?= htmlspecialchars($r['nama_siswa']); ?></td>
                    <td><?= $r['nama_kelas']; ?></td>
                    <td><?= !empty($r['tanggal']) ? $hari_indo : '-'; ?></td>
                    <td><?= !empty($r['tanggal']) ? date('d-m-Y', strtotime($r['tanggal'])) : '-'; ?></td>
                    <td><?= $r['total_hadir']; ?></td>
                    <td><?= $r['total_izin']; ?></td>
                    <td><?= $r['total_sakit']; ?></td>
                    <td><?= $r['total_alpa']; ?></td>
                </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='10' class='text-center py-4'>Belum ada data presensi untuk periode ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p>Cicalengka, <?= date('d F Y'); ?></p>
                <p class="fw-bold" style="margin-top: 60px;">Kepala Sekolah</p>
            </div>
        </div>
    </div>

</body>
</html>
