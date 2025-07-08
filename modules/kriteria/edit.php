<?php
$page_title = 'Edit Kriteria';
require_once '../../includes/header.php';

requireRole('admin');

// Get kriteria ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID kriteria tidak valid!');
    header('Location: index.php');
    exit();
}

// Get kriteria data
$stmt = $pdo->prepare("SELECT * FROM kriteria WHERE id = ?");
$stmt->execute([$id]);
$kriteria = $stmt->fetch();

if (!$kriteria) {
    setAlert('danger', 'Data kriteria tidak ditemukan!');
    header('Location: index.php');
    exit();
}

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
        // Cek duplikasi kode (kecuali untuk kriteria yang sedang diedit)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kriteria WHERE kode = ? AND id != ?");
        $stmt->execute([$kode, $id]);
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
        // Cek total bobot setelah diupdate (excluding current kriteria)
        $stmt = $pdo->prepare("SELECT SUM(bobot) as total_bobot FROM kriteria WHERE id != ?");
        $stmt->execute([$id]);
        $total_bobot_others = (float)$stmt->fetch()['total_bobot'];
        
        if (($total_bobot_others + $bobot) > 1.0001) {
            $sisa_bobot = 1.0 - $total_bobot_others;
            $errors['bobot'] = 'Total bobot akan melebihi 1.0000! Maksimal bobot untuk kriteria ini: ' . formatNumber($sisa_bobot, 4);
        }
    }
    
    if (empty($jenis) || !in_array($jenis, ['benefit', 'cost'])) {
        $errors['jenis'] = 'Jenis kriteria harus dipilih!';
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE kriteria 
                SET kode = ?, nama = ?, bobot = ?, jenis = ?, keterangan = ?
                WHERE id = ?
            ");
            $stmt->execute([$kode, $nama, $bobot, $jenis, $keterangan, $id]);
            
            setAlert('success', 'Kriteria berhasil diperbarui!');
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            setAlert('danger', 'Gagal memperbarui kriteria!');
        }
    }
} else {
    // Set default values from database
    $kode = $kriteria['kode'];
    $nama = $kriteria['nama'];
    $bobot = $kriteria['bobot'];
    $jenis = $kriteria['jenis'];
    $keterangan = $kriteria['keterangan'];
}

// Override with POST data if form was submitted with errors
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = $_POST['kode'] ?? $kode;
    $nama = $_POST['nama'] ?? $nama;
    $bobot = $_POST['bobot'] ?? $bobot;
    $jenis = $_POST['jenis'] ?? $jenis;
    $keterangan = $_POST['keterangan'] ?? $keterangan;
}

// Get total bobot kriteria lain
$stmt = $pdo->prepare("SELECT SUM(bobot) as total_bobot FROM kriteria WHERE id != ?");
$stmt->execute([$id]);
$total_bobot_others = (float)$stmt->fetch()['total_bobot'];
$max_bobot = 1.0 - $total_bobot_others;

