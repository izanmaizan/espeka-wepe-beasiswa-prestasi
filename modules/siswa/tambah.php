<?php
$page_title = 'Tambah Siswa';
require_once '../../includes/header.php';

requireRole('admin');

$errors = [];

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = cleanInput($_POST['nis']);
    $nama = cleanInput($_POST['nama']);
    $kelas = cleanInput($_POST['kelas']);
    $jenis_kelamin = cleanInput($_POST['jenis_kelamin']);
    $alamat = cleanInput($_POST['alamat']);
    $no_hp = cleanInput($_POST['no_hp']);
    $tahun_ajaran = cleanInput($_POST['tahun_ajaran']);
    
    // Auto-detect tingkat from kelas
    $tingkat = detectTingkatFromKelas($kelas);
    
    // Validasi
    if (empty($nis)) {
        $errors['nis'] = 'NIS harus diisi!';
    } elseif (!preg_match('/^[0-9]+$/', $nis)) {
        $errors['nis'] = 'NIS hanya boleh berisi angka!';
    } else {
        // Cek duplikasi NIS
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nis = ?");
        $stmt->execute([$nis]);
        if ($stmt->fetchColumn() > 0) {
            $errors['nis'] = 'NIS sudah terdaftar!';
        }
    }
    
    if (empty($nama)) {
        $errors['nama'] = 'Nama harus diisi!';
    } elseif (strlen($nama) < 3) {
        $errors['nama'] = 'Nama minimal 3 karakter!';
    }
    
    if (empty($kelas)) {
        $errors['kelas'] = 'Kelas harus diisi!';
    }
    
    if (empty($jenis_kelamin) || !in_array($jenis_kelamin, ['L', 'P'])) {
        $errors['jenis_kelamin'] = 'Jenis kelamin harus dipilih!';
    }
    
    if (empty($tahun_ajaran)) {
        $errors['tahun_ajaran'] = 'Tahun ajaran harus diisi!';
    }
    
    if (!empty($no_hp) && !preg_match('/^[0-9+\-\s]+$/', $no_hp)) {
        $errors['no_hp'] = 'Format nomor HP tidak valid!';
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO siswa (nis, nama, kelas, tingkat, jenis_kelamin, alamat, no_hp, tahun_ajaran) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nis, $nama, $kelas, $tingkat, $jenis_kelamin, $alamat, $no_hp, $tahun_ajaran]);
            
            setAlert('success', "Data siswa berhasil ditambahkan! (Tingkat: $tingkat)");
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            setAlert('danger', 'Gagal menambahkan data siswa!');
        }
    }
}

