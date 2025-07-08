<?php
$page_title = 'Detail Siswa';
require_once '../../includes/header.php';

requireLogin();

// Get siswa ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID siswa tidak valid!');
    header('Location: index.php');
    exit();
}

// Get siswa data
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    setAlert('danger', 'Data siswa tidak ditemukan!');
    header('Location: index.php');
    exit();
}

// Get penilaian data
$stmt = $pdo->prepare("
    SELECT k.kode, k.nama as kriteria_nama, k.bobot, k.jenis, p.nilai, p.keterangan as penilaian_keterangan
    FROM kriteria k 
    LEFT JOIN penilaian p ON k.id = p.kriteria_id AND p.siswa_id = ?
    ORDER BY k.kode
");
$stmt->execute([$id]);
$penilaian_data = $stmt->fetchAll();

// Get hasil perhitungan terakhir untuk siswa ini
$stmt = $pdo->prepare("
    SELECT * FROM hasil_perhitungan 
    WHERE siswa_id = ? 
    ORDER BY tanggal_hitung DESC 
    LIMIT 1
");
$stmt->execute([$id]);
$hasil_terakhir = $stmt->fetch();

// Hitung kelengkapan penilaian
$total_kriteria = count($penilaian_data);
$kriteria_dinilai = 0;
foreach ($penilaian_data as $p) {
    if ($p['nilai'] !== null && $p['nilai'] > 0) {
        $kriteria_dinilai++;
    }
}
$persentase_kelengkapan = $total_kriteria > 0 ? ($kriteria_dinilai / $total_kriteria) * 100 : 0;

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Data Siswa', 'url' => 'index.php'],
    ['text' => 'Detail Siswa', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-person-lines-fill"></i>
        Detail Siswa
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <?php if (hasRole('admin')): ?>
            <a href="edit.php?id=<?php echo $siswa['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i>
                Edit Data
            </a>
            <a href="../penilaian/index.php?siswa_id=<?php echo $siswa['id']; ?>" class="btn btn-success">
                <i class="bi bi-clipboard-data"></i>
                Input Penilaian
            </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Informasi Siswa -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-person-badge"></i>
                    Informasi Siswa
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
                    <h5 class="mt-2"><?php echo htmlspecialchars($siswa['nama']); ?></h5>
                    <span class="badge <?php echo $siswa['status'] === 'aktif' ? 'bg-success' : 'bg-warning'; ?> fs-6">
                        <?php echo ucfirst($siswa['status']); ?>
                    </span>
                </div>

                <table class="table table-borderless table-sm">
                    <tr>
                        <td width="40%"><strong>NIS</strong></td>
                        <td>: <?php echo htmlspecialchars($siswa['nis']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kelas</strong></td>
                        <td>: <span class="badge bg-info"><?php echo htmlspecialchars($siswa['kelas']); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Jenis Kelamin</strong></td>
                        <td>: <?php echo $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tahun Ajaran</strong></td>
                        <td>: <?php echo htmlspecialchars($siswa['tahun_ajaran']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td>: <?php echo htmlspecialchars($siswa['alamat'] ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>No. HP</strong></td>
                        <td>: <?php echo htmlspecialchars($siswa['no_hp'] ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Terdaftar</strong></td>
                        <td>: <?php echo date('d/m/Y', strtotime($siswa['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Penilaian -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="mb-0">
                            <i class="bi bi-clipboard-check"></i>
                            Status Penilaian
                        </h6>
                    </div>
                    <div class="col-auto">
                        <span
                            class="badge <?php echo $persentase_kelengkapan == 100 ? 'bg-success' : ($persentase_kelengkapan > 0 ? 'bg-warning' : 'bg-danger'); ?>">
                            <?php echo number_format($persentase_kelengkapan, 1); ?>% Lengkap
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?php echo $persentase_kelengkapan == 100 ? 'bg-success' : 'bg-warning'; ?>"
                            role="progressbar" style="width: <?php echo $persentase_kelengkapan; ?>%"
                            aria-valuenow="<?php echo $persentase_kelengkapan; ?>" aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted"><?php echo $kriteria_dinilai; ?> dari <?php echo $total_kriteria; ?>
                        kriteria telah dinilai</small>
                </div>

                <!-- Detail Penilaian -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Kriteria</th>
                                <th>Bobot</th>
                                <th>Jenis</th>
                                <th>Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penilaian_data as $p): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($p['kode']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($p['kriteria_nama']); ?></td>
                                <td><?php echo formatNumber($p['bobot'], 4); ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo $p['jenis'] === 'benefit' ? 'bg-success' : 'bg-warning'; ?> badge-sm">
                                        <?php echo ucfirst($p['jenis']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['nilai'] !== null && $p['nilai'] > 0): ?>
                                    <span
                                        class="fw-semibold text-primary"><?php echo formatNumber($p['nilai'], 2); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['nilai'] !== null && $p['nilai'] > 0): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($persentase_kelengkapan < 100 && hasRole('admin')): ?>
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Penilaian Belum Lengkap!</strong>
                    Silakan lengkapi penilaian untuk semua kriteria agar siswa dapat diikutkan dalam perhitungan
                    ranking.
                    <div class="mt-2">
                        <a href="../penilaian/index.php?siswa_id=<?php echo $siswa['id']; ?>"
                            class="btn btn-sm btn-warning">
                            <i class="bi bi-clipboard-plus"></i> Lengkapi Penilaian
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hasil Perhitungan -->
<?php if ($hasil_terakhir): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-trophy"></i>
                    Hasil Perhitungan Terakhir
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div
                                class="display-1 <?php echo $hasil_terakhir['ranking'] <= 3 ? 'text-warning' : 'text-muted'; ?>">
                                <?php echo $hasil_terakhir['ranking']; ?>
                            </div>
                            <h6 class="text-muted">Ranking</h6>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Skor S</h6>
                                        <h5 class="text-primary">
                                            <?php echo formatNumber($hasil_terakhir['skor_s'], 6); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Skor V</h6>
                                        <h5 class="text-success">
                                            <?php echo formatNumber($hasil_terakhir['skor_v'], 6); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Status</h6>
                                        <span
                                            class="badge <?php echo $hasil_terakhir['ranking'] <= 5 ? 'bg-success' : 'bg-secondary'; ?> fs-6">
                                            <?php echo $hasil_terakhir['ranking'] <= 5 ? 'Rekomendasi' : 'Tidak Direkomendasikan'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="30%"><strong>Tanggal Perhitungan:</strong></td>
                                    <td><?php echo date('d F Y', strtotime($hasil_terakhir['tanggal_hitung'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tahun Ajaran:</strong></td>
                                    <td><?php echo htmlspecialchars($hasil_terakhir['tahun_ajaran']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-calculator text-muted" style="font-size: 3rem;"></i>
                <h6 class="text-muted mt-2">Belum Ada Hasil Perhitungan</h6>
                <p class="text-muted">Siswa ini belum pernah diikutkan dalam perhitungan ranking beasiswa.</p>
                <?php if (hasRole('admin') && $persentase_kelengkapan == 100): ?>
                <a href="../laporan/hitung.php" class="btn btn-primary">
                    <i class="bi bi-calculator"></i> Jalankan Perhitungan
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>