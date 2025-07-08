<?php
$page_title = 'Kriteria Penilaian';
require_once '../../includes/header.php';

requireRole('admin');

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM kriteria WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        setAlert('success', 'Kriteria berhasil dihapus!');
    } catch (PDOException $e) {
        setAlert('danger', 'Gagal menghapus kriteria! Mungkin masih ada data penilaian yang menggunakan kriteria ini.');
    }
    header('Location: index.php');
    exit();
}

// Get kriteria data
$stmt = $pdo->query("SELECT * FROM kriteria ORDER BY kode");
$kriteria_list = $stmt->fetchAll();

// Calculate total bobot
$total_bobot = 0;
foreach ($kriteria_list as $kriteria) {
    $total_bobot += $kriteria['bobot'];
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Kriteria Penilaian', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-list-check"></i>
        Kriteria Penilaian
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="tambah.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tambah Kriteria
            </a>
        </div>
    </div>
</div>

<!-- Info Card -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-info-circle text-info"></i>
                    Total Bobot Kriteria
                </h6>
                <h4 class="mb-0 <?php echo abs($total_bobot - 1.0) < 0.0001 ? 'text-success' : 'text-warning'; ?>">
                    <?php echo formatNumber($total_bobot, 4); ?>
                </h4>
                <?php if (abs($total_bobot - 1.0) >= 0.0001): ?>
                <small class="text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Total bobot harus sama dengan 1.0000
                </small>
                <?php else: ?>
                <small class="text-success">
                    <i class="bi bi-check-circle"></i>
                    Total bobot sudah benar
                </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-primary">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-list-ol text-primary"></i>
                    Jumlah Kriteria
                </h6>
                <h4 class="mb-0"><?php echo count($kriteria_list); ?></h4>
                <small class="text-muted">kriteria penilaian aktif</small>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-table"></i>
            Daftar Kriteria Penilaian
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($kriteria_list)): ?>
        <div class="text-center py-5">
            <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-2">Belum ada kriteria penilaian</h6>
            <p class="text-muted">Silakan tambah kriteria penilaian terlebih dahulu.</p>
            <a href="tambah.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Kriteria
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama Kriteria</th>
                        <th>Bobot</th>
                        <th>Bobot (%)</th>
                        <th>Jenis</th>
                        <th>Keterangan</th>
                        <th width="12%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($kriteria_list as $kriteria): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <span
                                class="badge bg-secondary fs-6"><?php echo htmlspecialchars($kriteria['kode']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($kriteria['nama']); ?></strong>
                        </td>
                        <td>
                            <span class="fw-semibold"><?php echo formatNumber($kriteria['bobot'], 4); ?></span>
                        </td>
                        <td>
                            <span class="text-primary"><?php echo formatNumber($kriteria['bobot'] * 100, 1); ?>%</span>
                        </td>
                        <td>
                            <span
                                class="badge <?php echo $kriteria['jenis'] === 'benefit' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($kriteria['jenis']); ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($kriteria['keterangan'] ?: '-'); ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="edit.php?id=<?php echo $kriteria['id']; ?>" class="btn btn-outline-warning"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $kriteria['id']; ?>"
                                    class="btn btn-outline-danger" title="Hapus"
                                    onclick="return confirmDelete('Yakin ingin menghapus kriteria ini? Data penilaian yang menggunakan kriteria ini akan ikut terhapus!')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="3" class="text-end">Total Bobot:</th>
                        <th><?php echo formatNumber($total_bobot, 4); ?></th>
                        <th><?php echo formatNumber($total_bobot * 100, 1); ?>%</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Info Panel -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Jenis Kriteria
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <span class="badge bg-success fs-6 mb-2">Benefit</span>
                            <p class="small text-muted mb-0">
                                Semakin tinggi nilai semakin baik
                                <br><em>Contoh: Nilai rapor, Prestasi</em>
                            </p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <span class="badge bg-warning fs-6 mb-2">Cost</span>
                            <p class="small text-muted mb-0">
                                Semakin rendah nilai semakin baik
                                <br><em>Contoh: Penghasilan orangtua</em>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-calculator"></i>
                    Weighted Product
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Metode Weighted Product menggunakan pembobotan untuk menghitung ranking alternatif.
                </p>
                <div class="small">
                    <strong>Formula:</strong><br>
                    <code>S(Ai) = Π (Xij)^Wj</code><br>
                    <code>V(Ai) = S(Ai) / Π (Xj*)^Wj</code>
                    <br><br>
                    <em>Dimana W adalah bobot kriteria</em>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>