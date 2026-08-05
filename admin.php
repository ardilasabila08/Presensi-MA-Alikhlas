<?php
session_start();
require_once 'koneksi.php';

// Cek hak akses, pastikan hanya admin yang bisa masuk
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: index.php");
    exit();
}

// 1. Tambahkan fungsi getNamaHari di sini (tanpa <?php baru di tengah jalan)
// Fungsi helper untuk mengubah nama hari ke Bahasa Indonesia
function getNamaHari($tanggal) {
    $daftar_hari = array(
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );
    $namahari = date('l', strtotime($tanggal));
    return $daftar_hari[$namahari];
}

$nama_admin = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin';
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$tgl_hari_ini = date('Y-m-d');

// Fungsi helper tambahan untuk menghitung presensi berdasarkan ID kelas
function getTotalPresensiByIdKelas($koneksi, $id_kelas, $status) {
    $q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_presensi p JOIN tb_siswa s ON p.nis = s.nis WHERE s.id_kelas = '$id_kelas' AND p.status = '$status'");
    $d = mysqli_fetch_assoc($q);
    return $d ? $d['total'] : 0;
}
// Fungsi helper tambahan untuk menghitung presensi berdasarkan kelas dan jam pelajaran tertentu
function getTotalPresensiPerJam($koneksi, $id_kelas, $status, $tanggal, $jam_pelajaran) {
    $q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_presensi p JOIN tb_siswa s ON p.nis = s.nis WHERE s.id_kelas='$id_kelas' AND p.status='$status' AND p.tanggal='$tanggal' AND p.jam_pelajaran='$jam_pelajaran'");
    $d = mysqli_fetch_assoc($q);
    return $d ? $d['total'] : 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin E-Presensi - MA Al-Ikhlash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #334155; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: #ffffff; border-right: 1px solid #e2e8f0; z-index: 100; overflow-y: auto; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #64748b; font-weight: 500; border-radius: 8px; margin-bottom: 4px; padding: 10px 15px; }
        .nav-link:hover, .nav-link.active { background-color: #e6f4ea; color: #065f46; font-weight: 600; }
        .card-custom { border: none; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
        .stat-card { border-radius: 16px; color: white; padding: 20px; }
        .transition-hover {
            transition: all 0.2s ease-in-out;
        }
        .transition-hover:hover {
            transform: translateY(-3px);
            background-color: #ffffff !important;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar p-4 d-flex flex-column justify-content-between">
        <div>
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <i class="bi bi-mortarboard-fill fs-3 text-success"></i>
                <h5 class="fw-bold mb-0 text-dark">MA Al-Ikhlash</h5>
            </div>
            
            <div class="text-uppercase small fw-bold text-muted px-2 mb-2">Menu Utama</div>
            <ul class="nav flex-column mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $page == 'dashboard' ? 'active' : ''; ?>" href="admin.php?page=dashboard">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard Admin
                    </a>
                </li>
            </ul>

           <div class="text-uppercase small fw-bold text-muted px-2 mb-2">Rekap Presensi</div>
<ul class="nav flex-column mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $page == 'rekap_presensi' ? 'active' : ''; ?>" href="admin.php?page=rekap_presensi">
            <i class="bi bi-journal-text me-2"></i> Rekap Presensi Kelas
        </a>
    </li>
    <li class="nav-item mt-1">
       <a class="nav-link <?= $page == 'rekap_guru' ? 'active' : ''; ?>" href="admin.php?page=rekap_guru">
    <i class="bi bi-person-badge-fill me-2"></i> Rekap Kehadiran Guru
</a>
    </li>
    <li class="nav-item mt-1">
        <a class="nav-link <?= $page == 'kelola_guru' ? 'active' : ''; ?>" href="admin.php?page=kelola_guru">
            <i class="bi bi-person-plus-fill me-2"></i> Kelola Data Guru
        </a>
    </li>
</ul>
            <div class="text-uppercase small fw-bold text-muted px-2 mb-2">Master Data</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $page == 'data_siswa' ? 'active' : ''; ?>" href="admin.php?page=data_siswa">
                        <i class="bi bi-people me-2"></i> Kelola Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page == 'data_kelas' ? 'active' : ''; ?>" href="admin.php?page=data_kelas">
                        <i class="bi bi-building me-2"></i> Kelola Data Kelas
                    </a>
                    
                </li>
   
            </ul>
        </div>
        

        <div>
            <hr class="text-muted">
            <a href="logout.php" class="nav-link text-danger fw-semibold">
                <i class="bi bi-box-arrow-right me-2"></i> Keluar
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="card card-custom p-3 mb-4 d-flex flex-row justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-success">Panel Admin E-Presensi</h4>
                <p class="text-muted small mb-0">MA Al-Ikhlash Cicalengka</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="fw-semibold text-secondary"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama_admin); ?> (Admin)</span>
            </div>
        </div>
<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if ($page == 'edit_rekap_guru') {
    include 'edit_rekap_guru.php';
    exit;
}

if ($page == 'rekap_guru') {
    include 'rekap_guru.php';
    exit;
}
if ($page == 'kelola_guru') {
    include 'kelola_guru.php';
    exit;
}
if ($page == 'input_presensi') {
    include 'input_presensi.php';
    exit;
    } elseif ($page == 'absen_guru') {
    include 'absen_guru.php';
    exit;

   if ($page == 'data_siswa') {
    include 'data_siswa.php';
    exit;
}

    if ($page == 'edit_siswa') {
    include 'edit_siswa.php';
    exit;
}
if ($page == 'hapus_siswa') {
    include 'hapus_siswa.php';
    exit;
}



}

$tanggal  = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$hari_ini = getNamaHari($tanggal);
?>

<!-- Informasi Hari dan Tanggal -->
<div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
    <div>
        <i class="fas fa-calendar-alt me-2"></i> Hari / Tanggal Rekap: <strong><?= $hari_ini; ?>, <?= date('d-m-Y', strtotime($tanggal)); ?></strong>
    </div>
</div>

  <?php if ($page == 'dashboard'): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-custom p-4 text-white shadow-sm" style="background: linear-gradient(135deg, #047857 0%, #10b981 100%);">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <span class="badge bg-white text-success px-3 py-1 rounded-pill fw-bold mb-2 shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i> Panel Utama Admin
                                </span>
                                <h3 class="fw-bold text-white mb-2 fs-2">Selamat Datang, Admin! 👋</h3>
                                <p class="mb-0 text-white-50 fs-6">Sistem E-Presensi MA Al-Ikhlash Cicalengka. Pantau kehadiran harian siswa, kelola data kelas, dan rekap absensi dengan cepat dan terstruktur.</p>
                            </div>
                            <div class="col-md-4 text-end d-none d-md-block">
                                <i class="bi bi-calendar2-check fs-1 text-white opacity-50" style="font-size: 4rem !important;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <?php
            $jml_siswa_res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_siswa");
            $jml_siswa = ($jml_siswa_res) ? mysqli_fetch_assoc($jml_siswa_res)['total'] : 0;

            $jml_kelas_res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_kelas");
            $jml_kelas = ($jml_kelas_res) ? mysqli_fetch_assoc($jml_kelas_res)['total'] : 0;
?>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card card-custom p-4 border-start border-success border-4 h-100 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Seluruh Siswa</h6>
                                <h3 class="fw-bold text-dark mb-0"><?= $jml_siswa; ?> <span class="fs-6 fw-normal text-muted">Siswa</span></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top small text-success fw-semibold">
                            <i class="bi bi-arrow-up-right me-1"></i> Data aktif tersinkronisasi
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom p-4 border-start border-primary border-4 h-100 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Kelas Terdaftar</h6>
                                <h3 class="fw-bold text-dark mb-0"><?= $jml_kelas; ?> <span class="fs-6 fw-normal text-muted">Kelas</span></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                                <i class="bi bi-building-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top small text-primary fw-semibold">
                            <i class="bi bi-layers-fill me-1"></i> Tingkat MTs & MA (VII - XII)
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom p-4 border-start border-warning border-4 h-100 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-1">Status Sistem</h6>
                                <h3 class="fw-bold text-success mb-0">Aktif <span class="fs-6 fw-normal text-muted">Online</span></h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                                <i class="bi bi-shield-lock-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top small text-muted">
                            <i class="bi bi-check-circle-fill text-success me-1"></i> Database terkoneksi normal
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card card-custom p-4 h-100 shadow-sm">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-rocket-takeoff-fill text-success me-2"></i>Aksi Cepat Admin</h5>
                        <p class="text-muted small mb-4">Pilih menu pintasan di bawah ini untuk mempercepat pengelolaan data harian.</p>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="admin.php?page=data_siswa" class="p-3 border rounded-3 text-decoration-none d-block bg-light bg-opacity-50 h-100 transition-hover">
                                    <i class="bi bi-person-plus-fill fs-4 text-success mb-2 d-block"></i>
                                    <h6 class="fw-bold text-dark mb-1">Kelola Siswa</h6>
                                    <span class="text-muted small">Tambah / edit data siswa</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="admin.php?page=data_kelas" class="p-3 border rounded-3 text-decoration-none d-block bg-light bg-opacity-50 h-100 transition-hover">
                                    <i class="bi bi-folder-symlink-fill fs-4 text-primary mb-2 d-block"></i>
                                    <h6 class="fw-bold text-dark mb-1">Data Kelas</h6>
                                    <span class="text-muted small">Atur daftar kelas</span>
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="admin.php?page=rekap_presensi" class="p-3 border rounded-3 text-decoration-none d-block bg-light bg-opacity-50 transition-hover text-center">
                                    <i class="bi bi-journal-check fs-4 text-warning mb-2 d-block"></i>
                                    <h6 class="fw-bold text-dark mb-1">Buka Rekap Presensi Kelas (MTs & MA)</h6>
                                    <span class="text-muted small">Pilih kelas spesifik untuk melihat kehadiran harian & per jam</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card card-custom p-4 h-100 shadow-sm border-top border-success border-3">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle-fill text-success me-2"></i>Informasi Sistem</h5>
                        <ul class="list-unstyled text-muted small mb-0">
                            <li class="mb-3 d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-5 mt-n1"></i>
                                <span>"Sistem presensi terhubung dengan form input kehadiran yang dikelola langsung oleh petugas berwenang."</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-5 mt-n1"></i>
                                <span>"Pastikan data siswa diperbarui secara berkala jika ada siswa yang pindah atau masuk kelas baru."</span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-5 mt-n1"></i>
                                <span>"Gunakan tombol Keluar di bagian bawah menu sebelah kiri jika sesi admin telah selesai digunakan."</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

<?php elseif ($page == 'rekap_presensi'): ?>
        <?php
            $id_kelas_pilih = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
            
            $nama_kelas_aktif = "Pilih Kelas Terlebih Dahulu";
            if (!empty($id_kelas_pilih)) {
                $q_nk = mysqli_query($koneksi, "SELECT nama_kelas FROM tb_kelas WHERE id_kelas = '$id_kelas_pilih'");
                if ($d_nk = mysqli_fetch_assoc($q_nk)) {
                    $nama_kelas_aktif = $d_nk['nama_kelas'];
                }
            }

            $tot_hadir = !empty($id_kelas_pilih) ? getTotalPresensiByIdKelas($koneksi, $id_kelas_pilih, 'Hadir', $tgl_hari_ini) : 0;
            $tot_izin  = !empty($id_kelas_pilih) ? getTotalPresensiByIdKelas($koneksi, $id_kelas_pilih, 'Izin', $tgl_hari_ini) : 0;
            $tot_sakit = !empty($id_kelas_pilih) ? getTotalPresensiByIdKelas($koneksi, $id_kelas_pilih, 'Sakit', $tgl_hari_ini) : 0;
            $tot_alpa  = !empty($id_kelas_pilih) ? getTotalPresensiByIdKelas($koneksi, $id_kelas_pilih, 'Alpa', $tgl_hari_ini) : 0;
        ?>

        <!-- Form Pilih Kelas Dropdown -->
        <div class="card card-custom p-4 mb-4 shadow-sm">
            <h<!-- Form Pilih Kelas & Filter Periode -->
<div class="card card-custom p-4 mb-4 shadow-sm">
    <h5 class="fw-bold mb-3"><i class="bi bi-filter-circle text-success me-2"></i>Filter Rekap Presensi</h5>
    <form method="GET" action="admin.php" class="row g-3 align-items-center">
        <input type="hidden" name="page" value="rekap_presensi">
        
        <!-- Pilihan Kelas -->
        <div class="col-md-5">
            <select name="id_kelas" class="form-select" onchange="this.form.submit()">
                <option value="">-- Pilih Kelas (MTs & MA) --</option>
                <?php
                $q_all_kelas = mysqli_query($koneksi, "SELECT * FROM tb_kelas ORDER BY nama_kelas ASC");
                while($kls = mysqli_fetch_assoc($q_all_kelas)):
                ?>
                    <option value="<?= $kls['id_kelas']; ?>" <?= ($id_kelas_pilih == $kls['id_kelas']) ? 'selected' : ''; ?>>
                        <?= $kls['nama_kelas']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Pilihan Filter Periode -->
        <div class="col-md-4">
            <select name="filter" class="form-select" onchange="this.form.submit()">
                <?php $filter_pilih = isset($_GET['filter']) ? $_GET['filter'] : 'semua'; ?>
                <option value="semua" <?= ($filter_pilih == 'semua') ? 'selected' : ''; ?>>Semua Periode</option>
                <option value="hari_ini" <?= ($filter_pilih == 'hari_ini') ? 'selected' : ''; ?>>Hari Ini</option>
                <option value="minggu_ini" <?= ($filter_pilih == 'minggu_ini') ? 'selected' : ''; ?>>Minggu Ini (7 Hari Terakhir)</option>
                <option value="bulan_ini" <?= ($filter_pilih == 'bulan_ini') ? 'selected' : ''; ?>>Bulan Ini</option>
            </select>
        </div>
    </form>
</div>

        <?php if (!empty($id_kelas_pilih)): ?>
            <!-- Kotak Statistik Total Harian Kelas Terpilih -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-success shadow-sm">
                        <h6 class="text-white-50 small text-uppercase fw-bold">Total Hadir (<?= $nama_kelas_aktif; ?>)</h6>
                        <h2 class="fw-bold mb-0"><?= $tot_hadir; ?> <span class="fs-6 fw-normal">Siswa</span></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-warning shadow-sm">
                        <h6 class="text-dark small text-uppercase fw-bold">Total Izin (<?= $nama_kelas_aktif; ?>)</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $tot_izin; ?> <span class="fs-6 fw-normal">Siswa</span></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-info shadow-sm">
                        <h6 class="text-white-50 small text-uppercase fw-bold">Total Sakit (<?= $nama_kelas_aktif; ?>)</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $tot_sakit; ?> <span class="fs-6 fw-normal">Siswa</span></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-danger shadow-sm">
                        <h6 class="text-white-50 small text-uppercase fw-bold">Total Alpa (<?= $nama_kelas_aktif; ?>)</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $tot_alpa; ?> <span class="fs-6 fw-normal">Siswa</span></h2>
                    </div>
                </div>
            </div>

            <!-- Tabel Rincian Siswa -->
            <div class="card card-custom p-4 mt-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Rekap Akumulasi Presensi <?= $nama_kelas_aktif; ?></h5>
                        <p class="text-muted small mb-0">Total akumulasi kehadiran keseluruhan siswa pada kelas ini.</p>
                    </div>
                    <div>
                    <a href="cetak_laporan.php?id_kelas=<?= $id_kelas_pilih; ?>&filter=<?= isset($_GET['filter']) ? $_GET['filter'] : 'semua'; ?>" target="_blank" class="btn btn-success">
                        <i class="bi bi-printer"></i> Cetak Laporan
                    </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">NO</th>
                                <th width="12%">NIS</th>
                                <th class="text-start">NAMA SISWA</th>
                                <th>KELAS</th>
                                <th>HARI</th>
                                <th>TANGGAL</th>
                                <th class="bg-success text-white">HADIR</th>
                                <th class="bg-warning text-dark">IZIN</th>
                                <th class="bg-info text-dark">SAKIT</th>
                                <th class="bg-danger text-white">ALPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

$filter_sql = "";
$filter_sql = "";
if ($filter == 'hari_ini') {
    $tgl_sekarang = date('Y-m-d');
    $filter_sql = " AND p.tanggal = '$tgl_sekarang'";
} elseif ($filter == 'minggu_ini') {
    // Mengambil data dari hari Senin minggu ini sampai hari Minggu ini
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
    WHERE s.id_kelas = '$id_kelas_pilih' $filter_sql
    GROUP BY s.nis, s.nama_siswa, k.nama_kelas, p.tanggal
    ORDER BY p.tanggal ASC, s.nama_siswa ASC
");

                            $no = 1;
                            if($q_rekap && mysqli_num_rows($q_rekap) > 0) {
                                while($r = mysqli_fetch_assoc($q_rekap)) {
                                    $tanggal_presensi = !empty($r['tanggal']) ? $r['tanggal'] : $tgl_hari_ini;
                                    
                                    $days_map = [
                                        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                                        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                                    ];
                                    $eng_day = date('l', strtotime($tanggal_presensi));
                                    $nama_hari = isset($days_map[$eng_day]) ? $days_map[$eng_day] : $eng_day;
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="font-monospace"><?= $r['nis']; ?></td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($r['nama_siswa']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $r['nama_kelas']; ?></span></td>
                                <td><?= $nama_hari; ?></td>
                                <td><?= date('d-m-Y', strtotime($tanggal_presensi)); ?></td>
                                <td><span class="badge bg-success px-3 py-2"><?= $r['total_hadir']; ?></span></td>
                                <td><span class="badge bg-warning text-dark px-3 py-2"><?= $r['total_izin']; ?></span></td>
                                <td><span class="badge bg-info text-dark px-3 py-2"><?= $r['total_sakit']; ?></span></td>
                                <td><span class="badge bg-danger px-3 py-2"><?= $r['total_alpa']; ?></span></td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center text-muted py-4'>Belum ada data siswa untuk kelas ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center py-4 shadow-sm">
                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                Silakan pilih salah satu kelas pada dropdown di atas (mencakup kelas tingkat MTs & MA) untuk menampilkan data rekap presensinya.
            </div>
        <?php endif; ?>

    <?php elseif ($page == 'data_siswa'): ?>
        <div class="card card-custom p-4 shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Kelola Data Siswa</h5>
                    <p class="text-muted small mb-0">Daftar seluruh siswa terdaftar yang otomatis tersinkronisasi ke form absen ketua kelas.</p>
                </div>
                <a href="tambah_siswa.php" class="btn btn-success btn-sm fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Siswa Baru
                </a>
            </div>

            <?php 
            $filter_kelas = isset($_GET['filter_kelas']) ? $_GET['filter_kelas'] : '';
            $query_sql = "SELECT tb_siswa.*, tb_kelas.nama_kelas FROM tb_siswa LEFT JOIN tb_kelas ON tb_siswa.id_kelas = tb_kelas.id_kelas";
            if (!empty($filter_kelas)) {
                $query_sql .= " WHERE tb_kelas.id_kelas = '$filter_kelas'";
            }
            $query_sql .= " ORDER BY tb_siswa.nama_siswa ASC";
            
            $q_ds = mysqli_query($koneksi, $query_sql);
            $q_kelas_opt = mysqli_query($koneksi, "SELECT * FROM tb_kelas ORDER BY nama_kelas ASC");
            ?>

            <form method="GET" action="admin.php" class="row g-2 align-items-center mb-4 bg-light p-3 rounded-3">
                <input type="hidden" name="page" value="data_siswa">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Filter Berdasarkan Kelas:</label>
                    <select name="filter_kelas" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Tampilkan Semua Kelas --</option>
                        <?php while($fk = mysqli_fetch_assoc($q_kelas_opt)): ?>
                            <option value="<?= $fk['id_kelas']; ?>" <?= $filter_kelas == $fk['id_kelas'] ? 'selected' : ''; ?>>
                                <?= $fk['nama_kelas']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (!empty($filter_kelas)): ?>
                    <div class="col-md-2 mt-4">
                        <a href="admin.php?page=data_siswa" class="btn btn-outline-secondary btn-sm w-100">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">NO</th>
                            <th>NIS</th>
                            <th>NAMA SISWA</th>
                            <th>KELAS</th>
                            <th class="text-center" width="15%">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no_s = 1;
                        if($q_ds && mysqli_num_rows($q_ds) > 0) {
                            while($ds = mysqli_fetch_assoc($q_ds)) {
                        ?>
                        <tr>
                            <td><?= $no_s++; ?></td>
                            <td class="font-monospace"><?= $ds['nis']; ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($ds['nama_siswa']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?= isset($ds['nama_kelas']) ? $ds['nama_kelas'] : '-'; ?></span></td>
                            <td class="text-center">
                                <a href="edit_siswa.php?nis=<?= $ds['nis']; ?>" class="btn btn-warning btn-sm text-white"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus_siswa.php?nis=<?= $ds['nis']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data siswa ini?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-4'>Belum ada data siswa yang sesuai.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($page == 'data_kelas'): ?>
        <div class="card card-custom p-4 shadow-sm">
            <h5 class="fw-bold mb-3">Kelola Data Kelas (MTs & MA)</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">NO</th>
                            <th>NAMA KELAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_kls = mysqli_query($koneksi, "SELECT * FROM tb_kelas ORDER BY nama_kelas ASC");
                        $no_k = 1;
                        while($kls = mysqli_fetch_assoc($q_kls)) {
                        ?>
                        <tr>
                            <td><?= $no_k++; ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($kls['nama_kelas']); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>