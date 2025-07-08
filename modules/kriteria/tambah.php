<?php
$page_title = 'Tambah Kriteria';
require_once '../../includes/header.php';

requireRole('admin');

$errors = [];

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = cleanInput($_POST['kode']);
    $nama = cleanInput($_POST['nama']);
    $bobot = (float)$_POST['bobot'];
    $jenis = cleanInput($_POST['jenis']);
    $keterangan = cleanInput($_POST['keterangan']);
    
    // Validasi
    if (empty($kode)) {
        $errors['kode'] = 'Kode kriteria harus diisi!';
    } elseif (!preg_match('/^[A-Z][0-9]*$/', $kode)) {
        $errors['kode'] = 'Kode harus diawali huruf kapital dan diikuti angka (contoh: C1)!';
    } else {
        // Cek duplikasi kode
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kriteria WHERE kode = ?");
        $stmt->execute([$kode]);
        if ($stmt->fetchColumn() > 0) {
            $errors['kode'] = 'Kode kriteria sudah digunakan!';
        }
    }
    
    if (empty($nama)) {
        $errors['nama'] = 'Nama kriteria harus diisi!';
    } elseif (strlen($nama) < 5) {
        $errors['nama'] = 'Nama kriteria minimal 5 karakter!';
    }
    
    if ($bobot <= 0 || $bobot > 1) {
        $errors['bobot'] = 'Bobot harus antara 0.0001 sampai 1.0000!';
    } else {
        // Cek total bobot setelah ditambah kriteria baru
        $stmt = $pdo->query("SELECT SUM(bobot) as total_bobot FROM kriteria");
        $total_bobot_existing = (float)$stmt->fetch()['total_bobot'];
        
        if (($total_bobot_existing + $bobot) > 1.0001) {
            $errors['bobot'] = 'Total bobot akan melebihi 1.0000! Sisa bobot yang tersedia: ' . formatNumber(1.0 - $total_bobot_existing, 4);
        }
    }
    
    if (empty($jenis) || !in_array($jenis, ['benefit', 'cost'])) {
        $errors['jenis'] = 'Jenis kriteria harus dipilih!';
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO kriteria (kode, nama, bobot, jenis, keterangan) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kode, $nama, $bobot, $jenis, $keterangan]);
            
            setAlert('success', 'Kriteria berhasil ditambahkan!');
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            setAlert('danger', 'Gagal menambahkan kriteria!');
        }
    }
}

// Default values
$kode = $_POST['kode'] ?? '';
$nama = $_POST['nama'] ?? '';
$bobot = $_POST['bobot'] ?? '';
$jenis = $_POST['jenis'] ?? '';
$keterangan = $_POST['keterangan'] ?? '';