// Default values
$nis = $_POST['nis'] ?? '';
$nama = $_POST['nama'] ?? '';
$kelas = $_POST['kelas'] ?? '';
$jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$no_hp = $_POST['no_hp'] ?? '';
$tahun_ajaran = $_POST['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1);

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Data Siswa', 'url' => 'index.php'],
    ['text' => 'Tambah Siswa', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-person-plus"></i>
        Tambah Siswa
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
                    Form Tambah Siswa
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nis" class="form-label">
                                NIS <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control <?php echo isset($errors['nis']) ? 'is-invalid' : ''; ?>" id="nis"
                                name="nis" value="<?php echo htmlspecialchars($nis); ?>"
                                placeholder="Masukkan NIS siswa" required>
                            <?php if (isset($errors['nis'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['nis']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tahun_ajaran" class="form-label">
                                Tahun Ajaran <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control <?php echo isset($errors['tahun_ajaran']) ? 'is-invalid' : ''; ?>"
                                id="tahun_ajaran" name="tahun_ajaran"
                                value="<?php echo htmlspecialchars($tahun_ajaran); ?>" placeholder="2024/2025" required>
                            <?php if (isset($errors['tahun_ajaran'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['tahun_ajaran']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nama" class="form-label">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" id="nama"
                            name="nama" value="<?php echo htmlspecialchars($nama); ?>"
                            placeholder="Masukkan nama lengkap siswa" required>
                        <?php if (isset($errors['nama'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['nama']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kelas" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['kelas']) ? 'is-invalid' : ''; ?>"
                                id="kelas" name="kelas" required onchange="updateTingkatPreview()">
                                <option value="">Pilih Kelas</option>

                                <optgroup label="Kelas VII">
                                    <option value="VII.1" <?php echo $kelas === 'VII.1' ? 'selected' : ''; ?>>VII.1
                                    </option>
                                    <option value="VII.2" <?php echo $kelas === 'VII.2' ? 'selected' : ''; ?>>VII.2
                                    </option>
                                    <option value="VII.3" <?php echo $kelas === 'VII.3' ? 'selected' : ''; ?>>VII.3
                                    </option>
                                    <option value="VII.4" <?php echo $kelas === 'VII.4' ? 'selected' : ''; ?>>VII.4
                                    </option>
                                </optgroup>

                                <optgroup label="Kelas VIII">
                                    <option value="VIII.1" <?php echo $kelas === 'VIII.1' ? 'selected' : ''; ?>>VIII.1
                                    </option>
                                    <option value="VIII.2" <?php echo $kelas === 'VIII.2' ? 'selected' : ''; ?>>VIII.2
                                    </option>
                                    <option value="VIII.3" <?php echo $kelas === 'VIII.3' ? 'selected' : ''; ?>>VIII.3
                                    </option>
                                    <option value="VIII.4" <?php echo $kelas === 'VIII.4' ? 'selected' : ''; ?>>VIII.4
                                    </option>
                                </optgroup>

                                <optgroup label="Kelas IX">
                                    <option value="IX.1" <?php echo $kelas === 'IX.1' ? 'selected' : ''; ?>>IX.1
                                    </option>
                                    <option value="IX.2" <?php echo $kelas === 'IX.2' ? 'selected' : ''; ?>>IX.2
                                    </option>
                                    <option value="IX.3" <?php echo $kelas === 'IX.3' ? 'selected' : ''; ?>>IX.3
                                    </option>
                                    <option value="IX.4" <?php echo $kelas === 'IX.4' ? 'selected' : ''; ?>>IX.4
                                    </option>
                                </optgroup>
                            </select>
                            <?php if (isset($errors['kelas'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['kelas']; ?></div>
                            <?php else: ?>
                            <div class="form-text">
                                Tingkat akan otomatis terdeteksi: <span id="tingkat-preview"
                                    class="fw-bold text-primary">-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="jenis_kelamin" class="form-label">
                                Jenis Kelamin <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select <?php echo isset($errors['jenis_kelamin']) ? 'is-invalid' : ''; ?>"
                                id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo $jenis_kelamin === 'L' ? 'selected' : ''; ?>>Laki-laki
                                </option>
                                <option value="P" <?php echo $jenis_kelamin === 'P' ? 'selected' : ''; ?>>Perempuan
                                </option>
                            </select>
                            <?php if (isset($errors['jenis_kelamin'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['jenis_kelamin']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3"
                            placeholder="Masukkan alamat lengkap siswa"><?php echo htmlspecialchars($alamat); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor HP</label>
                        <input type="text"
                            class="form-control <?php echo isset($errors['no_hp']) ? 'is-invalid' : ''; ?>" id="no_hp"
                            name="no_hp" value="<?php echo htmlspecialchars($no_hp); ?>"
                            placeholder="Masukkan nomor HP siswa/orangtua">
                        <?php if (isset($errors['no_hp'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['no_hp']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-x-circle"></i>
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i>
                            Simpan Data
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
                    Format Kelas SMP Negeri 2 Ampek Angkek
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Format Kelas:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Kelas VII:</strong> VII.1, VII.2, VII.3, VII.4</li>
                        <li><strong>Kelas VIII:</strong> VIII.1, VIII.2, VIII.3, VIII.4</li>
                        <li><strong>Kelas IX:</strong> IX.1, IX.2, IX.3, IX.4</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <h6>Tingkat Otomatis:</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><span class="badge bg-primary">VII.*</span></td>
                            <td>→ Tingkat 7</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success">VIII.*</span></td>
                            <td>→ Tingkat 8</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark">IX.*</span></td>
                            <td>→ Tingkat 9</td>
                        </tr>
                    </table>
                </div>

                <div class="mt-3">
                    <h6>Contoh Data:</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>NIS:</td>
                            <td><code>2025001</code></td>
                        </tr>
                        <tr>
                            <td>Kelas:</td>
                            <td><code>IX.1</code></td>
                        </tr>
                        <tr>
                            <td>Tahun:</td>
                            <td><code>2024/2025</code></td>
                        </tr>
                        <tr>
                            <td>HP:</td>
                            <td><code>081234567890</code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Preview Siswa Existing -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-people"></i>
                    Distribusi Siswa Saat Ini
                </h6>
            </div>
            <div class="card-body">
                <?php
                // Get current distribution
                $stmt = $pdo->query("
                    SELECT tingkat, COUNT(*) as jumlah 
                    FROM siswa 
                    WHERE status = 'aktif' 
                    GROUP BY tingkat 
                    ORDER BY tingkat
                ");
                $distribusi = $stmt->fetchAll();
                ?>

                <?php if (empty($distribusi)): ?>
                <p class="text-muted small">Belum ada data siswa</p>
                <?php else: ?>
                <div class="row text-center">
                    <?php foreach ($distribusi as $dist): ?>
                    <div class="col-4">
                        <h6 class="text-primary"><?php echo $dist['jumlah']; ?></h6>
                        <small class="text-muted">Tingkat <?php echo $dist['tingkat']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Update tingkat preview when kelas is selected
function updateTingkatPreview() {
    const kelasSelect = document.getElementById('kelas');
    const tingkatPreview = document.getElementById('tingkat-preview');
    const kelas = kelasSelect.value;

    if (kelas.startsWith('VII')) {
        tingkatPreview.textContent = 'Tingkat 7';
        tingkatPreview.className = 'fw-bold text-primary';
    } else if (kelas.startsWith('VIII')) {
        tingkatPreview.textContent = 'Tingkat 8';
        tingkatPreview.className = 'fw-bold text-success';
    } else if (kelas.startsWith('IX')) {
        tingkatPreview.textContent = 'Tingkat 9';
        tingkatPreview.className = 'fw-bold text-warning';
    } else {
        tingkatPreview.textContent = '-';
        tingkatPreview.className = 'fw-bold text-muted';
    }
}

// Call on page load if kelas already selected
document.addEventListener('DOMContentLoaded', function() {
    updateTingkatPreview();
});
</script>

<?php require_once '../../includes/footer.php'; ?>