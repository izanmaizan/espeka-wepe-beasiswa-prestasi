<?php
$page_title = 'Dashboard';
require_once '../../includes/header.php';

requireLogin();

// Get statistik data
$stats = [];

// Total siswa
$stmt = $pdo->query("SELECT COUNT(*) as total, 
                            COUNT(CASE WHEN status = 'aktif' THEN 1 END) as aktif 
                     FROM siswa");
$stats['siswa'] = $stmt->fetch();

// Total siswa per tingkat
$stats['per_tingkat'] = [];
foreach (['7', '8', '9'] as $tingkat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM siswa WHERE tingkat = ? AND status = 'aktif'");
    $stmt->execute([$tingkat]);
    $stats['per_tingkat'][$tingkat] = $stmt->fetchColumn();
}

// Total kriteria
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(bobot) as total_bobot FROM kriteria");
$stats['kriteria'] = $stmt->fetch();

// Siswa dengan penilaian lengkap
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT p.siswa_id) as siswa_dinilai
    FROM penilaian p 
    JOIN siswa s ON p.siswa_id = s.id 
    WHERE s.status = 'aktif'
    GROUP BY p.siswa_id
    HAVING COUNT(p.kriteria_id) = (SELECT COUNT(*) FROM kriteria)
");
$result = $stmt->fetchAll();
$stats['siswa_dinilai'] = count($result);