// Get sisa bobot yang tersedia
$stmt = $pdo->query("SELECT SUM(bobot) as total_bobot FROM kriteria");
$total_bobot_existing = (float)$stmt->fetch()['total_bobot'];
$sisa_bobot = 1.0 - $total_bobot_existing;

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Kriteria Penilaian', 'url' => 'index.php'],
    ['text' => 'Tambah Kriteria', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-circle"></i>
        Tambah Kriteria
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

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-form"></i>
                    Form Tambah Kriteria
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="kode" class="form-label">
                                Kode Kriteria <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control <?php echo isset($errors['kode']) ? 'is-invalid' : ''; ?>" id="kode"
                                name="kode" value="<?php echo htmlspecialchars($kode); ?>" placeholder="C1, C2, ..."
                                required maxlength="10" style="text-transform: uppercase;">
                            <?php if (isset($errors['kode'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['kode']; ?></div>
                            <?php else: ?>
                            <div class="form-text">Format: C1, C2, K1, dll.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label for="nama" class="form-label">
                                Nama Kriteria <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" id="nama"
                                name="nama" value="<?php echo htmlspecialchars($nama); ?>"
                                placeholder="Masukkan nama kriteria" required>
                            <?php if (isset($errors['nama'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['nama']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bobot" class="form-label">
                                Bobot <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number"
                                    class="form-control <?php echo isset($errors['bobot']) ? 'is-invalid' : ''; ?>"
                                    id="bobot" name="bobot" value="<?php echo htmlspecialchars($bobot); ?>"
                                    step="0.0001" min="0.0001" max="<?php echo $sisa_bobot; ?>" placeholder="0.2500"
                                    required>
                                <span class="input-group-text">
                                    <span id="bobot-persen">0%</span>
                                </span>
                                <?php if (isset($errors['bobot'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['bobot']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">
                                Sisa bobot tersedia: <strong><?php echo formatNumber($sisa_bobot, 4); ?></strong>
                                (<?php echo formatNumber($sisa_bobot * 100, 1); ?>%)
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="jenis" class="form-label">
                                Jenis Kriteria <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['jenis']) ? 'is-invalid' : ''; ?>"
                                id="jenis" name="jenis" required>
                                <option value="">Pilih Jenis</option>
                                <option value="benefit" <?php echo $jenis === 'benefit' ? 'selected' : ''; ?>>
                                    Benefit (Semakin tinggi semakin baik)
                                </option>
                                <option value="cost" <?php echo $jenis === 'cost' ? 'selected' : ''; ?>>
                                    Cost (Semakin rendah semakin baik)
                                </option>
                            </select>
                            <?php if (isset($errors['jenis'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['jenis']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                            placeholder="Masukkan keterangan kriteria (opsional)"><?php echo htmlspecialchars($keterangan); ?></textarea>
                        <div class="form-text">Jelaskan cara penilaian, satuan, atau rentang nilai yang digunakan.</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-x-circle"></i>
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i>
                            Simpan Kriteria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Panduan Kriteria
                </h6>
            </div>
            <div class="card-body">
                <h6>Jenis Kriteria:</h6>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-body p-2">
                                <span class="badge bg-success">Benefit</span>
                                <p class="small mb-0 mt-1">
                                    Nilai tinggi = hasil baik<br>
                                    <em>Contoh: Nilai rapor, prestasi</em>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="card border-warning">
                            <div class="card-body p-2">
                                <span class="badge bg-warning">Cost</span>
                                <p class="small mb-0 mt-1">
                                    Nilai rendah = hasil baik<br>
                                    <em>Contoh: Penghasilan orangtua</em>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <h6>Contoh Bobot:</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Prioritas</th>
                            <th>Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sangat Penting</td>
                            <td>0.3000 (30%)</td>
                        </tr>
                        <tr>
                            <td>Penting</td>
                            <td>0.2500 (25%)</td>
                        </tr>
                        <tr>
                            <td>Cukup Penting</td>
                            <td>0.2000 (20%)</td>
                        </tr>
                        <tr>
                            <td>Kurang Penting</td>
                            <td>0.1500 (15%)</td>
                        </tr>
                        <tr>
                            <td>Tidak Penting</td>
                            <td>0.1000 (10%)</td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert alert-warning">
                    <small>
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Penting:</strong> Total semua bobot kriteria harus sama dengan 1.0000 (100%).
                    </small>
                </div>
            </div>
        </div>

        <!-- Kriteria yang sudah ada -->
        <?php
        $existing_kriteria = getAllKriteria($pdo);
        if (!empty($existing_kriteria)):
        ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-list"></i>
                    Kriteria yang Ada
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_kriteria as $k): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($k['kode']); ?></td>
                                <td><?php echo formatNumber($k['bobot'], 4); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th>Total:</th>
                                <th><?php echo formatNumber($total_bobot_existing, 4); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Update percentage display
document.getElementById('bobot').addEventListener('input', function() {
    const bobot = parseFloat(this.value) || 0;
    const persen = (bobot * 100).toFixed(1);
    document.getElementById('bobot-persen').textContent = persen + '%';
});

// Auto-uppercase kode
document.getElementById('kode').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

<?php require_once '../../includes/footer.php'; ?>