<?php
// 1. Mulai session dan panggil koneksi database terlebih dahulu
session_start();
include "koneksi.php"; // (Sesuaikan dengan nama file koneksi kamu, misal: include 'koneksi.php'; atau require_once 'koneksi.php';)

// 2. Baru atur zona waktu agar sinkron ke WIB
date_default_timezone_set('Asia/Jakarta');

// 3. Cek apakah user sudah login dan bukan admin
if (!isset($_SESSION['level']) || $_SESSION['level'] == 'admin') {
    header("Location: index.php");
    exit;
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : '';
$tanggal_hari_ini = date('Y-m-d');
$jam_sekarang = date('H:i:s');
// Masukkan variabel $jam_sekarang ke dalam query INSERT INTO tb_kehadiran_guru ...
$jam_angka = (int) date('H');

// LOGIKA OTOMATIS: Jika sebelum jam 12 maka Jam Masuk, jika jam 12 ke atas maka Jam Pulang
if ($jam_angka < 12) {
    $jenis_absen_otomatis = 'Jam Masuk';
} else {
    $jenis_absen_otomatis = 'Jam Pulang';
}

// Tentukan hari ini secara otomatis
$nama_hari_inggris = date('l');
$daftar_hari = [
    'Monday'    => 'SENIN',
    'Tuesday'   => 'SELASA',
    'Wednesday' => 'RABU',
    'Thursday'  => 'KAMIS',
    'Friday'    => 'JUMAT',
    'Saturday'  => 'LIBUR',
    'Sunday'    => 'LIBUR'
];

$hari_aktif = isset($daftar_hari[$nama_hari_inggris]) ? $daftar_hari[$nama_hari_inggris] : 'LIBUR';

$pesan_sukses_guru = "";
$is_blocked = false;

// Jika hari Libur, langsung set blokir dan pesan
if ($hari_aktif == 'LIBUR') {
    $is_blocked = true;
    $pesan_sukses_guru = "Mohon maaf untuk hari sabtu dan minggu libur.";
}

// Tambahan pengaman mutlak: Jika tombol diklik saat libur, paksa batalkan proses!
if (isset($_POST['simpan_absen_guru_mandiri']) && $hari_aktif == 'LIBUR') {
    $pesan_sukses_guru = "Mohon maaf untuk hari sabtu dan minggu libur.";
    // Lewati proses simpan database
} else {
    // ... (simpan seluruh kode proses simpan tombol yang ada di bawahnya di sini) ...
}

// Ambil pilihan kelas, hari, dan jam pelajaran
$id_kelas_aktif = isset($_REQUEST['id_kelas']) ? $_REQUEST['id_kelas'] : '';
$pilih_hari     = isset($_REQUEST['hari']) ? $_REQUEST['hari'] : $hari_aktif;

if ($pilih_hari == 'SABTU') {
    $jam_pelajaran = 'Ekstrakurikuler';
} else {
    $jam_pelajaran  = isset($_REQUEST['jam_pelajaran']) ? $_REQUEST['jam_pelajaran'] : '06.20 - 07.00 (Sholat Dhuha & Tadarus)';
}

$nama_kelas_aktif = 'Pilih Kelas Terlebih Dahulu';
if (!empty($id_kelas_aktif)) {
    $q_kelas = mysqli_query($koneksi, "SELECT * FROM tb_kelas WHERE id_kelas = '$id_kelas_aktif'");
    $d_kelas = mysqli_fetch_assoc($q_kelas);
    if ($d_kelas) {
        $nama_kelas_aktif = $d_kelas['nama_kelas'];
    }
}

// ==========================================
// Proses simpan absen mandiri guru + Foto Webcam
// ==========================================
$pesan_sukses_guru = "";
$is_blocked = false;

if (isset($_POST['simpan_absen_guru_mandiri'])) {
    // TAMBAHAN PENGECEKAN HARI LIBUR AGAR TIDAK BISA TEMBUS KE BAWAH
    if (isset($hari_aktif) && $hari_aktif == 'LIBUR') {
        $pesan_sukses_guru = "Mohon maaf untuk hari sabtu dan minggu libur.";
    } else {
        // --- SEMUA KODINGAN ASLIMU DI BAWAH INI DIBIARKAN UTUH TANPA DIUBAH ---
        if ($is_blocked) {
            $pesan_sukses_guru = "Mohon maaf untuk hari sabtu dan minggu libur.";
        } else {
            $nama_guru_abs = $nama_petugas;
            $hari_guru_abs = mysqli_real_escape_string($koneksi, $_POST['hari_guru'] ?? '');
            $status_guru_abs = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Hadir');
            $jam_pelajaran_abs = mysqli_real_escape_string($koneksi, $_POST['jam_pelajaran'] ?? '-');

            $tanggal_hari_ini = date('Y-m-d');
            $jam_absen_sekarang = date('H:i:s');

            // 1. Cek riwayat absen hari ini untuk guru yang sedang login
            $cek_absen = mysqli_query($koneksi, "SELECT * FROM tb_kehadiran_guru WHERE nama_guru = '$nama_guru_abs' AND tanggal = '$tanggal_hari_ini'");
            $data_exist = mysqli_fetch_assoc($cek_absen);

            // Fungsi kecil untuk mengecek apakah kolom jam benar-benar terisi
            $has_jam_masuk = ($data_exist && !empty($data_exist['jam_masuk']) && $data_exist['jam_masuk'] != '00:00:00');
            $has_jam_pulang = ($data_exist && !empty($data_exist['jam_pulang']) && $data_exist['jam_pulang'] != '00:00:00');

            // 2. KONDISI BLOKIR: Jika KEDUA JAM (masuk & pulang) sudah terisi valid
            if ($has_jam_masuk && $has_jam_pulang) {
                $pesan_sukses_guru = "Tidak bisa absen karena sudah absen jam masuk dan jam pulang.";
                $is_blocked = true;
            } else {
                // Ambil data foto dari webcam
                $image_data_base64 = isset($_POST['image_captured']) ? $_POST['image_captured'] : '';
                $nama_file_foto = '';

                if (!empty($image_data_base64)) {
                    $image_parts = explode(';base64,', $image_data_base64);
                    if (count($image_parts) == 2) {
                        $image_base64_decoded = base64_decode($image_parts[1]);
                        $nama_file_foto = 'absen_guru_' . time() . '_' . rand(100, 999) . '.png';
                        $path_penyimpanan = 'uploads/' . $nama_file_foto;

                        if (!is_dir('uploads')) {
                            mkdir('uploads', 0777, true);
                        }

                        file_put_contents($path_penyimpanan, $image_base64_decoded);
                    }
                }

                if (!$data_exist) {
                    // KONDISI A: Belum pernah absen sama sekali hari ini -> JAM MASUK
                    $keterangan_waktu = "Jam Masuk";
                    $query_guru = "INSERT INTO tb_kehadiran_guru (tanggal, hari, nama_guru, status, jam_pelajaran, jam_masuk, foto_masuk) 
                                   VALUES ('$tanggal_hari_ini', '$hari_guru_abs', '$nama_guru_abs', '$status_guru_abs', '$jam_pelajaran_abs', '$jam_absen_sekarang', '$nama_file_foto')";
                } else {
                    // KONDISI B: Sudah ada jam masuk, sekarang isi JAM PULANG
                    $keterangan_waktu = "Jam Pulang";
                    $query_guru = "UPDATE tb_kehadiran_guru 
                                   SET jam_pulang = '$jam_absen_sekarang', 
                                       foto_pulang = '$nama_file_foto', 
                                       jam_pelajaran = '$jam_pelajaran_abs',
                                       hari = '$hari_guru_abs'
                                   WHERE nama_guru = '$nama_guru_abs' AND tanggal = '$tanggal_hari_ini'";
                }

                if (mysqli_query($koneksi, $query_guru)) {
                    $pesan_sukses_guru = "Absen <b>$keterangan_waktu</b> guru atas nama <b>$nama_guru_abs</b> berhasil disimpan!";
                } else {
                    $pesan_sukses_guru = "Gagal menyimpan absen guru: " . mysqli_error($koneksi);
                }
            }
        }
        // --- AKHIR DARI KODINGAN ASLIMU ---
    }
}

// ----------------------------------------
// Proses simpan presensi siswa
// ----------------------------------------
$pesan_sukses = "";
if (isset($_POST['simpan_presensi'])) {
    $status_array = isset($_POST['status']) ? $_POST['status'] : [];
    $hari_input   = isset($_POST['hari']) ? $_POST['hari'] : 'SENIN';
    $jam_input    = ($hari_input == 'SABTU') ? 'Ekstrakurikuler' : (isset($_POST['jam_pelajaran']) ? $_POST['jam_pelajaran'] : '06.20 - 07.00 (Sholat Dhuha & Tadarus)');
    
    $jam_input_safe   = mysqli_real_escape_string($koneksi, $jam_input);
    $hari_input_safe = mysqli_real_escape_string($koneksi, $hari_input);
    
    foreach ($status_array as $nis_key => $status_val) {
        if (is_array($status_val)) {
            $status_kehadiran = isset($status_val[0]) ? $status_val[0] : 'Hadir';
        } else {
            $status_kehadiran = $status_val;
        }

        $nis               = mysqli_real_escape_string($koneksi, $nis_key);
        $status_kehadiran  = mysqli_real_escape_string($koneksi, $status_kehadiran);
        
        $cek_absen = mysqli_query($koneksi, "SELECT * FROM tb_presensi WHERE nis = '$nis' AND tanggal = '$tanggal_hari_ini' AND jam_pelajaran = '$jam_input_safe'");
        
        if (mysqli_num_rows($cek_absen) > 0) {
            mysqli_query($koneksi, "UPDATE tb_presensi SET status = '$status_kehadiran', kelas = '$nama_kelas_aktif' WHERE nis = '$nis' AND tanggal = '$tanggal_hari_ini' AND jam_pelajaran = '$jam_input_safe'");
        } else {
            mysqli_query($koneksi, "INSERT INTO tb_presensi (nis, kelas, tanggal, jam_pelajaran, status) VALUES ('$nis', '$nama_kelas_aktif', '$tanggal_hari_ini', '$jam_input_safe', '$status_kehadiran')");
        }
    }
    $pesan_sukses = "Data presensi hari " . $hari_input . " (" . $jam_input . ") berhasil disimpan!";
}

$query_dropdown_kelas = mysqli_query($koneksi, "SELECT * FROM tb_kelas");
$query_siswa = null;
if (!empty($id_kelas_aktif)) {
    $query_siswa = mysqli_query($koneksi, "SELECT tb_siswa.*, tb_kelas.nama_kelas FROM tb_siswa JOIN tb_kelas ON tb_siswa.id_kelas = tb_kelas.id_kelas WHERE tb_siswa.id_kelas = '$id_kelas_aktif' ORDER BY tb_siswa.nama_siswa ASC");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda & Presensi - MA Al-Ikhlash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; color: #334155; }
        .main-container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        .card-custom { border: none; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
        .weather-card { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border-radius: 16px; border: none; }
        .section-box { display: none; }
        .section-box.active { display: block; }
        #webcam-video { width: 100%; max-width: 320px; height: 240px; border-radius: 12px; background: #000; object-fit: cover; }
        #canvas-capture { display: none; } 
    @media (max-width: 768px) {
    .main-container {
        max-width: 100% !important;
        padding: 10px !important;
    }
    .card-custom, .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .btn-danger, .badge {
        margin-top: 10px !important;
    }
}
    </style>
</head>
<body>

    <div class="main-container">
        <!-- Header Informasi & Beranda -->
        <div class="card card-custom p-4 mb-4 d-flex flex-row justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-success"><i class="bi bi-speedometer2 me-2"></i>Beranda & Sistem Presensi</h4>
                <p class="text-muted small mb-0">Petugas / Pengajar: <span class="fw-bold text-dark"><?= htmlspecialchars($nama_petugas); ?></span></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success px-3 py-2 fs-6"><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y'); ?> | <span id="live-clock"><?= date('H:i:s'); ?></span></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm fw-semibold">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>

        <!-- Widget Informasi Cuaca & Navigasi Cepat -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card weather-card p-4 h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-cloud-sun-fill me-2"></i> Informasi Cuaca Hari Ini</h6>
                        <span class="badge bg-white text-primary px-2 py-1 fw-bold" style="font-size: 11px;">Cicalengka</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="fs-2 fw-bold me-3" id="suhu-cuaca">--°C</div>
                        <div>
                            <div class="fw-semibold small" id="deskripsi-cuaca">Memuat cuaca...</div>
                            <p class="mb-0 opacity-75" style="font-size: 11px;">Wilayah sekitar sekolah.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-grid-fill me-2"></i> Menu Navigasi Cepat</h6>
                        <p class="text-muted small mb-3">"Silakan pilih menu di bawah ini untuk melakukan presensi guru atau menginput kehadiran siswa."</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="switchTab('guru')" id="btnTabGuru" class="btn btn-outline-success btn-sm w-50 fw-bold">
                            <i class="bi bi-person-check me-1"></i> Absen Guru
                        </button>
                        <button onclick="switchTab('siswa')" id="btnTabSiswa" class="btn btn-outline-primary btn-sm w-50 fw-bold">
                            <i class="bi bi-people me-1"></i> Absen Siswa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOTAK 1: ABSEN MANDIRI GURU -->
        <div id="sectionGuru" class="section-box active">
            <div class="card card-custom p-4 mb-4 border border-success">
                <h5 class="fw-bold text-success mb-2"><i class="bi bi-person-check-fill me-2"></i>Absen Mandiri Guru / Petugas (Live Camera)</h5>
                <!-- TEMPEL KODINGAN PREVIEW FOTO DI SINI -->
                <div class="mb-3 text-center bg-light p-2 rounded border">
                    <span class="badge bg-success mb-1">Lihat Jadwal Pelajaran Sekolah</span>
                    <?php
                    $foto_aktif = file_exists('uploads/info_jadwal.txt') ? trim(file_get_contents('uploads/info_jadwal.txt')) : '';
                    if (!empty($foto_aktif) && file_exists('uploads/' . $foto_aktif)): 
                    ?>
                        <div>
                            <a href="lihat_foto.php?file=<?= $foto_aktif; ?>">
    <img src="uploads/<?= $foto_aktif; ?>" alt="Jadwal Sekolah" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                            </a>
                            <p class="small text-muted mt-1 mb-0" style="font-size: 11px;">Klik gambar untuk memperbesar</p>
                        </div>
                    <?php else: ?>
                        <p class="small text-danger fst-italic mb-0">Jadwal belum diunggah admin.</p>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-3">Sistem otomatis mencatat Jam Masuk (sebelum jam 12.00) atau Jam Pulang (jam 12.00 ke atas) beserta foto.</p>
                <?php if (!empty($pesan_sukses_guru)): ?>
                    <div class="alert <?= $is_blocked ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show py-2 small" role="alert">
                        <i class="bi <?= $is_blocked ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'; ?> me-2"></i> <?= $pesan_sukses_guru; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="input_presensi.php?tab=guru" method="POST" id="formAbsenGuru" onsubmit="return validateFormGuru()">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary small">NAMA GURU (Otomatis):</label>
                            <input type="text" name="nama_guru" class="form-control fw-bold text-success bg-light" value="<?= htmlspecialchars($nama_petugas); ?>" readonly>

                            <label class="form-label fw-bold text-secondary small mt-3">HARI (Otomatis):</label>
<input type="text" name="hari_guru" class="form-control fw-bold text-primary bg-light" value="<?php echo $hari_aktif; ?>" readonly>

                            <!-- PILIH STATUS KEHADIRAN -->
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-bold">STATUS KEHADIRAN:</label>
                                <select name="status" class="form-select" required>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                </select>
                            </div>

                            <!-- PILIH SESI / KETERANGAN -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">SESI / KETERANGAN:</label>
                                <select name="jam_pelajaran" class="form-select" required>
                                    <option value="Pagi (07:00 - 12:00)">Pagi (07:00 - 12:00)</option>
                                    <option value="Siang (12:00 - 17:00)">Siang (12:00 - 17:00)</option>
                                </select>
                            </div>

                            <label class="form-label fw-bold text-secondary small mt-3">KETERANGAN WAKTU (Otomatis):</label>
                            <input type="text" name="keterangan_waktu" id="keteranganWaktuInput" class="form-control fw-bold text-primary bg-light" value="<?= $jenis_absen_otomatis; ?>" readonly>
                        </div>

                        <!-- Bagian Kamera Webcam -->
                        <div class="col-md-8 text-center bg-light p-3 rounded-4 border">
                            <label class="form-label fw-bold text-secondary small d-block mb-2">AMBIL FOTO KEHADIRAN (WEBCAM LANGSUNG):</label>
                            
                            <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                                <div>
                                    <video id="webcam-video" autoplay playsinline></video>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="ambilFoto()">
                                            <i class="bi bi-camera-fill me-1"></i> Ambil Foto
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <canvas id="canvas-capture"></canvas>
                                    <div id="preview-container">
                                        <div id="placeholder-foto" class="border border-dashed rounded-3 d-flex flex-column justify-content-center align-items-center bg-white text-muted small" style="width: 320px; height: 240px;">
                                            <i class="bi bi-person-bounding-box fs-1 mb-1"></i>
                                            <span>Hasil foto akan tampil di sini</span>
                                        </div>
                                        <img id="img-result" src="" alt="Preview Foto" class="rounded-3 d-none" style="width: 320px; height: 240px; object-fit: cover;" />
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="image_captured" id="image_captured">

                            <div class="mt-4">
                                <button type="submit" name="simpan_absen_guru_mandiri" class="btn btn-success px-5 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-check-circle me-1"></i> Kirim Absen & Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Javascript Webcam & Tab Switcher Pendukung -->
    <script>
        const video = document.getElementById('webcam-video');
        const canvas = document.getElementById('canvas-capture');
        const imageCapturedInput = document.getElementById('image_captured');
        const placeholderFoto = document.getElementById('placeholder-foto');
        const imgResult = document.getElementById('img-result');

        // Nyalakan Kamera
        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.error("Kesalahan akses kamera: ", err);
            });

        function ambilFoto() {
            canvas.width = video.videoWidth || 320;
            canvas.height = video.videoHeight || 240;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const dataURL = canvas.toDataURL('image/png');
            imageCapturedInput.value = dataURL;

            imgResult.src = dataURL;
            imgResult.classList.remove('d-none');
            placeholderFoto.classList.add('d-none');
        }

        function validateFormGuru() {
            if (!imageCapturedInput.value) {
                alert('Silakan ambil foto kehadiran terlebih dahulu menggunakan kamera!');
                return false;
            }
            return true;
        }

        function switchTab(tabName) {
            const sectionGuru = document.getElementById('sectionGuru');
            if(tabName === 'guru') {
                sectionGuru.classList.add('active');
            }
        }
    </script>
</body>
</html>

       <!-- KOTAK 2: ABSEN SISWA -->
<div id="sectionSiswa" class="section-box">
    <div class="card card-custom p-4 mb-4 border border-primary">
        <h5 class="fw-bold text-primary mb-2"><i class="bi bi-people-fill me-2"></i>Form Presensi Berdasarkan Jadwal Siswa</h5>
        <p class="text-muted small mb-3">Pilih hari dan kelas untuk memunculkan daftar siswa..</p>

      <form method="GET" action="input_presensi.php" class="row g-3" id="formFilter">
    <input type="hidden" name="tab" value="siswa">
    
  <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">PILIH HARI:</label>
                <input type="hidden" name="hari" value="<?= $pilih_hari; ?>">
                <input type="text" class="form-control form-select-sm fw-bold text-primary bg-light" value="<?= $pilih_hari; ?>" readonly style="height: 38px;">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">PILIH KELAS:</label>
                <select name="id_kelas" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <option value="">-- Pilih Kelas --</option>
                    <?php 
                    mysqli_data_seek($query_dropdown_kelas, 0);
                    while($k = mysqli_fetch_assoc($query_dropdown_kelas)): 
                    ?>
                        <option value="<?= $k['id_kelas']; ?>" <?= ($id_kelas_aktif == $k['id_kelas']) ? 'selected' : ''; ?>>
                            <?= $k['nama_kelas']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan_sukses; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card card-custom p-4">
        <form action="input_presensi.php?tab=siswa&id_kelas=<?= $id_kelas_aktif; ?>&hari=<?= $pilih_hari; ?>" method="POST">
            <input type="hidden" name="jam_pelajaran" value="<?= htmlspecialchars($jam_pelajaran); ?>">
            <input type="hidden" name="hari" value="<?= htmlspecialchars($pilih_hari); ?>">

            <div class="alert alert-info py-2 small mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-info-circle-fill me-1"></i> Hari: <b><?= $pilih_hari; ?></b> | Kelas: <b><?= $nama_kelas_aktif; ?></b> | Kegiatan: <b><?= $jam_pelajaran; ?></b>
                </div>
                <?php 
                $is_libur = ($pilih_hari == 'SABTU' || $pilih_hari == 'MINGGU');
                if (!empty($id_kelas_aktif) && $query_siswa && mysqli_num_rows($query_siswa) > 0 && !$is_libur): 
                ?>
                <div>
                    <button type="button" class="btn btn-outline-success btn-sm fw-bold me-1" onclick="pilihSemuaStatus('Hadir')">Pilih Semua Hadir</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold" onclick="resetSemuaStatus()">Reset</button>
                </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">NO</th>
                            <th width="10%">NIS</th>
                            <th width="22%">NAMA SISWA</th>
                            <th width="13%">JENIS KELAMIN</th>
                            <th width="10%">KELAS</th>
                            <th width="10%">HARI</th>
                            <th width="13%">TANGGAL</th>
                            <th width="17%">PILIH KEHADIRAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($is_libur) {
                            echo "<tr><td colspan='8' class='text-center text-danger fw-bold py-4'>
                                <i class='bi bi-exclamation-triangle-fill me-2'></i> HARI " . strtoupper($pilih_hari) . " LIBUR, TIDAK DAPAT MELAKUKAN PRESENSI SISWA.
                            </td></tr>";
                        } elseif (!empty($id_kelas_aktif) && $query_siswa && mysqli_num_rows($query_siswa) > 0) {
                            $no = 1;
                            while ($siswa = mysqli_fetch_assoc($query_siswa)) {
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td>
                                <?= $siswa['nis']; ?>
                                <input type="hidden" name="nis[]" value="<?= $siswa['nis']; ?>">
                            </td>
                            <td><?= $siswa['nama_siswa']; ?></td>
                            <td class="text-center"><?= isset($siswa['jenis_kelamin']) ? $siswa['jenis_kelamin'] : '-'; ?></td>
                            <td class="text-center"><?= $siswa['nama_kelas']; ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $pilih_hari; ?></span></td>
                            <td class="text-center"><?= date('d-m-Y', strtotime($tanggal_hari_ini)); ?></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="status[<?= $siswa['nis']; ?>]" value="Hadir" id="hadir_<?= $siswa['nis']; ?>" checked>
                                    <label class="btn btn-outline-success btn-sm" for="hadir_<?= $siswa['nis']; ?>">Hadir</label>

                                    <input type="radio" class="btn-check" name="status[<?= $siswa['nis']; ?>]" value="Izin" id="izin_<?= $siswa['nis']; ?>">
                                    <label class="btn btn-outline-primary btn-sm" for="izin_<?= $siswa['nis']; ?>">Izin</label>

                                    <input type="radio" class="btn-check" name="status[<?= $siswa['nis']; ?>]" value="Sakit" id="sakit_<?= $siswa['nis']; ?>">
                                    <label class="btn btn-outline-warning btn-sm" for="sakit_<?= $siswa['nis']; ?>">Sakit</label>

                                    <input type="radio" class="btn-check" name="status[<?= $siswa['nis']; ?>]" value="Alpa" id="alpa_<?= $siswa['nis']; ?>">
                                    <label class="btn btn-outline-danger btn-sm" for="alpa_<?= $siswa['nis']; ?>">Alpa</label>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } else {
                            echo "<tr><td colspan='8' class='text-center text-muted py-4'>Silakan pilih kelas terlebih dahulu pada menu di atas.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($id_kelas_aktif) && $query_siswa && mysqli_num_rows($query_siswa) > 0 && !$is_libur): ?>
                <div class="text-end mt-4">
                    <button type="submit" name="simpan_presensi" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Data Presensi
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    initWebcam();
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('tab') && urlParams.get('tab') === 'guru') {
        switchTab('guru');
    } else if ((urlParams.has('tab') && urlParams.get('tab') === 'siswa') || urlParams.has('id_kelas')) {
        switchTab('siswa');
    } else {
        switchTab('guru'); 
    }
});

