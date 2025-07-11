<?php
// Start output buffering untuk mencegah header errors
ob_start();

$page_title = 'Input Penilaian';
require_once '../../includes/header.php';

requireRole('admin');

// Handle form submission dengan error handling yang lebih baik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siswa_id'])) {
    $siswa_id = (int)$_POST['siswa_id'];
    $nilai_kriteria = $_POST['nilai'] ?? [];
    
    // Pastikan tidak ada output sebelumnya
    ob_clean();
    
    try {
        // Validasi basic
        if (empty($nilai_kriteria)) {
            throw new Exception('Minimal satu kriteria harus diisi!');
        }
        
        $pdo->beginTransaction();
        
        // Hapus penilaian lama
        $stmt = $pdo->prepare("DELETE FROM penilaian WHERE siswa_id = ?");
        $stmt->execute([$siswa_id]);
        
        $berhasil_simpan = 0;
        
        // Simpan penilaian baru
        foreach ($nilai_kriteria as $kriteria_id => $nilai_input) {
            if (!empty($nilai_input) && trim($nilai_input) !== '') {
                // Get kriteria info
                $stmt_kriteria = $pdo->prepare("SELECT kode, jenis FROM kriteria WHERE id = ?");
                $stmt_kriteria->execute([$kriteria_id]);
                $kriteria_info = $stmt_kriteria->fetch();
                
                if ($kriteria_info) {
                    $nilai_numerik = 0;
                    $nilai_kategori = trim($nilai_input);
                    
                    // Konversi berdasarkan jenis kriteria
                    if ($kriteria_info['kode'] === 'C1') {
                        // Raport
                        $val = (float)$nilai_input;
                        if ($val >= 81) $nilai_numerik = 5.0;
                        elseif ($val >= 61) $nilai_numerik = 4.0;
                        elseif ($val >= 41) $nilai_numerik = 3.0;
                        elseif ($val >= 21) $nilai_numerik = 2.0;
                        else $nilai_numerik = 1.0;
                    } elseif ($kriteria_info['kode'] === 'C3') {
                        // Absensi
                        $val = (int)$nilai_input;
                        if ($val == 0) $nilai_numerik = 1.0;
                        elseif ($val <= 2) $nilai_numerik = 2.0;
                        elseif ($val == 3) $nilai_numerik = 3.0;
                        elseif ($val <= 5) $nilai_numerik = 4.0;
                        else $nilai_numerik = 5.0;
                    } else {
                        // Kategori
                        $mapping = ['SB' => 5.0, 'B' => 4.0, 'C' => 3.0, 'KB' => 2.0, 'SKB' => 1.0];
                        $nilai_numerik = $mapping[strtoupper($nilai_input)] ?? 3.0;
                        $nilai_kategori = strtoupper($nilai_input);
                    }
                    
                    // Insert penilaian
                    $stmt = $pdo->prepare("INSERT INTO penilaian (siswa_id, kriteria_id, nilai_kategori, nilai_numerik) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$siswa_id, $kriteria_id, $nilai_kategori, $nilai_numerik])) {
                        $berhasil_simpan++;
                    }
                }
            }
        }
        
        if ($berhasil_simpan > 0) {
            $pdo->commit();
            $_SESSION['alert'] = [
                'type' => 'success', 
                'message' => "✅ Data penilaian berhasil disimpan! ($berhasil_simpan kriteria tersimpan)"
            ];
        } else {
            $pdo->rollBack();
            $_SESSION['alert'] = [
                'type' => 'warning', 
                'message' => '⚠️ Tidak ada data yang disimpan. Pastikan minimal satu kriteria diisi dengan benar.'
            ];
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['alert'] = [
            'type' => 'danger', 
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
    
    // Clean output buffer dan redirect
    ob_end_clean();
    
    // Redirect dengan JavaScript sebagai fallback
    ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <script>
    window.location.href = 'index.php';
    </script>
</head>

<body>
    <p>Redirecting...</p>
    <script>
    if (!window.location.href.includes('index.php')) {
        window.location.replace('index.php');
    }
    </script>
</body>

</html>
<?php
    exit();
}

// Get data untuk halaman
try {
    $tingkat_filter = $_GET['tingkat'] ?? '';
    $siswa_list = getSiswaPerTingkat($pdo, $tingkat_filter) ?: [];
    $kriteria_list = getAllKriteria($pdo) ?: [];
} catch (Exception $e) {
    $siswa_list = [];
    $kriteria_list = [];
}

// Get selected siswa
$selected_siswa = null;
$penilaian_data = [];

if (isset($_GET['siswa_id']) && !empty($_GET['siswa_id'])) {
    try {
        $siswa_id = (int)$_GET['siswa_id'];
        $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ? AND status = 'aktif'");
        $stmt->execute([$siswa_id]);
        $selected_siswa = $stmt->fetch();
        
        if ($selected_siswa) {
            $stmt = $pdo->prepare("SELECT kriteria_id, nilai_kategori FROM penilaian WHERE siswa_id = ?");
            $stmt->execute([$siswa_id]);
            $penilaian_result = $stmt->fetchAll();
            
            foreach ($penilaian_result as $p) {
                $penilaian_data[$p['kriteria_id']] = $p['nilai_kategori'];
            }
        }
    } catch (Exception $e) {
        $selected_siswa = null;
        $penilaian_data = [];
    }
}

// Get progress statistics
$progress_stats = [];
foreach (['7', '8', '9'] as $tingkat) {
    try {
        $siswa_tingkat = getSiswaPerTingkat($pdo, $tingkat) ?: [];
        $siswa_dinilai = 0;
        $siswa_lengkap = 0;
        
        foreach ($siswa_tingkat as $siswa) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE siswa_id = ?");
            $stmt->execute([$siswa['id']]);
            $jumlah_penilaian = $stmt->fetchColumn();
            
            if ($jumlah_penilaian > 0) {
                $siswa_dinilai++;
                if ($jumlah_penilaian >= count($kriteria_list)) {
                    $siswa_lengkap++;
                }
            }
        }
        
        $progress_stats[$tingkat] = [
            'total' => count($siswa_tingkat),
            'dinilai' => $siswa_dinilai,
            'lengkap' => $siswa_lengkap
        ];
    } catch (Exception $e) {
        $progress_stats[$tingkat] = ['total' => 0, 'dinilai' => 0, 'lengkap' => 0];
    }
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Input Penilaian', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-clipboard-data"></i>
        Input Penilaian Siswa
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="../dashboard/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-house"></i>
                Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php 
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
    echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-' . ($alert['type'] === 'success' ? 'check-circle' : ($alert['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle')) . '"></i> ';
    echo $alert['message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}
?>

<!-- Progress Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart"></i>
                    Progress Penilaian per Tingkat
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach (['7', '8', '9'] as $tingkat): ?>
                    <?php 
                    $stat = $progress_stats[$tingkat];
                    $progress_persen = $stat['total'] > 0 ? ($stat['lengkap'] / $stat['total']) * 100 : 0;
                    ?>
                    <div class="col-md-4">
                        <div
                            class="card border-<?php echo $progress_persen >= 90 ? 'success' : ($progress_persen >= 50 ? 'warning' : 'danger'); ?>">
                            <div
                                class="card-header bg-<?php echo $progress_persen >= 90 ? 'success' : ($progress_persen >= 50 ? 'warning' : 'danger'); ?> text-<?php echo $progress_persen >= 50 ? 'white' : 'dark'; ?>">
                                <h6 class="mb-0">
                                    <i class="bi bi-<?php echo $tingkat; ?>-circle"></i>
                                    Kelas <?php echo $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX'); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h6><?php echo $stat['total']; ?></h6>
                                        <small class="text-muted">Total</small>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="text-info"><?php echo $stat['dinilai']; ?></h6>
                                        <small class="text-muted">Dinilai</small>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="text-success"><?php echo $stat['lengkap']; ?></h6>
                                        <small class="text-muted">Lengkap</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success"
                                            style="width: <?php echo $progress_persen; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($progress_persen, 1); ?>%
                                        lengkap</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($kriteria_list)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Perhatian!</strong> Belum ada kriteria penilaian.
    <a href="../kriteria/tambah.php" class="alert-link">Tambah kriteria terlebih dahulu</a>.
</div>
<?php elseif (empty($siswa_list)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Perhatian!</strong> Belum ada data siswa aktif
    <?php if ($tingkat_filter): ?>
    untuk tingkat <?php echo $tingkat_filter; ?>
    <?php endif; ?>.
    <a href="../siswa/tambah.php" class="alert-link">Tambah data siswa terlebih dahulu</a>.
</div>
<?php else: ?>

<div class="row">
    <!-- Filter & Pilih Siswa -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-funnel"></i>
                    Filter & Pilih Siswa
                </h6>
            </div>
            <div class="card-body">
                <!-- Filter Tingkat -->
                <form method="GET" class="mb-3">
                    <?php if (isset($_GET['siswa_id'])): ?>
                    <input type="hidden" name="siswa_id" value="<?php echo $_GET['siswa_id']; ?>">
                    <?php endif; ?>
                    <label for="tingkat" class="form-label">Filter Tingkat Kelas:</label>
                    <select class="form-select" id="tingkat" name="tingkat" onchange="this.form.submit()">
                        <option value="">Semua Tingkat (<?php echo count($siswa_list); ?> siswa)</option>
                        <option value="7" <?php echo $tingkat_filter === '7' ? 'selected' : ''; ?>>
                            Kelas VII (<?php echo $progress_stats['7']['total']; ?> siswa)
                        </option>
                        <option value="8" <?php echo $tingkat_filter === '8' ? 'selected' : ''; ?>>
                            Kelas VIII (<?php echo $progress_stats['8']['total']; ?> siswa)
                        </option>
                        <option value="9" <?php echo $tingkat_filter === '9' ? 'selected' : ''; ?>>
                            Kelas IX (<?php echo $progress_stats['9']['total']; ?> siswa)
                        </option>
                    </select>
                </form>

                <!-- Search -->
                <div class="mb-3">
                    <label for="search" class="form-label">Cari Siswa:</label>
                    <input type="text" class="form-control" id="search" placeholder="Ketik nama atau NIS...">
                </div>

                <!-- Daftar Siswa -->
                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;" id="siswa-list">
                    <?php if (!empty($siswa_list)): ?>
                    <?php foreach ($siswa_list as $siswa): ?>
                    <?php
                    // Get penilaian status
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE siswa_id = ?");
                        $stmt->execute([$siswa['id']]);
                        $jumlah_penilaian = $stmt->fetchColumn();
                        $is_lengkap = $jumlah_penilaian >= count($kriteria_list);
                        $has_penilaian = $jumlah_penilaian > 0;
                    } catch (Exception $e) {
                        $jumlah_penilaian = 0;
                        $is_lengkap = false;
                        $has_penilaian = false;
                    }
                    ?>
                    <a href="?siswa_id=<?php echo $siswa['id']; ?>&tingkat=<?php echo $tingkat_filter; ?>"
                        class="list-group-item list-group-item-action siswa-item <?php echo $selected_siswa && $selected_siswa['id'] === $siswa['id'] ? 'active' : ''; ?>"
                        data-nama="<?php echo strtolower($siswa['nama']); ?>" data-nis="<?php echo $siswa['nis']; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($siswa['nama']); ?></h6>
                            <div>
                                <span
                                    class="badge <?php echo $selected_siswa && $selected_siswa['id'] === $siswa['id'] ? 'bg-light text-dark' : 'bg-primary'; ?>">
                                    <?php echo htmlspecialchars($siswa['kelas']); ?>
                                </span>
                                <span class="badge bg-secondary ms-1">
                                    T<?php echo $siswa['tingkat']; ?>
                                </span>
                            </div>
                        </div>
                        <p class="mb-1 small">NIS: <?php echo htmlspecialchars($siswa['nis']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small
                                class="<?php echo $selected_siswa && $selected_siswa['id'] === $siswa['id'] ? 'text-light' : 'text-muted'; ?>">
                                <i
                                    class="bi bi-<?php echo $is_lengkap ? 'check-circle-fill text-success' : ($has_penilaian ? 'clock text-warning' : 'circle'); ?>"></i>
                                <?php 
                                if ($is_lengkap) {
                                    echo 'Penilaian Lengkap';
                                } elseif ($has_penilaian) {
                                    echo "Parsial ($jumlah_penilaian/" . count($kriteria_list) . ")";
                                } else {
                                    echo 'Belum dinilai';
                                }
                                ?>
                            </small>
                            <?php if ($is_lengkap): ?>
                            <span class="badge bg-success badge-sm">Siap</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-person-x"></i>
                        <br>Tidak ada data siswa
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Penilaian -->
    <div class="col-md-8">
        <?php if ($selected_siswa): ?>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="mb-0">
                            <i class="bi bi-clipboard-check"></i>
                            Form Penilaian: <?php echo htmlspecialchars($selected_siswa['nama']); ?>
                        </h6>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-info">
                            Tingkat <?php echo $selected_siswa['tingkat']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Info Siswa -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="30%"><strong>NIS</strong></td>
                                <td>: <?php echo htmlspecialchars($selected_siswa['nis']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama</strong></td>
                                <td>: <?php echo htmlspecialchars($selected_siswa['nama']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kelas</strong></td>
                                <td>: <?php echo htmlspecialchars($selected_siswa['kelas']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%"><strong>Jenis Kelamin</strong></td>
                                <td>:
                                    <?php echo $selected_siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tahun Ajaran</strong></td>
                                <td>: <?php echo htmlspecialchars($selected_siswa['tahun_ajaran']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>: <span
                                        class="badge bg-success"><?php echo ucfirst($selected_siswa['status']); ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Form Penilaian -->
                <form method="POST" action="" id="formPenilaian">
                    <input type="hidden" name="siswa_id" value="<?php echo $selected_siswa['id']; ?>">

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="12%">Kode</th>
                                    <th>Kriteria</th>
                                    <th width="12%">Bobot</th>
                                    <th width="10%">Jenis</th>
                                    <th width="30%">Nilai</th>
                                    <th width="15%">Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kriteria_list as $kriteria): ?>
                                <?php $current_value = $penilaian_data[$kriteria['id']] ?? ''; ?>
                                <tr>
                                    <td>
                                        <span
                                            class="badge bg-secondary"><?php echo htmlspecialchars($kriteria['kode']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($kriteria['nama']); ?></strong>
                                        <?php if ($kriteria['keterangan']): ?>
                                        <br><small
                                            class="text-muted"><?php echo htmlspecialchars($kriteria['keterangan']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatNumber($kriteria['bobot'], 4); ?>
                                        <br><small
                                            class="text-muted"><?php echo formatNumber($kriteria['bobot'] * 100, 1); ?>%</small>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?php echo $kriteria['jenis'] === 'benefit' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($kriteria['jenis']); ?>
                                        </span>
                                        <?php if ($kriteria['jenis'] === 'cost'): ?>
                                        <br><small class="text-muted">Rendah = Baik</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($kriteria['kode'] === 'C1'): ?>
                                        <input type="number" class="form-control form-control-sm"
                                            name="nilai[<?php echo $kriteria['id']; ?>]"
                                            value="<?php echo htmlspecialchars($current_value); ?>" step="0.1" min="0"
                                            max="100" placeholder="0-100" data-kriteria="raport">
                                        <small class="text-muted">Nilai rapor 0-100</small>
                                        <?php elseif ($kriteria['kode'] === 'C3'): ?>
                                        <input type="number" class="form-control form-control-sm"
                                            name="nilai[<?php echo $kriteria['id']; ?>]"
                                            value="<?php echo htmlspecialchars($current_value); ?>" min="0" max="50"
                                            placeholder="Jumlah tidak masuk" data-kriteria="absensi">
                                        <small class="text-muted">Jumlah tidak masuk (hari)</small>
                                        <?php else: ?>
                                        <select class="form-select form-select-sm"
                                            name="nilai[<?php echo $kriteria['id']; ?>]" data-kriteria="kategori">
                                            <option value="">Pilih Kategori</option>
                                            <option value="SB" <?php echo $current_value === 'SB' ? 'selected' : ''; ?>>
                                                SB - Sangat Baik (5.0)</option>
                                            <option value="B" <?php echo $current_value === 'B' ? 'selected' : ''; ?>>
                                                B - Baik (4.0)</option>
                                            <option value="C" <?php echo $current_value === 'C' ? 'selected' : ''; ?>>
                                                C - Cukup (3.0)</option>
                                            <option value="KB" <?php echo $current_value === 'KB' ? 'selected' : ''; ?>>
                                                KB - Kurang Baik (2.0)</option>
                                            <option value="SKB"
                                                <?php echo $current_value === 'SKB' ? 'selected' : ''; ?>>
                                                SKB - Sangat Kurang Baik (1.0)</option>
                                        </select>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="preview-nilai fw-bold text-primary"
                                            id="preview_<?php echo $kriteria['id']; ?>">
                                            <?php 
                                            if ($current_value) {
                                                if ($kriteria['kode'] === 'C1') {
                                                    $val = (float)$current_value;
                                                    if ($val >= 81) echo '5.0';
                                                    elseif ($val >= 61) echo '4.0';
                                                    elseif ($val >= 41) echo '3.0';
                                                    elseif ($val >= 21) echo '2.0';
                                                    else echo '1.0';
                                                } elseif ($kriteria['kode'] === 'C3') {
                                                    $val = (int)$current_value;
                                                    if ($val == 0) echo '1.0';
                                                    elseif ($val <= 2) echo '2.0';
                                                    elseif ($val == 3) echo '3.0';
                                                    elseif ($val <= 5) echo '4.0';
                                                    else echo '5.0';
                                                } else {
                                                    $mapping = ['SB' => '5.0', 'B' => '4.0', 'C' => '3.0', 'KB' => '2.0', 'SKB' => '1.0'];
                                                    echo $mapping[strtoupper($current_value)] ?? '3.0';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </span>
                                        <br><small class="text-muted">Nilai numerik</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                        <button type="submit" class="btn btn-primary" id="btnSimpan">
                            <i class="bi bi-save"></i>
                            Simpan Penilaian
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i>
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-plus text-muted" style="font-size: 3rem;"></i>
                <h6 class="text-muted mt-2">Pilih Siswa untuk Dinilai</h6>
                <p class="text-muted">Klik nama siswa di sebelah kiri untuk mulai memberikan penilaian.</p>

                <div class="alert alert-info text-start mt-4">
                    <h6><i class="bi bi-info-circle"></i> Alur Kerja Penilaian:</h6>
                    <ol class="mb-0 small">
                        <li>Pilih siswa dari daftar di sebelah kiri</li>
                        <li>Isi form penilaian untuk semua kriteria</li>
                        <li>Klik "Simpan Penilaian"</li>
                        <li>Sistem otomatis kembali ke halaman ini</li>
                        <li>Lanjutkan dengan siswa berikutnya</li>
                    </ol>
                </div>

                <?php if ($tingkat_filter): ?>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-funnel"></i> Lihat Semua Tingkat
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Petunjuk Penilaian -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Petunjuk Penilaian
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Kriteria dengan Input Angka:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>C1 - Rata-rata Raport:</strong></td>
                                    <td>Nilai 0-100</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <small class="text-muted">
                                            81-100 = 5.0 | 61-80 = 4.0 | 41-60 = 3.0 | 21-40 = 2.0 | 0-20 = 1.0
                                        </small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>C3 - Absensi (Cost):</strong></td>
                                    <td>Jumlah tidak masuk</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <small class="text-muted">
                                            0 = 1.0 | 1-2 = 2.0 | 3 = 3.0 | 4-5 = 4.0 | >5 = 5.0
                                        </small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Kriteria dengan Kategori:</h6>
                        <p class="small text-muted">
                            <strong>C2 - Keaktifan, C4 - Kedisiplinan, C5 - Keagamaan</strong>
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><span class="badge bg-success">SB</span></td>
                                    <td>Sangat Baik (5.0)</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info">B</span></td>
                                    <td>Baik (4.0)</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning text-dark">C</span></td>
                                    <td>Cukup (3.0)</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">KB</span></td>
                                    <td>Kurang Baik (2.0)</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">SKB</span></td>
                                    <td>Sangat Kurang Baik (1.0)</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <h6><i class="bi bi-lightbulb"></i> Tips Penting:</h6>
                    <ul class="mb-0 small">
                        <li><strong>Cost Criteria (Absensi):</strong> Semakin kecil nilai semakin baik</li>
                        <li><strong>Benefit Criteria (Lainnya):</strong> Semakin tinggi nilai semakin baik</li>
                        <li><strong>Kelengkapan:</strong> Pastikan semua kriteria telah diisi untuk perhitungan yang
                            akurat</li>
                        <li><strong>Preview:</strong> Lihat nilai numerik hasil konversi di kolom preview</li>
                        <li><strong>Auto-redirect:</strong> Setelah simpan, otomatis kembali ke halaman utama untuk
                            melanjutkan penilaian siswa lain</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const siswaItems = document.querySelectorAll('.siswa-item');

    siswaItems.forEach(item => {
        const nama = item.dataset.nama;
        const nis = item.dataset.nis;

        if (nama.includes(searchTerm) || nis.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Live preview untuk nilai numerik
document.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.startsWith('nilai[')) {
        const kriteriaId = e.target.name.match(/\d+/)[0];
        const previewElement = document.getElementById('preview_' + kriteriaId);
        const value = e.target.value;
        const kriteriaType = e.target.dataset.kriteria;

        if (value) {
            let numericValue = 0;

            if (kriteriaType === 'raport') {
                const val = parseFloat(value);
                if (val >= 81) numericValue = 5.0;
                else if (val >= 61) numericValue = 4.0;
                else if (val >= 41) numericValue = 3.0;
                else if (val >= 21) numericValue = 2.0;
                else numericValue = 1.0;
            } else if (kriteriaType === 'absensi') {
                const val = parseInt(value);
                if (val == 0) numericValue = 1.0;
                else if (val <= 2) numericValue = 2.0;
                else if (val == 3) numericValue = 3.0;
                else if (val <= 5) numericValue = 4.0;
                else numericValue = 5.0;
            } else if (kriteriaType === 'kategori') {
                const mapping = {
                    'SB': 5.0,
                    'B': 4.0,
                    'C': 3.0,
                    'KB': 2.0,
                    'SKB': 1.0
                };
                numericValue = mapping[value] || 0;
            }

            previewElement.textContent = numericValue.toFixed(1);
        } else {
            previewElement.textContent = '-';
        }
    }
});

// Form submission handling
document.getElementById('formPenilaian').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[name^="nilai["]');
    let emptyCount = 0;

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            emptyCount++;
        }
    });

    if (emptyCount === requiredFields.length) {
        e.preventDefault();
        alert('Minimal satu kriteria harus diisi!');
        return false;
    }

    if (emptyCount > 0) {
        if (!confirm(
                `Ada ${emptyCount} kriteria yang belum diisi. Yakin ingin menyimpan?\n\nSetelah penyimpanan, Anda akan dialihkan ke halaman utama.`
                )) {
            e.preventDefault();
            return false;
        }
    }

    // Disable submit button
    const btnSimpan = document.getElementById('btnSimpan');
    if (btnSimpan) {
        btnSimpan.disabled = true;
        btnSimpan.innerHTML = '<i class="bi bi-hourglass-split"></i> Menyimpan...';
    }

    return true;
});

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }
        }, 8000);
    });
});
</script>

<?php endif; ?>

<?php 
// Flush output buffer
ob_end_flush();
require_once '../../includes/footer.php'; 
?>