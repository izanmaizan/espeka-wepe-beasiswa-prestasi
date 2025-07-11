<?php
$page_title = 'Laporan & Hasil';
require_once '../../includes/header.php';

requireLogin();

// Get hasil perhitungan berdasarkan view
$view_mode = $_GET['view'] ?? 'global'; // 'tingkat' atau 'global'
$tingkat_filter = $_GET['tingkat'] ?? '';

// Get hasil perhitungan
if ($view_mode === 'global') {
    $hasil_perhitungan = getHasilPerhitunganGlobal($pdo);
} else {
    $hasil_perhitungan = getHasilPerhitunganPerTingkat($pdo, $tingkat_filter);
}

// Get statistik
$statistik = getStatistikHasil($pdo);

// Get tanggal perhitungan terakhir
$stmt = $pdo->query("SELECT MAX(tanggal_hitung) as tanggal_terakhir FROM hasil_perhitungan");
$tanggal_terakhir = $stmt->fetch()['tanggal_terakhir'];

// Get top 3 per tingkat untuk summary
$top_per_tingkat = [];
if (!empty($hasil_perhitungan)) {
    $top_per_tingkat = getTop3PerTingkat($pdo);
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Laporan & Hasil', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-file-earmark-text"></i>
        Laporan & Hasil Beasiswa Prestasi
    </h1>

    <!-- TOMBOL CETAK LAPORAN PDF -->
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <?php if (hasRole('admin')): ?>
            <a href="hitung.php" class="btn btn-success">
                <i class="bi bi-calculator"></i>
                Hitung Ulang
            </a>
            <?php endif; ?>

            <?php if (!empty($hasil_perhitungan)): ?>
            <!-- Tombol Cetak PDF -->
            <a href="export.php?view=<?php echo $view_mode; ?>&tingkat=<?php echo $tingkat_filter; ?>"
                class="btn btn-outline-primary" target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom"
                title="Cetak Laporan PDF Resmi">
                <i class="bi bi-printer"></i>
                Cetak Laporan
            </a>
            <?php endif; ?>
        </div>

        <!-- Status Laporan Info -->
        <?php if (!empty($hasil_perhitungan)): ?>
        <div class="btn-group">
            <small class="text-muted align-self-center ms-3">
                <i class="bi bi-info-circle"></i>
                Laporan:
                <strong>
                    <?php 
                if ($view_mode === 'global') {
                    echo 'Top 10 Global (' . count($hasil_perhitungan) . ' siswa)';
                } else {
                    if ($tingkat_filter) {
                        $tingkat_name = $tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX');
                        echo "Kelas $tingkat_name (" . count($hasil_perhitungan) . ' siswa)';
                    } else {
                        echo 'Semua Tingkat (' . count($hasil_perhitungan) . ' siswa)';
                    }
                }
                ?>
                </strong>
            </small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($hasil_perhitungan) && $view_mode === 'global'): ?>
<!-- Belum ada hasil -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calculator text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">Belum Ada Hasil Perhitungan</h4>
                <p class="text-muted">
                    Silakan lakukan perhitungan Weighted Product terlebih dahulu untuk melihat hasil ranking beasiswa
                    prestasi.
                </p>
                <?php if (hasRole('admin')): ?>
                <div class="mt-4">
                    <a href="hitung.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-calculator"></i>
                        Mulai Perhitungan
                    </a>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        Pastikan data siswa, kriteria, dan penilaian sudah lengkap sebelum melakukan perhitungan.
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>

<!-- View Mode Selection -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="?view=global"
                                class="btn <?php echo $view_mode === 'global' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-trophy"></i> Top 10 Penerima Beasiswa
                            </a>
                            <a href="?view=tingkat&tingkat=<?php echo $tingkat_filter; ?>"
                                class="btn <?php echo $view_mode === 'tingkat' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-layers"></i> Hasil Per Tingkat
                            </a>
                        </div>
                    </div>

                    <?php if ($view_mode === 'tingkat'): ?>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="hidden" name="view" value="tingkat">
                            <select class="form-select me-2" name="tingkat" onchange="this.form.submit()">
                                <option value="">Semua Tingkat</option>
                                <option value="7" <?php echo $tingkat_filter === '7' ? 'selected' : ''; ?>>Kelas VII
                                </option>
                                <option value="8" <?php echo $tingkat_filter === '8' ? 'selected' : ''; ?>>Kelas VIII
                                </option>
                                <option value="9" <?php echo $tingkat_filter === '9' ? 'selected' : ''; ?>>Kelas IX
                                </option>
                            </select>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo $statistik['global']['total_global'] ?? 0; ?></h4>
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
                <small class="text-muted">Penerima Beasiswa Global</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="bi bi-calendar-event text-info" style="font-size: 2rem;"></i>
                <h6 class="mt-2 mb-0">
                    <?php echo $tanggal_terakhir ? date('d/m/Y', strtotime($tanggal_terakhir)) : 'N/A'; ?></h6>
                <small class="text-muted">Tanggal Perhitungan</small>
            </div>
        </div>
    </div>
