<?php
include "koneksi.php";

// Menangkap parameter filter dari URL yang dikirimkan tombol cetak di admin
// Menangkap parameter filter dari URL
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$tanggal_harian = isset($_GET['tanggal_harian']) ? $_GET['tanggal_harian'] : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';
$bulan_tahun = isset($_GET['bulan_tahun']) ? $_GET['bulan_tahun'] : '';

// Menyusun query SQL berdasarkan filter yang dipilih
$query_sql = "SELECT * FROM tb_kehadiran_guru";

if ($filter_type == 'harian' && !empty($tanggal_harian)) {
    $query_sql .= " WHERE tanggal = '$tanggal_harian'";
    $info_periode = "Periode Tanggal: " . date('d-m-Y', strtotime($tanggal_harian));
} elseif ($filter_type == 'rentang' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query_sql .= " WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
    $info_periode = "Periode Tanggal: " . date('d-m-Y', strtotime($tgl_mulai)) . " s/d " . date('d-m-Y', strtotime($tgl_selesai));
} elseif ($filter_type == 'bulanan' && !empty($bulan_tahun)) {
    $query_sql .= " WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_tahun'";
    
    $timestamp = strtotime($bulan_tahun . '-01');
    $nama_bulan = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];
    $bulan_indo = strtr(date('F Y', $timestamp), $nama_bulan);
    
    $info_periode = "Periode Bulan: " . $bulan_indo;
} else {
    $info_periode = "Periode: Seluruh Data";

}

$query_sql .= " ORDER BY tanggal DESC, jam_absen DESC";

$q_rekap = mysqli_query($koneksi, $query_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Rekap Kehadiran Guru</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h3, .header h4 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        table th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; float: right; text-align: center; }
        
        .no-print {
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            background-color: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .btn-primary {
            background-color: #0d6efd;
        }
        .btn:hover {
            opacity: 0.9;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="no-print">
        <a href="admin.php?page=rekap_guru" class="btn">&larr; Kembali ke Panel Admin</a>
        <button onclick="window.print()" class="btn btn-primary" style="border:none; cursor:pointer;">Cetak Ulang</button>
    </div>

    <div class="header">
        <h3>MA AL-IKHLASH CICALENGKA</h3>
        <h4>LAPORAN REKAP KEHADIRAN GURU & PETUGAS</h4>
        <p><?= $info_periode; ?></p>
        <p>Tanggal Cetak: <?= date('d-m-Y'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="11%">TANGGAL</th>
                <th width="9%">HARI</th>
                <th>NAMA GURU / PETUGAS</th>
                <th width="22%">JAM PELAJARAN / SESI</th>
                <th width="11%" class="text-center">JAM MASUK</th>
                <th width="11%" class="text-center">JAM PULANG</th>
                <th width="9%" class="text-center">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if(mysqli_num_rows($q_rekap) > 0) {
                while($r = mysqli_fetch_assoc($q_rekap)) {
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= date('d-m-Y', strtotime($r['tanggal'])); ?></td>
                <td><?= $r['hari'] ?? '-'; ?></td>
                <td><b><?= htmlspecialchars($r['nama_guru']); ?></b></td>
                <td><?= htmlspecialchars($r['jam_pelajaran']); ?></td>
                <td class="text-center"><?= (!empty($r['jam_masuk']) && $r['jam_masuk'] != '00:00:00') ? $r['jam_masuk'] : '-'; ?></td>
                <td class="text-center"><?= (!empty($r['jam_pulang']) && $r['jam_pulang'] != '00:00:00') ? $r['jam_pulang'] : '-'; ?></td>
                <td class="text-center"><?= $r['status']; ?></td>
            </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='8' class='text-center'>Belum ada data rekap kehadiran untuk periode ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Cicalengka, <?= date('d-m-Y'); ?></p>
        <br><br><br>
        <p><b>Administrator</b></p>
    </div>
</div>

</body>
</html>