// Hasil perhitungan terakhir
$stmt = $pdo->query("
    SELECT MAX(tanggal_hitung) as tanggal_terakhir,
           COUNT(*) as total_hasil
    FROM hasil_perhitungan
");
$stats['hasil'] = $stmt->fetch();

// Top 10 global jika ada hasil
$top_global = [];
if ($stats['hasil']['total_hasil'] > 0) {
    $stmt = $pdo->query("
        SELECT hp.ranking_global, s.nama, s.kelas, s.tingkat, hp.skor_v
        FROM hasil_perhitungan hp
        JOIN siswa s ON hp.siswa_id = s.id
        WHERE hp.ranking_global <= 10
        ORDER BY hp.ranking_global ASC
    ");
    $top_global = $stmt->fetchAll();
}

// Top 3 per tingkat jika ada hasil
$top_per_tingkat = [];
if ($stats['hasil']['total_hasil'] > 0) {
    foreach (['7', '8', '9'] as $tingkat) {
        $stmt = $pdo->prepare("
            SELECT hp.ranking_tingkat, s.nama, s.kelas, hp.skor_v
            FROM hasil_perhitungan hp
            JOIN siswa s ON hp.siswa_id = s.id
            WHERE hp.tingkat = ? AND hp.ranking_tingkat <= 3
            ORDER BY hp.ranking_tingkat ASC
        ");
        $stmt->execute([$tingkat]);
        $top_per_tingkat[$tingkat] = $stmt->fetchAll();
    }
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-speedometer2"></i>
        Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <small class="text-muted">
            <i class="bi bi-person-circle"></i>
            Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?></strong>
            <span class="badge bg-<?php echo $_SESSION['role'] === 'admin' ? 'danger' : 'info'; ?> ms-2">
                <?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? '')); ?>
            </span>
        </small>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo $stats['siswa']['aktif']; ?></h4>
                <small class="text-muted">Siswa Aktif</small>
                <hr class="my-2">
                <div class="row text-center">
                    <div class="col-4">
                        <strong><?php echo $stats['per_tingkat']['7']; ?></strong>
                        <br><small class="text-muted">Kelas VII</small>
                    </div>
                    <div class="col-4">
                        <strong><?php echo $stats['per_tingkat']['8']; ?></strong>
                        <br><small class="text-muted">Kelas VIII</small>
                    </div>
                    <div class="col-4">
                        <strong><?php echo $stats['per_tingkat']['9']; ?></strong>
                        <br><small class="text-muted">Kelas IX</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-list-check text-success" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo $stats['kriteria']['total']; ?></h4>
                <small class="text-muted">Kriteria Penilaian</small>
                <hr class="my-2">
                <small>
                    Total Bobot:
                    <strong
                        class="<?php echo abs($stats['kriteria']['total_bobot'] - 1.0) < 0.0001 ? 'text-success' : 'text-warning'; ?>">
                        <?php echo formatNumber($stats['kriteria']['total_bobot'], 4); ?>
                    </strong>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="bi bi-clipboard-data text-info" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo $stats['siswa_dinilai']; ?></h4>
                <small class="text-muted">Siswa Sudah Dinilai</small>
                <hr class="my-2">
                <small>
                    Dari <strong><?php echo $stats['siswa']['aktif']; ?></strong> siswa aktif
                    <br>
                    <span
                        class="<?php echo $stats['siswa_dinilai'] == $stats['siswa']['aktif'] ? 'text-success' : 'text-warning'; ?>">
                        <?php echo $stats['siswa_dinilai'] == $stats['siswa']['aktif'] ? 'Lengkap' : 'Belum Lengkap'; ?>
                    </span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-trophy text-warning" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo count($top_global); ?></h4>
                <small class="text-muted">Penerima Beasiswa</small>
                <hr class="my-2">
                <small>
                    <?php if ($stats['hasil']['tanggal_terakhir']): ?>
                    Terakhir: <?php echo date('d/m/Y', strtotime($stats['hasil']['tanggal_terakhir'])); ?>
                    <?php else: ?>
                    <span class="text-muted">Belum ada perhitungan</span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions for Admin -->
<?php if (hasRole('admin')): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-lightning"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="../siswa/tambah.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-plus"></i>
                            Tambah Siswa
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="../kriteria/tambah.php" class="btn btn-outline-success w-100">
                            <i class="bi bi-plus-square"></i>
                            Tambah Kriteria
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="../penilaian/index.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-clipboard-data"></i>
                            Input Penilaian
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="../laporan/hitung.php" class="btn btn-outline-warning w-100">
                            <i class="bi bi-calculator"></i>
                            Hitung Ranking
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Results Display -->
<div class="row">
    <!-- Top 10 Global -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-trophy-fill text-warning"></i>
                    Top 10 Penerima Beasiswa Global
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($top_global)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calculator text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">Belum ada hasil perhitungan</p>
                    <?php if (hasRole('admin')): ?>
                    <a href="../laporan/hitung.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-calculator"></i> Mulai Perhitungan
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($top_global as $index => $siswa): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="badge <?php 
                                echo $siswa['ranking_global'] == 1 ? 'bg-warning' : 
                                     ($siswa['ranking_global'] == 2 ? 'bg-secondary' : 
                                     ($siswa['ranking_global'] == 3 ? 'bg-primary' : 'bg-light text-dark')); 
                            ?> me-2">
                                <?php echo $siswa['ranking_global']; ?>
                            </span>
                            <strong><?php echo htmlspecialchars($siswa['nama']); ?></strong>
                            <br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($siswa['kelas']); ?> - Tingkat
                                <?php echo $siswa['tingkat']; ?>
                            </small>
                        </div>
                        <span class="badge bg-success"><?php echo formatNumber($siswa['skor_v'], 4); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 text-center">
                    <a href="../laporan/index.php?view=global" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top 3 Per Tingkat -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-star-fill text-success"></i>
                    Top 3 Per Tingkat Kelas
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($top_per_tingkat)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calculator text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">Belum ada hasil perhitungan</p>
                </div>
                <?php else: ?>
                <?php foreach (['7', '8', '9'] as $tingkat): ?>
                <div class="mb-3">
                    <h6 class="text-primary">
                        <i class="bi bi-<?php echo $tingkat; ?>-circle"></i>
                        Kelas <?php echo $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX'); ?>
                    </h6>
                    <?php if (empty($top_per_tingkat[$tingkat])): ?>
                    <small class="text-muted">Belum ada hasil untuk tingkat ini</small>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($top_per_tingkat[$tingkat] as $siswa): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            <div>
                                <span class="badge <?php 
                                    echo $siswa['ranking_tingkat'] == 1 ? 'bg-warning' : 
                                         ($siswa['ranking_tingkat'] == 2 ? 'bg-secondary' : 'bg-primary'); 
                                ?> me-2 badge-sm">
                                    <?php echo $siswa['ranking_tingkat']; ?>
                                </span>
                                <small><strong><?php echo htmlspecialchars($siswa['nama']); ?></strong></small>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($siswa['kelas']); ?></small>
                            </div>
                            <span
                                class="badge bg-success badge-sm"><?php echo formatNumber($siswa['skor_v'], 4); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="text-center">
                    <a href="../laporan/index.php?view=tingkat" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-gear"></i>
                    Status Sistem
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-people text-primary me-2"></i>
                            <div>
                                <strong>Data Siswa</strong>
                                <br>
                                <span
                                    class="badge <?php echo $stats['siswa']['aktif'] > 0 ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $stats['siswa']['aktif'] > 0 ? 'Ready' : 'Kosong'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-list-check text-success me-2"></i>
                            <div>
                                <strong>Kriteria</strong>
                                <br>
                                <span
                                    class="badge <?php echo abs($stats['kriteria']['total_bobot'] - 1.0) < 0.0001 ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo abs($stats['kriteria']['total_bobot'] - 1.0) < 0.0001 ? 'Valid' : 'Perlu Perbaikan'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clipboard-data text-info me-2"></i>
                            <div>
                                <strong>Penilaian</strong>
                                <br>
                                <span
                                    class="badge <?php echo $stats['siswa_dinilai'] == $stats['siswa']['aktif'] ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $stats['siswa_dinilai'] == $stats['siswa']['aktif'] ? 'Lengkap' : 'Perlu Dilengkapi'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calculator text-warning me-2"></i>
                            <div>
                                <strong>Perhitungan</strong>
                                <br>
                                <span
                                    class="badge <?php echo $stats['hasil']['total_hasil'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $stats['hasil']['total_hasil'] > 0 ? 'Tersedia' : 'Belum Ada'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php 
                $all_ready = $stats['siswa']['aktif'] > 0 && 
                           abs($stats['kriteria']['total_bobot'] - 1.0) < 0.0001 && 
                           $stats['siswa_dinilai'] == $stats['siswa']['aktif'];
                ?>

                <?php if (!$all_ready && hasRole('admin')): ?>
                <div class="alert alert-warning mt-3">
                    <h6><i class="bi bi-exclamation-triangle"></i> Perhatian!</h6>
                    <p class="mb-2">Sistem belum siap untuk perhitungan. Pastikan:</p>
                    <ul class="mb-0">
                        <?php if ($stats['siswa']['aktif'] == 0): ?>
                        <li>Data siswa sudah diinput</li>
                        <?php endif; ?>
                        <?php if (abs($stats['kriteria']['total_bobot'] - 1.0) >= 0.0001): ?>
                        <li>Total bobot kriteria = 1.0000</li>
                        <?php endif; ?>
                        <?php if ($stats['siswa_dinilai'] < $stats['siswa']['aktif']): ?>
                        <li>Semua siswa sudah diberi penilaian</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php elseif ($all_ready && hasRole('admin')): ?>
                <div class="alert alert-success mt-3">
                    <h6><i class="bi bi-check-circle"></i> Siap Hitung!</h6>
                    <p class="mb-2">Semua data sudah lengkap. Sistem siap untuk melakukan perhitungan ranking beasiswa.
                    </p>
                    <a href="../laporan/hitung.php" class="btn btn-success">
                        <i class="bi bi-calculator"></i> Mulai Perhitungan
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>