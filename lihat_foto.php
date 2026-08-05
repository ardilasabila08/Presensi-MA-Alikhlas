<?php
$file = $_GET['file'] ?? '';
$path = 'uploads/' . basename($file);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Bukti Foto</title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-dark text-white">
    <div class="container py-4">
        <!-- Tombol Kembali di Bagian Atas Pinggir -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="javascript:window.close();" class="btn btn-light btn-sm fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman
            </a>
            <span class="text-muted small">File: <?= htmlspecialchars($file); ?></span>
        </div>

        <!-- Tampilan Gambar Ditengah -->
        <div class="text-center">
            <?php if (!empty($file) && file_exists($path)): ?>
                <img src="<?= $path; ?>" alt="Bukti Foto Absen" class="img-fluid rounded shadow-lg border border-secondary" style="max-height: 80vh;">
            <?php else: ?>
                <div class="alert alert-danger mt-5">File foto tidak ditemukan atau sudah dihapus.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>