// Cek apakah kriteria sudah digunakan dalam penilaian
$stmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE kriteria_id = ?");
$stmt->execute([$id]);
$digunakan_dalam_penilaian = $stmt->fetchColumn() > 0;

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Kriteria Penilaian', 'url' => 'index.php'],
    ['text' => 'Edit Kriteria', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-pencil-square"></i>
        Edit Kriteria
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

<?php if ($digunakan_dalam_penilaian): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Perhatian!</strong> Kriteria ini sudah digunakan dalam penilaian siswa.
    Perubahan yang Anda lakukan akan mempengaruhi hasil perhitungan yang sudah ada.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-form"></i>
                    Form Edit Kriteria: <?php echo htmlspecialchars($kriteria['kode']); ?>
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
                                    id="bobot" name="bobot" value="<?php echo $bobot; ?>" step="0.0001" min="0.0001"
                                    max="<?php echo $max_bobot; ?>" placeholder="0.2500" required>
                                <span class="input-group-text">
                                    <span id="bobot-persen"><?php echo number_format($bobot * 100, 1); ?>%</span>
                                </span>
                                <?php if (isset($errors['bobot'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['bobot']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">
                                Maksimal bobot yang dapat digunakan:
                                <strong><?php echo formatNumber($max_bobot, 4); ?></strong>
                                (<?php echo formatNumber($max_bobot * 100, 1); ?>%)
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
                            Update Kriteria
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
                    Data Saat Ini
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="40%"><strong>Kode:</strong></td>
                        <td><?php echo htmlspecialchars($kriteria['kode']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama:</strong></td>
                        <td><?php echo htmlspecialchars($kriteria['nama']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Bobot:</strong></td>
                        <td>
                            <?php echo formatNumber($kriteria['bobot'], 4); ?>
                            <small
                                class="text-muted">(<?php echo formatNumber($kriteria['bobot'] * 100, 1); ?>%)</small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Jenis:</strong></td>
                        <td>
                            <span
                                class="badge <?php echo $kriteria['jenis'] === 'benefit' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($kriteria['jenis']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat:</strong></td>
                        <td><?php echo date('d/m/Y', strtotime($kriteria['created_at'])); ?></td>
                    </tr>
                </table>

                <div class="alert alert-<?php echo $digunakan_dalam_penilaian ? 'warning' : 'info'; ?> mt-3">
                    <i
                        class="bi bi-<?php echo $digunakan_dalam_penilaian ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                    <strong>Status Penggunaan:</strong><br>
                    <?php if ($digunakan_dalam_penilaian): ?>
                    Kriteria ini sudah digunakan dalam <?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE kriteria_id = ?");
                    $stmt->execute([$id]);
                    echo $stmt->fetchColumn();
                    ?> penilaian siswa.
                    <?php else: ?>
                    Kriteria ini belum digunakan dalam penilaian manapun.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-calculator"></i>
                    Simulasi Bobot
                </h6>
            </div>
            <div class="card-body">
                <h6>Total Bobot Kriteria Lain:</h6>
                <div class="progress mb-2">
                    <div class="progress-bar bg-secondary" style="width: <?php echo $total_bobot_others * 100; ?>%">
                        <?php echo formatNumber($total_bobot_others * 100, 1); ?>%
                    </div>
                </div>

                <h6>Bobot Kriteria Ini:</h6>
                <div class="progress mb-2">
                    <div class="progress-bar bg-primary" id="progress-current"
                        style="width: <?php echo $bobot * 100; ?>%">
                        <span id="progress-text"><?php echo formatNumber($bobot * 100, 1); ?>%</span>
                    </div>
                </div>

                <small class="text-muted">
                    Sisa kapasitas: <span
                        id="sisa-kapasitas"><?php echo formatNumber((1.0 - $total_bobot_others - $bobot) * 100, 1); ?>%</span>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
// Update percentage display dan progress bar
document.getElementById('bobot').addEventListener('input', function() {
    const bobot = parseFloat(this.value) || 0;
    const persen = (bobot * 100).toFixed(1);
    const totalOthers = <?php echo $total_bobot_others; ?>;
    const sisa = ((1.0 - totalOthers - bobot) * 100).toFixed(1);

    document.getElementById('bobot-persen').textContent = persen + '%';
    document.getElementById('progress-current').style.width = persen + '%';
    document.getElementById('progress-text').textContent = persen + '%';
    document.getElementById('sisa-kapasitas').textContent = sisa + '%';

    // Change color based on validity
    const progressBar = document.getElementById('progress-current');
    if (totalOthers + bobot > 1.0001) {
        progressBar.className = 'progress-bar bg-danger';
    } else {
        progressBar.className = 'progress-bar bg-primary';
    }
});

// Auto-uppercase kode
document.getElementById('kode').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

<?php require_once '../../includes/footer.php'; ?>