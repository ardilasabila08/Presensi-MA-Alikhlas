<?php
// Tangkap parameter filter di bagian paling atas agar siap digunakan oleh tombol cetak dan query
$filter_type    = $_GET['filter_type'] ?? 'semua';
$tanggal_harian = $_GET['tanggal_harian'] ?? date('Y-m-d');
$bulan_tahun    = $_GET['bulan_tahun'] ?? date('Y-m');
$tgl_mulai      = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai    = $_GET['tgl_selesai'] ?? date('Y-m-d');
$is_filtered    = isset($_GET['tampilkan']); // Cek apakah tombol Tampilkan sudah diklik

// Inisialisasi awal variabel modal array agar tidak error undefined saat halaman pertama kali dibuka
$modal_array = [];

// Deteksi Primary Key secara otomatis
$result_pk = mysqli_query($koneksi, "SHOW KEYS FROM tb_kehadiran_guru WHERE Key_name = 'PRIMARY'");
$pk_field = 'id';
if ($row_pk = mysqli_fetch_assoc($result_pk)) {
    $pk_field = $row_pk['Column_name'];
}

// Proses Hapus Rekap Kehadiran Guru / Petugas
if (isset($_GET['hapus_absen'])) {
    $id_val = mysqli_real_escape_string($koneksi, $_GET['hapus_absen']);
    
    $q_foto = mysqli_query($koneksi, "SELECT foto, foto_masuk, foto_pulang, foto_keluar FROM tb_kehadiran_guru WHERE $pk_field = '$id_val'");
    if($f_data = mysqli_fetch_assoc($q_foto)){
        foreach(['foto', 'foto_masuk', 'foto_pulang', 'foto_keluar'] as $col) {
            if(!empty($f_data[$col]) && file_exists('uploads/' . $f_data[$col])){
                unlink('uploads/' . $f_data[$col]);
            }
        }
    }

    mysqli_query($koneksi, "DELETE FROM tb_kehadiran_guru WHERE $pk_field = '$id_val'");
    echo "<script>alert('Data kehadiran berhasil dihapus!'); window.location='admin.php?page=rekap_guru';</script>";
}