function initWebcam() {
    const video = document.getElementById('webcam-video');
    if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.log("Kamera tidak dapat diakses: ", err);
            });
    }
}

function ambilFoto() {
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('canvas-capture');
    const context = canvas.getContext('2d');
    const imageCapturedInput = document.getElementById('image_captured');
    const placeholder = document.getElementById('placeholder-foto');
    const imgResult = document.getElementById('img-result');

    canvas.width = 320;
    canvas.height = 240;
    context.drawImage(video, 0, 0, 320, 240);

    const dataURL = canvas.toDataURL('image/png');
    imageCapturedInput.value = dataURL;

    placeholder.classList.add('d-none');
    imgResult.src = dataURL;
    imgResult.classList.remove('d-none');
}

function validateFormGuru() {
    const captured = document.getElementById('image_captured').value;
    if(!captured) {
        alert('Silakan ambil foto wajah terlebih dahulu dengan menekan tombol "Ambil Foto"!');
        return false;
    }
    return true;
}

function switchTab(target) {
    const secGuru = document.getElementById('sectionGuru');
    const secSiswa = document.getElementById('sectionSiswa');
    const btnGuru = document.getElementById('btnTabGuru');
    const btnSiswa = document.getElementById('btnTabSiswa');

    if (target === 'guru') {
        secGuru.classList.add('active');
        secSiswa.classList.remove('active');
        btnGuru.className = 'btn btn-success btn-sm w-50 fw-bold';
        btnSiswa.className = 'btn btn-outline-primary btn-sm w-50 fw-bold';
    } else {
        secGuru.classList.remove('active');
        secSiswa.classList.add('active');
        btnGuru.className = 'btn btn-outline-success btn-sm w-50 fw-bold';
        btnSiswa.className = 'btn btn-primary btn-sm w-50 fw-bold';
    }
}

