<?php
require_once 'koneksi.php';

// Cek apakah ada parameter nis yang dikirim
if (isset($_GET['nis'])) {
    $nis = $_GET['nis'];

    // Query hapus data siswa berdasarkan nis
    $query = "DELETE FROM tb_siswa WHERE nis = '$nis'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        // Jika berhasil, alihkan kembali ke halaman data siswa
        echo "<script>alert('Data siswa berhasil dihapus!'); window.location='admin.php?page=data_siswa';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!'); window.location='admin.php?page=data_siswa';</script>";
    }
} else {
    header("Location: admin.php?page=data_siswa");
}
?>
