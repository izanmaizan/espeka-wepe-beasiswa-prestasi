<?php
$page_title = 'Perhitungan Weighted Product';
require_once '../../includes/header.php';

requireRole('admin');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$hasil_perhitungan = null;
$errors = [];

// Proses perhitungan
if ($step === 2) {
    // Validasi data sebelum perhitungan
    $siswa_list = getAllSiswaAktif($pdo);
    $kriteria_list = getAllKriteria($pdo);
    
    if (empty($siswa_list)) {
        $errors[] = 'Tidak ada data siswa aktif!';
    }
    
    if (empty($kriteria_list)) {
        $errors[] = 'Tidak ada kriteria penilaian!';
    }
    
    // Cek total bobot
    $total_bobot = 0;
    foreach ($kriteria_list as $k) {
        $total_bobot += $k['bobot'];
    }
    
    if (abs($total_bobot - 1.0) >= 0.0001) {
        $errors[] = 'Total bobot kriteria harus sama dengan 1.0000 (saat ini: ' . formatNumber($total_bobot, 4) . ')';
    }
    
    // Cek kelengkapan penilaian per tingkat
    $tingkat_stats = [];
    foreach (['7', '8', '9'] as $tingkat) {
        $siswa_tingkat = getSiswaPerTingkat($pdo, $tingkat);
        $siswa_dinilai_lengkap = 0;
        
        foreach ($siswa_tingkat as $siswa) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM penilaian 
                WHERE siswa_id = ? AND nilai_numerik IS NOT NULL AND nilai_numerik > 0
            ");
            $stmt->execute([$siswa['id']]);
            $jumlah_penilaian = $stmt->fetchColumn();
            
            if ($jumlah_penilaian >= count($kriteria_list)) {
                $siswa_dinilai_lengkap++;
            }
        }
        
        $tingkat_stats[$tingkat] = [
            'total' => count($siswa_tingkat),
            'dinilai' => $siswa_dinilai_lengkap
        ];
        
        if ($siswa_dinilai_lengkap == 0 && count($siswa_tingkat) > 0) {
            $errors[] = "Belum ada siswa kelas $tingkat yang penilaiannya lengkap!";
        }
    }
    
    // Jika tidak ada error, lakukan perhitungan
    if (empty($errors)) {
        $hasil_perhitungan = hitungSemuaTingkat($pdo);
        
        if ($hasil_perhitungan) {
            // Simpan hasil perhitungan
            if (simpanHasilPerhitungan($pdo, $hasil_perhitungan)) {
                setAlert('success', 'Perhitungan berhasil dilakukan dan disimpan!');
            } else {
                $errors[] = 'Perhitungan berhasil namun gagal menyimpan ke database!';
            }
        } else {
            $errors[] = 'Gagal melakukan perhitungan!';
        }
    }
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Laporan & Hasil', 'url' => 'index.php'],
    ['text' => 'Perhitungan WP', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-calculator"></i>
        Perhitungan Weighted Product
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <h6><i class="bi bi-exclamation-triangle"></i> Terdapat masalah:</h6>
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="mt-3">
        <a href="index.php" class="btn btn-sm btn-outline-danger">Kembali ke Laporan</a>
        <a href="../penilaian/index.php" class="btn btn-sm btn-primary">Cek Penilaian</a>
    </div>
</div>
<?php endif; ?>

<?php if ($step === 1): ?>
<!-- Step 1: Persiapan -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-list-check"></i>
                    Persiapan Perhitungan
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Sebelum melakukan perhitungan Weighted Product, pastikan semua data berikut sudah lengkap dan benar:
                </p>

                <?php
                // Check data readiness
                $siswa_list = getAllSiswaAktif($pdo);
                $kriteria_list = getAllKriteria($pdo);
                
                $total_bobot = 0;
                foreach ($kriteria_list as $k) {
                    $total_bobot += $k['bobot'];
                }
                
                // Check per tingkat
                $tingkat_ready = [];
                $total_siswa_dinilai = 0;
                
                foreach (['7', '8', '9'] as $tingkat) {
                    $siswa_tingkat = getSiswaPerTingkat($pdo, $tingkat);
                    $siswa_dinilai = 0;
                    
                    foreach ($siswa_tingkat as $siswa) {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM penilaian 
                            WHERE siswa_id = ? AND nilai_numerik IS NOT NULL AND nilai_numerik > 0
                        ");
                        $stmt->execute([$siswa['id']]);
                        if ($stmt->fetchColumn() >= count($kriteria_list)) {
                            $siswa_dinilai++;
                        }
                    }
                    
                    $tingkat_ready[$tingkat] = [
                        'total' => count($siswa_tingkat),
                        'dinilai' => $siswa_dinilai,
                        'ready' => $siswa_dinilai >= 3 // Minimal 3 siswa untuk bisa dapat top 3
                    ];
                    
                    $total_siswa_dinilai += $siswa_dinilai;
                }
                ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-people text-primary"></i>
                                    Data Siswa Aktif
                                </div>
                                <span class="badge bg-primary rounded-pill"><?php echo count($siswa_list); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-list-check text-success"></i>
                                    Kriteria Penilaian
                                </div>
                                <span class="badge bg-success rounded-pill"><?php echo count($kriteria_list); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-clipboard-data text-info"></i>
                                    Siswa Dinilai Lengkap
                                </div>
                                <span class="badge bg-info rounded-pill"><?php echo $total_siswa_dinilai; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Validasi Bobot Kriteria:</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total Bobot:</span>
                                    <span
                                        class="fw-bold <?php echo abs($total_bobot - 1.0) < 0.0001 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatNumber($total_bobot, 4); ?>
                                    </span>
                                </div>
                                <?php if (abs($total_bobot - 1.0) < 0.0001): ?>
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i> Bobot sudah valid
                                </small>
                                <?php else: ?>
                                <small class="text-danger">
                                    <i class="bi bi-x-circle"></i> Total bobot harus 1.0000
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status per Tingkat -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6>Status Penilaian per Tingkat:</h6>
                        <div class="row">
                            <?php foreach (['7', '8', '9'] as $tingkat): ?>
                            <div class="col-md-4">
                                <div
                                    class="card border-<?php echo $tingkat_ready[$tingkat]['ready'] ? 'success' : 'warning'; ?>">
                                    <div
                                        class="card-header bg-<?php echo $tingkat_ready[$tingkat]['ready'] ? 'success' : 'warning'; ?> text-<?php echo $tingkat_ready[$tingkat]['ready'] ? 'white' : 'dark'; ?>">
                                        <h6 class="mb-0">
                                            <i class="bi bi-<?php echo $tingkat; ?>-circle"></i>
                                            Kelas
                                            <?php echo $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX'); ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <h5><?php echo $tingkat_ready[$tingkat]['total']; ?></h5>
                                                <small class="text-muted">Total Siswa</small>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="text-success">
                                                    <?php echo $tingkat_ready[$tingkat]['dinilai']; ?></h5>
                                                <small class="text-muted">Sudah Dinilai</small>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span
                                                class="badge <?php echo $tingkat_ready[$tingkat]['ready'] ? 'bg-success' : 'bg-warning'; ?> w-100">
                                                <?php echo $tingkat_ready[$tingkat]['ready'] ? 'Siap Hitung' : 'Perlu Dilengkapi'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php
                $can_calculate = count($siswa_list) > 0 && count($kriteria_list) > 0 && 
                                abs($total_bobot - 1.0) < 0.0001 && $total_siswa_dinilai >= 10;
                ?>

                <div class="mt-4">
                    <?php if ($can_calculate): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <strong>Siap untuk perhitungan!</strong>
                        <ul class="mb-0 mt-2">
                            <li>Sistem akan menghitung ranking per tingkat (top 3 per tingkat)</li>
                            <li>Sistem akan menghitung ranking global (top 10 keseluruhan)</li>
                            <li>Total kandidat penerima: 10 siswa terbaik dari semua tingkat</li>
                        </ul>
                    </div>
                    <div class="d-grid">
                        <a href="?step=2" class="btn btn-success btn-lg">
                            <i class="bi bi-calculator"></i>
                            Mulai Perhitungan Weighted Product
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Belum siap untuk perhitungan.</strong>
                        <p class="mb-2">Silakan lengkapi data yang diperlukan:</p>
                        <ul class="mb-0">
                            <?php if (count($siswa_list) === 0): ?>
                            <li>Data siswa aktif (minimal 10 siswa)</li>
                            <?php endif; ?>
                            <?php if (count($kriteria_list) === 0): ?>
                            <li>Kriteria penilaian</li>
                            <?php endif; ?>
                            <?php if (abs($total_bobot - 1.0) >= 0.0001): ?>
                            <li>Total bobot kriteria harus = 1.0000</li>
                            <?php endif; ?>
                            <?php if ($total_siswa_dinilai < 10): ?>
                            <li>Minimal 10 siswa sudah dinilai lengkap (saat ini: <?php echo $total_siswa_dinilai; ?>)
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="btn-group d-grid gap-2">
                        <?php if (count($siswa_list) === 0): ?>
                        <a href="../siswa/tambah.php" class="btn btn-outline-primary">Tambah Data Siswa</a>
                        <?php endif; ?>
                        <?php if (count($kriteria_list) === 0): ?>
                        <a href="../kriteria/tambah.php" class="btn btn-outline-success">Tambah Kriteria</a>
                        <?php endif; ?>
                        <?php if ($total_siswa_dinilai < count($siswa_list)): ?>
                        <a href="../penilaian/index.php" class="btn btn-outline-info">Lengkapi Penilaian</a>
                        <?php endif; ?>
                        <?php if (abs($total_bobot - 1.0) >= 0.0001): ?>
                        <a href="../kriteria/index.php" class="btn btn-outline-warning">Perbaiki Bobot Kriteria</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Tentang Sistem Beasiswa
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-trophy"></i> Sistem Penerima Beasiswa:</h6>
                    <ul class="mb-0 small">
                        <li><strong>3 Terbaik per Tingkat:</strong> Setiap kelas (VII, VIII, IX) akan dipilih 3 siswa
                            terbaik</li>
                        <li><strong>10 Terbaik Global:</strong> Dari semua siswa akan dipilih 10 terbaik keseluruhan
                        </li>
                        <li><strong>Metode:</strong> Weighted Product untuk ranking objektif</li>
                    </ul>
                </div>

                <h6>Langkah Perhitungan:</h6>
                <ol class="small text-muted">
                    <li>Hitung nilai S per siswa per tingkat</li>
                    <li>Hitung nilai V (normalisasi)</li>
                    <li>Ranking per tingkat (ambil top 3)</li>
                    <li>Ranking global semua siswa (ambil top 10)</li>
                </ol>

                <div class="mt-3">
                    <h6>Formula Weighted Product:</h6>
                    <div class="bg-light p-2 rounded">
                        <code class="small">
                            S(Ai) = Π (Xij)^Wj<br>
                            V(Ai) = S(Ai) / Σ S(Ai)
                        </code>
                    </div>
                    <small class="text-muted">
                        <strong>S</strong> = Skor siswa, <strong>V</strong> = Nilai ternormalisasi,<br>
                        <strong>X</strong> = Nilai kriteria, <strong>W</strong> = Bobot kriteria
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($step === 2 && $hasil_perhitungan): ?>
<!-- Step 2: Hasil Perhitungan -->
<div class="alert alert-success">
    <i class="bi bi-check-circle"></i>
    <strong>Perhitungan Berhasil!</strong>
    Hasil perhitungan Weighted Product telah selesai dan disimpan.
</div>

<!-- Statistik Hasil -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo count($hasil_perhitungan); ?></h4>
                <small class="text-muted">Total Siswa Dinilai</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-star text-success" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0">9</h4>
                <small class="text-muted">Top 3 per Tingkat</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-trophy text-warning" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0">10</h4>
                <small class="text-muted">Penerima Global</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="bi bi-calendar text-info" style="font-size: 2rem;"></i>
                <h6 class="mt-2 mb-0"><?php echo date('d/m/Y'); ?></h6>
                <small class="text-muted">Tanggal Perhitungan</small>
            </div>
        </div>
    </div>
</div>

<!-- Top 10 Global -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-trophy-fill text-warning"></i>
            Top 10 Penerima Beasiswa Global
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ranking Global</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th>Skor V</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $top_10_global = array_slice($hasil_perhitungan, 0, 10, true);
                    foreach ($top_10_global as $hasil): 
                    ?>
                    <tr class="table-success">
                        <td>
                            <span class="badge <?php 
                                echo $hasil['ranking_global'] == 1 ? 'bg-warning' : 
                                     ($hasil['ranking_global'] == 2 ? 'bg-secondary' : 
                                     ($hasil['ranking_global'] == 3 ? 'bg-primary' : 'bg-success')); 
                            ?> fs-6">
                                <?php echo $hasil['ranking_global']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($hasil['siswa']['nis']); ?></td>
                        <td><strong><?php echo htmlspecialchars($hasil['siswa']['nama']); ?></strong></td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($hasil['siswa']['kelas']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">Tingkat <?php echo $hasil['tingkat']; ?></span>
                        </td>
                        <td><strong><?php echo formatNumber($hasil['skor_v'], 6); ?></strong></td>
                        <td>
                            <span class="badge bg-success">
                                <i class="bi bi-trophy"></i> Penerima Beasiswa
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top 3 per Tingkat -->
<div class="row">
    <?php foreach (['7', '8', '9'] as $tingkat): ?>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-star-fill text-success"></i>
                    Top 3 Kelas <?php echo $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX'); ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Skor V</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $tingkat_results = array_filter($hasil_perhitungan, function($h) use ($tingkat) {
                                return $h['tingkat'] === $tingkat && $h['ranking_tingkat'] <= 3;
                            });
                            usort($tingkat_results, function($a, $b) {
                                return $a['ranking_tingkat'] <=> $b['ranking_tingkat'];
                            });
                            
                            foreach ($tingkat_results as $hasil): 
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?php 
                                        echo $hasil['ranking_tingkat'] == 1 ? 'bg-warning' : 
                                             ($hasil['ranking_tingkat'] == 2 ? 'bg-secondary' : 'bg-primary'); 
                                    ?>">
                                        <?php echo $hasil['ranking_tingkat']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($hasil['siswa']['nama']); ?></strong></td>
                                <td>
                                    <span
                                        class="badge bg-info badge-sm"><?php echo htmlspecialchars($hasil['siswa']['kelas']); ?></span>
                                </td>
                                <td><?php echo formatNumber($hasil['skor_v'], 4); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
    <a href="index.php" class="btn btn-primary btn-lg">
        <i class="bi bi-file-earmark-text"></i>
        Lihat Laporan Lengkap
    </a>
    <a href="?step=1" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-clockwise"></i>
        Hitung Ulang
    </a>
</div>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>