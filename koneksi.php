<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_absensi";

$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cukup pastikan koneksi gagal yang dicek dengan tanda seru (!)
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