</div>


<?php if ($view_mode === 'global'): ?>
<!-- Top 10 Global View -->
<div class="card mb-4">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="mb-0">
                    <i class="bi bi-trophy-fill text-warning"></i>
                    Top 10 Penerima Beasiswa Prestasi
                </h6>
            </div>
            <div class="col-auto">
                <span class="badge bg-primary">
                    Perhitungan: <?php echo $tanggal_terakhir ? date('d F Y', strtotime($tanggal_terakhir)) : 'N/A'; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="ranking-table">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Ranking Global</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th width="12%">Skor S</th>
                        <th width="12%">Skor V</th>
                        <th width="15%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hasil_perhitungan as $hasil): ?>
                    <tr class="table-success">
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-trophy-fill text-warning me-2"></i>
                                <span class="badge <?php 
                                    echo $hasil['ranking_global'] == 1 ? 'bg-warning' : 
                                         ($hasil['ranking_global'] == 2 ? 'bg-secondary' : 
                                         ($hasil['ranking_global'] == 3 ? 'bg-primary' : 'bg-success')); 
                                ?> fs-6">
                                    <?php echo $hasil['ranking_global']; ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold"><?php echo htmlspecialchars($hasil['nis']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($hasil['nama']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($hasil['kelas']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">Tingkat <?php echo $hasil['tingkat']; ?></span>
                        </td>
                        <td>
                            <span class="text-primary"><?php echo formatNumber($hasil['skor_s'], 6); ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-success"><?php echo formatNumber($hasil['skor_v'], 6); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-success">
                                <i class="bi bi-trophy"></i>
                                Penerima Beasiswa
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary per Tingkat dalam Global View -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-star-fill text-success"></i>
                    Top 3 Terbaik per Tingkat Kelas
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach (['7', '8', '9'] as $tingkat): ?>
                    <div class="col-md-4">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-<?php echo $tingkat; ?>-circle"></i>
                                    Kelas <?php echo $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX'); ?>
                                </h6>
                            </div>
                            <div class="card-body p-2">
                                <?php if (empty($top_per_tingkat["tingkat_$tingkat"])): ?>
                                <small class="text-muted">Belum ada hasil untuk tingkat ini</small>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($top_per_tingkat["tingkat_$tingkat"] as $siswa): ?>
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                        <div>
                                            <span class="badge <?php 
                                                echo $siswa['ranking_tingkat'] == 1 ? 'bg-warning' : 
                                                     ($siswa['ranking_tingkat'] == 2 ? 'bg-secondary' : 'bg-primary'); 
                                            ?> me-2 badge-sm">
                                                <?php echo $siswa['ranking_tingkat']; ?>
                                            </span>
                                            <small><strong><?php echo htmlspecialchars($siswa['nama']); ?></strong></small>
                                            <br>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($siswa['kelas']); ?></small>
                                        </div>
                                        <span
                                            class="badge bg-success badge-sm"><?php echo formatNumber($siswa['skor_v'], 4); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Per Tingkat View -->