// Proses Update Data Kehadiran Guru via Modal
if (isset($_POST['update_absen_guru'])) {
    $id_edit     = mysqli_real_escape_string($koneksi, $_POST['id_kehadiran']);
    $tgl_edit    = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $hari_edit   = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $nama_edit   = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
    $stat_edit   = mysqli_real_escape_string($koneksi, $_POST['status']);
    $jam_plj     = mysqli_real_escape_string($koneksi, $_POST['jam_pelajaran']);
    $jam_msk_edt = mysqli_real_escape_string($koneksi, $_POST['jam_masuk']);
    $jam_plg_edt = mysqli_real_escape_string($koneksi, $_POST['jam_pulang']);

    $upd = "UPDATE tb_kehadiran_guru SET 
            tanggal = '$tgl_edit',
            hari = '$hari_edit',
            nama_guru = '$nama_edit',
            status = '$stat_edit',
            jam_pelajaran = '$jam_plj',
            jam_masuk = '$jam_msk_edt',
            jam_pulang = '$jam_plg_edt' 
            WHERE $pk_field = '$id_edit'";
            
    if(mysqli_query($koneksi, $upd)){
        echo "<script>alert('Data kehadiran guru berhasil diperbarui!'); window.location='admin.php?page=rekap_guru';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: ".mysqli_error($koneksi)."');</script>";
    }
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-journal-check text-success me-2"></i>Rekap Kehadiran Guru & Petugas</h4>
        
        <!-- Tombol Aksi Kanan (Kembali ke Halaman & Cetak Laporan) -->
        <div class="d-flex gap-2">
            <a href="admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman
            </a>
            <a href="cetak_rekap_guru.php?filter_type=<?= $filter_type; ?>&tanggal_harian=<?= $tanggal_harian; ?>&bulan_tahun=<?= $bulan_tahun; ?>&tgl_mulai=<?= $tgl_mulai; ?>&tgl_selesai=<?= $tgl_selesai; ?>" target="_blank" class="btn btn-success">
                <i class="bi bi-printer me-1"></i> Cetak Laporan
            </a>
        </div>
    </div>
    
    <div class="card card-custom p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="fw-bold mb-1">Daftar Log Kehadiran Mengajar & Tugas</h5>
                <p class="text-muted small mb-0">Berikut adalah rekap catatan kehadiran guru dan petugas lengkap dengan bukti foto dan jam presensi otomatis.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge bg-success p-2 fs-6">
                    <i class="bi bi-calendar-date me-1"></i> Hari Ini: <?= date('d-m-Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- FORM FILTER -->
    <div class="card card-custom p-3 mb-4 border-success">
        <form method="GET" action="admin.php" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="rekap_guru">
            
            <div class="col-md-3">
                <label class="form-label fw-bold small text-success">PILIH FILTER:</label>
                <select name="filter_type" id="filterType" class="form-select form-select-sm" onchange="toggleFilterInput()">
                    <option value="semua" <?= ($filter_type == 'semua') ? 'selected' : ''; ?>>Semua Data</option>
                    <option value="harian" <?= ($filter_type == 'harian') ? 'selected' : ''; ?>>Rekap Per Hari</option>
                    <option value="bulanan" <?= ($filter_type == 'bulanan') ? 'selected' : ''; ?>>Rekap Per Bulan</option>
                    <option value="rentang" <?= ($filter_type == 'rentang') ? 'selected' : ''; ?>>Rentang Tanggal (Mingguan)</option>
                </select>
            </div>

            <div class="col-md-3 filter-input" id="inputHarian" style="display: none;">
                <label class="form-label fw-bold small text-success">PILIH TANGGAL:</label>
                <input type="date" name="tanggal_harian" class="form-control form-control-sm" value="<?= $tanggal_harian; ?>">
            </div>

            <div class="col-md-3 filter-input" id="inputBulanan" style="display: none;">
                <label class="form-label fw-bold small text-success">PILIH BULAN & TAHUN:</label>
                <input type="month" name="bulan_tahun" class="form-control form-control-sm" value="<?= $bulan_tahun; ?>">
            </div>

            <div class="col-md-2 filter-input" id="inputMulai" style="display: none;">
                <label class="form-label fw-bold small text-success">DARI TANGGAL:</label>
                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= $tgl_mulai; ?>">
            </div>
            <div class="col-md-2 filter-input" id="inputSampai" style="display: none;">
                <label class="form-label fw-bold small text-success">SAMPAI TANGGAL:</label>
                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= $tgl_selesai; ?>">
            </div>

            <div class="col-md-2">
                <button type="submit" name="tampilkan" value="1" class="btn btn-success btn-sm w-100 fw-bold shadow-sm"><i class="bi bi-search me-1"></i> Tampilkan</button>
            </div>
        </form>
    </div>

    <script>
    function toggleFilterInput() {
        let type = document.getElementById('filterType').value;
        document.getElementById('inputHarian').style.display = (type === 'harian') ? 'block' : 'none';
        document.getElementById('inputBulanan').style.display = (type === 'bulanan') ? 'block' : 'none';
        document.getElementById('inputMulai').style.display = (type === 'rentang') ? 'block' : 'none';
        document.getElementById('inputSampai').style.display = (type === 'rentang') ? 'block' : 'none';
    }
    document.addEventListener("DOMContentLoaded", toggleFilterInput);
    </script>

    <!-- KONDISI: Jika tombol Tampilkan belum diklik, tampilkan kotak informasi bersih -->
    <?php if (!$is_filtered): ?>
        <div class="alert alert-info border-0 shadow-sm text-center py-5">
            <i class="bi bi-info-circle fs-2 text-info mb-2 d-block"></i>
            <h5 class="fw-bold">Silakan Pilih Filter Rekap Terlebih Dahulu</h5>
            <p class="text-muted mb-0">Tentukan pilihan filter di atas, lalu klik tombol <b>Tampilkan</b> untuk melihat data rekap kehadiran.</p>
        </div>
    <?php else: ?>
        <!-- TABEL RIWAYAT ABSEN MUNCUL SETELAH TOMBOL DITEKAN -->
        <div class="card card-custom p-4">
            <h5 class="fw-bold mb-3">Tabel Riwayat Absen Guru & Petugas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="4%">NO</th>
                            <th width="10%">TANGGAL</th>
                            <th width="8%">HARI</th>
                            <th>NAMA GURU / PETUGAS</th>
                            <th width="16%">SESI / KETERANGAN</th>
                            <th width="10%" class="text-center">STATUS</th>
                            <th width="13%" class="text-center">FOTO MASUK</th>
                            <th width="13%" class="text-center">FOTO PULANG</th>
                            <th width="12%" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where_query = "1=1"; 

                        if ($filter_type == 'harian' && !empty($tanggal_harian)) {
                            $tgl = mysqli_real_escape_string($koneksi, $tanggal_harian);
                            $where_query = "tanggal = '$tgl'";
                        } elseif ($filter_type == 'bulanan' && !empty($bulan_tahun)) {
                            $bln = mysqli_real_escape_string($koneksi, $bulan_tahun);
                            $where_query = "tanggal LIKE '$bln%'";
                        } elseif ($filter_type == 'rentang' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
                            $mulai = mysqli_real_escape_string($koneksi, $tgl_mulai);
                            $selesai = mysqli_real_escape_string($koneksi, $tgl_selesai);
                            $where_query = "tanggal BETWEEN '$mulai' AND '$selesai'";
                        }

                        $no = 1;
                        $q_rekap = mysqli_query($koneksi, "SELECT * FROM tb_kehadiran_guru WHERE $where_query ORDER BY tanggal DESC, $pk_field DESC");

                        if(mysqli_num_rows($q_rekap) > 0) {
                            while($r = mysqli_fetch_assoc($q_rekap)) {
                                $id_val = $r[$pk_field];

                                $badge_color = 'bg-secondary';
                                if ($r['status'] == 'Hadir') $badge_color = 'bg-success';
                                elseif ($r['status'] == 'Izin') $badge_color = 'bg-warning text-dark';
                                elseif ($r['status'] == 'Sakit') $badge_color = 'bg-info text-dark';

                                $f_masuk  = '';
                                $f_pulang = '';

                                if(!empty($r['foto_masuk'])) $f_masuk = $r['foto_masuk'];
                                if(!empty($r['foto_pulang'])) $f_pulang = $r['foto_pulang'];
                                elseif(!empty($r['foto_keluar'])) $f_pulang = $r['foto_keluar'];

                                if(!empty($r['foto'])) {
                                    $ket_cek = strtolower($r['jam_pelajaran'] ?? '');
                                    if(strpos($ket_cek, 'pulang') !== false || !empty($r['jam_pulang']) || !empty($r['jam_keluar'])) {
                                        if(empty($f_pulang)) $f_pulang = $r['foto'];
                                    } else {
                                        if(empty($f_masuk)) $f_masuk = $r['foto'];
                                    }
                                }

                                $j_masuk  = !empty($r['jam_masuk']) && $r['jam_masuk'] != '00:00:00' ? $r['jam_masuk'] : (!empty($r['jam_absen']) && $r['jam_absen'] != '00:00:00' ? $r['jam_absen'] : '');
                                $j_pulang = !empty($r['jam_pulang']) && $r['jam_pulang'] != '00:00:00' ? $r['jam_pulang'] : (!empty($r['jam_keluar']) && $r['jam_keluar'] != '00:00:00' ? $r['jam_keluar'] : '');
                                
                                $sesi_teks = !empty($r['jam_pelajaran']) ? $r['jam_pelajaran'] : '-';
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= date('d-m-Y', strtotime($r['tanggal'])); ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $r['hari'] ?? '-'; ?></span></td>
                            <td class="fw-bold"><?= htmlspecialchars($r['nama_guru']); ?></td>
                            <td>
                                <span class="text-dark small d-block"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($sesi_teks); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badge_color; ?> px-3 py-2"><?= $r['status']; ?></span>
                            </td>
                            
                            <!-- FOTO MASUK (Mengarahkan ke halaman lihat_foto.php agar ada tombol Kembali) -->
                            <td class="text-center">
                                <?php if(!empty($f_masuk)): ?>
                                    <a href="lihat_foto.php?file=<?= $f_masuk; ?>" target="_blank">
                                        <img src="uploads/<?= $f_masuk; ?>" alt="Foto Masuk" class="img-thumbnail mb-1" style="width: 50px; height: 50px; object-fit: cover;">
                                    </a>
                                    <?php if(!empty($j_masuk)): ?>
                                        <div class="small text-primary fw-bold" style="font-size: 11px;"><i class="bi bi-stopwatch"></i> <?= $j_masuk; ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum absen</span>
                                <?php endif; ?>
                            </td>

                            <!-- FOTO PULANG (Mengarahkan ke halaman lihat_foto.php agar ada tombol Kembali) -->
                            <td class="text-center">
                                <?php if(!empty($f_pulang)): ?>
                                    <a href="lihat_foto.php?file=<?= $f_pulang; ?>" target="_blank">
                                        <img src="uploads/<?= $f_pulang; ?>" alt="Foto Pulang" class="img-thumbnail mb-1" style="width: 50px; height: 50px; object-fit: cover;">
                                    </a>
                                    <?php if(!empty($j_pulang)): ?>
                                        <div class="small text-primary fw-bold" style="font-size: 11px;"><i class="bi bi-stopwatch"></i> <?= $j_pulang; ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum absen</span>
                                <?php endif; ?>
                            </td>

                            <!-- AKSI -->
                            <td class="text-center">
                                <button type="button" class="btn btn-warning btn-sm text-white mb-1 btn-edit-manual" data-id="<?= $id_val; ?>" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="admin.php?page=rekap_guru&hapus_absen=<?= $id_val; ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Yakin ingin menghapus data rekap ini?');" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                                    $modal_array[] = ['id' => $id_val, 'row' => $r, 'j_masuk' => $j_masuk, 'j_pulang' => $j_pulang, 'sesi_teks' => $sesi_teks];
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center text-muted py-4'>Belum ada data rekap kehadiran guru atau petugas yang tercatat.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- RENDER MODAL POP-UP -->
<?php foreach ($modal_array as $m): ?>
<div class="custom-modal-backdrop" id="editModal<?= $m['id']; ?>" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; overflow-y: auto;">
    <div class="modal-dialog" style="max-width: 500px; margin: 30px auto;">
        <div class="modal-content shadow-lg bg-white rounded">
            <form action="admin.php?page=rekap_guru" method="POST">
                <div class="modal-header bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold text-success mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Kehadiran Guru & Petugas</h5>
                    <button type="button" class="btn-close btn-close-manual" data-id="<?= $m['id']; ?>" style="background: transparent; border: 0; font-size: 1.2rem; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="id_kehadiran" value="<?= $m['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">TANGGAL:</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $m['row']['tanggal']; ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">JAM MASUK:</label>
                            <input type="text" name="jam_masuk" class="form-control" value="<?= $m['j_masuk']; ?>" placeholder="00:00:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">JAM PULANG:</label>
                            <input type="text" name="jam_pulang" class="form-control" value="<?= $m['j_pulang']; ?>" placeholder="00:00:00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">HARI:</label>
                        <select name="hari" class="form-select" required>
                            <?php $h_val = $m['row']['hari'] ?? 'SENIN'; ?>
                            <option value="SENIN" <?= ($h_val == 'SENIN') ? 'selected' : ''; ?>>SENIN</option>
                            <option value="SELASA" <?= ($h_val == 'SELASA') ? 'selected' : ''; ?>>SELASA</option>
                            <option value="RABU" <?= ($h_val == 'RABU') ? 'selected' : ''; ?>>RABU</option>
                            <option value="KAMIS" <?= ($h_val == 'KAMIS') ? 'selected' : ''; ?>>KAMIS</option>
                            <option value="JUMAT" <?= ($h_val == 'JUMAT') ? 'selected' : ''; ?>>JUMAT</option>
                            <option value="SABTU" <?= ($h_val == 'SABTU') ? 'selected' : ''; ?>>SABTU</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">NAMA GURU / PETUGAS:</label>
                        <select name="nama_guru" class="form-select" required>
                            <?php
                            $q_ptg = mysqli_query($koneksi, "SELECT * FROM petugas");
                            while ($ptg = mysqli_fetch_assoc($q_ptg)) {
                                $sel = ($ptg['nama_lengkap'] == $m['row']['nama_guru']) ? 'selected' : '';
                                echo "<option value='".$ptg['nama_lengkap']."' $sel>".$ptg['nama_lengkap']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">STATUS KEHADIRAN:</label>
                        <select name="status" class="form-select" required>
                            <option value="Hadir" <?= ($m['row']['status'] == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                            <option value="Izin" <?= ($m['row']['status'] == 'Izin') ? 'selected' : ''; ?>>Izin</option>
                            <option value="Sakit" <?= ($m['row']['status'] == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">SESI / KETERANGAN:</label>
                        <select name="jam_pelajaran" class="form-select" required>
                            <?php 
                            $sesi_aktif = $m['sesi_teks'];
                            $pilihan_sesi = [
                                "Full Day (07:00 - 15:00)",
                                "Pagi (07:00 - 12:00)",
                                "Siang (12:00 - 17:00)",
                                "Sesi 1",
                                "Sesi 2",
                                "Sesi 3"
                            ];
                            foreach($pilihan_sesi as $ps) {
                                $s = ($sesi_aktif == $ps) ? 'selected' : '';
                                echo "<option value=\"$ps\" $s>$ps</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light border-top d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm btn-close-manual" data-id="<?= $m['id']; ?>">Batal</button>
                    <button type="submit" name="update_absen_guru" class="btn btn-success btn-sm">Simpan Perubahan</button>
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