function handleHariChange() {
    let hari = document.getElementById('pilihHari').value;
    let jamSelect = document.getElementById('pilihJam');
    
    if (jamSelect) {
        if (hari === 'SABTU') {
            jamSelect.innerHTML = '<option value="Ekstrakurikuler" selected>Ekstrakurikuler</option>';
            jamSelect.disabled = true;
        } else {
            jamSelect.disabled = false;
        }
    }
    document.getElementById('formFilter').submit();
}

function pilihSemuaStatus(statusVal) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${statusVal}"]`);
    radios.forEach(radio => {
        radio.checked = true;
    });
}

function resetSemuaStatus() {
    const radios = document.querySelectorAll('input[type="radio"][value="Hadir"]');
    radios.forEach(radio => {
        radio.checked = true;
    });
}
</script>

<script>
    setInterval(() => {
        const now = new Date();
        document.getElementById('live-clock').innerText = now.toTimeString().split(' ')[0];
    }, 1000);

    fetch('https://api.open-meteo.com/v1/forecast?latitude=-6.9745&longitude=107.7522&current_weather=true&timezone=Asia/Jakarta')
        .then(response => response.json())
        .then(data => {
            if(data.current_weather) {
                document.getElementById('suhu-cuaca').innerText = Math.round(data.current_weather.temperature) + '°C';
                let wCode = data.current_weather.weathercode;
                let ket = "Cerah / Berawan";
                if(wCode > 2 && wCode < 50) ket = "Berawan";
                if(wCode >= 50) ket = "Hujan Ringan";
                document.getElementById('deskripsi-cuaca').innerText = ket;
            }
        })
        .catch(() => {
            document.getElementById('deskripsi-cuaca').innerText = "Cuaca lokal";
        });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