<div class="card mb-4">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="mb-0">
                    <i class="bi bi-list-ol"></i>
                    Hasil Ranking Per Tingkat Kelas
                    <?php if ($tingkat_filter): ?>
                    - Kelas <?php echo $tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX'); ?>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="col-auto">
                <span class="badge bg-primary">
                    Perhitungan: <?php echo $tanggal_terakhir ? date('d F Y', strtotime($tanggal_terakhir)) : 'N/A'; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ranking Tingkat</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <?php if (empty($tingkat_filter)): ?>
                        <th>Tingkat</th>
                        <?php endif; ?>
                        <th>Skor S</th>
                        <th>Skor V</th>
                        <th>Ranking Global</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hasil_perhitungan as $hasil): ?>
                    <?php 
                    $is_top_3_tingkat = $hasil['ranking_tingkat'] <= 3;
                    $is_top_10_global = $hasil['ranking_global'] <= 10;
                    ?>
                    <tr class="<?php echo $is_top_3_tingkat ? 'table-success' : ''; ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($is_top_3_tingkat): ?>
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                <?php endif; ?>
                                <span class="badge <?php 
                                    echo $hasil['ranking_tingkat'] == 1 ? 'bg-warning' : 
                                         ($hasil['ranking_tingkat'] == 2 ? 'bg-secondary' : 
                                         ($hasil['ranking_tingkat'] == 3 ? 'bg-primary' : 'bg-light text-dark')); 
                                ?> fs-6">
                                    <?php echo $hasil['ranking_tingkat']; ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold"><?php echo htmlspecialchars($hasil['nis']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($hasil['nama']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($hasil['kelas']); ?></span>
                        </td>
                        <?php if (empty($tingkat_filter)): ?>
                        <td>
                            <span class="badge bg-secondary">Tingkat <?php echo $hasil['tingkat']; ?></span>
                        </td>
                        <?php endif; ?>
                        <td>
                            <span class="text-primary"><?php echo formatNumber($hasil['skor_s'], 6); ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-success"><?php echo formatNumber($hasil['skor_v'], 6); ?></span>
                        </td>
                        <td>
                            <span class="badge <?php echo $is_top_10_global ? 'bg-warning' : 'bg-light text-dark'; ?>">
                                <?php echo $hasil['ranking_global']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($is_top_3_tingkat): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-star"></i>
                                Top 3 Tingkat
                            </span>
                            <?php endif; ?>
                            <?php if ($is_top_10_global): ?>
                            <br><span class="badge bg-warning text-dark mt-1">
                                <i class="bi bi-trophy"></i>
                                Top 10 Global
                            </span>
                            <?php endif; ?>
                            <?php if (!$is_top_3_tingkat && !$is_top_10_global): ?>
                            <span class="badge bg-secondary">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detail Kriteria dan Informasi -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-list-check"></i>
                    Kriteria Penilaian
                </h6>
            </div>
            <div class="card-body">
                <?php
                $kriteria_list = getAllKriteria($pdo);
                ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Kriteria</th>
                                <th>Bobot</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kriteria_list as $kriteria): ?>
                            <tr>
                                <td>
                                    <span
                                        class="badge bg-secondary"><?php echo htmlspecialchars($kriteria['kode']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($kriteria['nama']); ?></td>
                                <td><?php echo formatNumber($kriteria['bobot'], 4); ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo $kriteria['jenis'] === 'benefit' ? 'bg-success' : 'bg-warning'; ?> badge-sm">
                                        <?php echo ucfirst($kriteria['jenis']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Sistem Beasiswa Prestasi
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-trophy"></i> Sistem Penerima Beasiswa:</h6>
                    <ul class="mb-0 small">
                        <li><strong>3 Terbaik per Tingkat:</strong> Setiap kelas (VII, VIII, IX) mendapat 3 penerima
                        </li>
                        <li><strong>10 Terbaik Global:</strong> Dari semua siswa dipilih 10 terbaik keseluruhan</li>
                        <li><strong>Metode:</strong> Weighted Product untuk ranking objektif</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <h6>Interpretasi Skor:</h6>
                    <ul class="mb-0 small">
                        <li><strong>Skor S:</strong> Hasil perkalian nilai kriteria dipangkatkan bobot</li>
                        <li><strong>Skor V:</strong> Nilai normalisasi dari Skor S (basis ranking)</li>
                        <li><strong>Ranking:</strong> Urutan berdasarkan Skor V tertinggi</li>
                    </ul>
                </div>

                <?php if (hasRole('admin')): ?>
                <div class="mt-3">
                    <h6>Aksi Admin:</h6>
                    <div class="d-grid gap-1">
                        <a href="../penilaian/index.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-clipboard-data"></i> Kelola Penilaian
                        </a>
                        <a href="hitung.php" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-calculator"></i> Hitung Ulang
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- CSS Optimized -->
<style>
.btn-toolbar .text-muted {
    font-size: 0.85rem;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.btn.loading {
    pointer-events: none;
}

.btn.loading i::before {
    content: "\F53F";
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.dropdown-menu {
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    min-width: 200px;
}

.dropdown-header {
    color: #6c757d;
    font-size: 0.775rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.dropdown-item {
    transition: all 0.15s ease-in-out;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(3px);
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    margin-right: 8px;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
        width: 100%;
    }

    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 5px;
    }

    .dropdown-menu {
        position: static !important;
        transform: none !important;
        width: 100%;
        box-shadow: none;
        border: 1px solid #dee2e6;
        margin-top: 5px;
    }
}
</style>

<!-- JavaScript Optimized -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Track cetak laporan clicks dan loading animation
    const exportLinks = document.querySelectorAll('a[href*="export.php"]');
    exportLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const view = this.href.includes('view=global') ? 'Global' : 'Tingkat';

            // Add loading state
            this.classList.add('loading');
            const icon = this.querySelector('i');
            const originalClass = icon.className;

            // Show loading animation
            setTimeout(() => {
                this.classList.remove('loading');
                icon.className = originalClass;
            }, 2000);

            // Analytics tracking
            console.log(`Cetak Laporan PDF - ${view}`);

            // Open PDF in new tab
            this.target = '_blank';
        });
    });

    // Keyboard shortcuts untuk cetak laporan
    document.addEventListener('keydown', function(e) {
        // Ctrl+P untuk cetak laporan PDF
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            const pdfLink = document.querySelector('a[href*="export.php"]');
            if (pdfLink) pdfLink.click();
        }
    });
});
</script>

<!-- Print Styles -->
<style media="print">
.btn-toolbar,
.breadcrumb,
nav,
.btn {
    display: none !important;
}

.card {
    border: 1px solid #000 !important;
    box-shadow: none !important;
}

.table {
    font-size: 12px;
}

@page {
    margin: 1cm;
}

body {
    print-color-adjust: exact;
}
</style>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>