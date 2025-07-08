<?php
$page_title = 'Edit Siswa';
require_once '../../includes/header.php';

requireRole('admin');

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
    $status = cleanInput($_POST['status']);
    
    // Validasi
    if (empty($nis)) {
        $errors['nis'] = 'NIS harus diisi!';
    } elseif (!preg_match('/^[0-9]+$/', $nis)) {
        $errors['nis'] = 'NIS hanya boleh berisi angka!';
    } else {
        // Cek duplikasi NIS (kecuali untuk siswa yang sedang diedit)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nis = ? AND id != ?");
        $stmt->execute([$nis, $id]);
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
    
    if (empty($status) || !in_array($status, ['aktif', 'nonaktif'])) {
        $errors['status'] = 'Status harus dipilih!';
    }
    
    if (!empty($no_hp) && !preg_match('/^[0-9+\-\s]+$/', $no_hp)) {
        $errors['no_hp'] = 'Format nomor HP tidak valid!';
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE siswa 
                SET nis = ?, nama = ?, kelas = ?, jenis_kelamin = ?, 
                    alamat = ?, no_hp = ?, tahun_ajaran = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nis, $nama, $kelas, $jenis_kelamin, $alamat, $no_hp, $tahun_ajaran, $status, $id]);
            
            setAlert('success', 'Data siswa berhasil diperbarui!');
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            setAlert('danger', 'Gagal memperbarui data siswa!');
        }
    }
} else {
    // Set default values from database
    $nis = $siswa['nis'];
    $nama = $siswa['nama'];
    $kelas = $siswa['kelas'];
    $jenis_kelamin = $siswa['jenis_kelamin'];
    $alamat = $siswa['alamat'];
    $no_hp = $siswa['no_hp'];
    $tahun_ajaran = $siswa['tahun_ajaran'];
    $status = $siswa['status'];
}

// Override with POST data if form was submitted with errors
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = $_POST['nis'] ?? $nis;
    $nama = $_POST['nama'] ?? $nama;
    $kelas = $_POST['kelas'] ?? $kelas;
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? $jenis_kelamin;
    $alamat = $_POST['alamat'] ?? $alamat;
    $no_hp = $_POST['no_hp'] ?? $no_hp;
    $tahun_ajaran = $_POST['tahun_ajaran'] ?? $tahun_ajaran;
    $status = $_POST['status'] ?? $status;
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Data Siswa', 'url' => 'index.php'],
    ['text' => 'Edit Siswa', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-pencil-square"></i>
        Edit Siswa
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
                    Form Edit Siswa: <?php echo htmlspecialchars($siswa['nama']); ?>
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
                                value="<?php echo htmlspecialchars($tahun_ajaran); ?>" placeholder="2023/2024" required>
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
                        <div class="col-md-4 mb-3">
                            <label for="kelas" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['kelas']) ? 'is-invalid' : ''; ?>"
                                id="kelas" name="kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php
                                $kelas_options = ['7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D', '9A', '9B', '9C', '9D'];
                                foreach ($kelas_options as $kelas_opt):
                                ?>
                                <option value="<?php echo $kelas_opt; ?>"
                                    <?php echo $kelas === $kelas_opt ? 'selected' : ''; ?>>
                                    <?php echo $kelas_opt; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['kelas'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['kelas']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 mb-3">
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

                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>"
                                id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="aktif" <?php echo $status === 'aktif' ? 'selected' : ''; ?>>Aktif
                                </option>
                                <option value="nonaktif" <?php echo $status === 'nonaktif' ? 'selected' : ''; ?>>
                                    Non-aktif</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
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
                            Update Data
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
                <table class="table table-sm">
                    <tr>
                        <td><strong>NIS:</strong></td>
                        <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama:</strong></td>
                        <td><?php echo htmlspecialchars($siswa['nama']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kelas:</strong></td>
                        <td><?php echo htmlspecialchars($siswa['kelas']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span
                                class="badge <?php echo $siswa['status'] === 'aktif' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($siswa['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat:</strong></td>
                        <td><?php echo date('d/m/Y', strtotime($siswa['created_at'])); ?></td>
                    </tr>
                </table>

                <?php
                // Check if siswa has penilaian
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE siswa_id = ?");
                $stmt->execute([$id]);
                $has_penilaian = $stmt->fetchColumn() > 0;
                ?>

                <div class="alert alert-<?php echo $has_penilaian ? 'success' : 'warning'; ?> mt-3">
                    <i class="bi bi-<?php echo $has_penilaian ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <strong>Status Penilaian:</strong><br>
                    <?php if ($has_penilaian): ?>
                    Siswa ini sudah memiliki data penilaian.
                    <?php else: ?>
                    Siswa ini belum memiliki data penilaian.
                    <?php endif; ?>
                </div>

                <?php if ($has_penilaian): ?>
                <div class="d-grid">
                    <a href="../penilaian/index.php?siswa_id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-clipboard-data"></i>
                        Lihat/Edit Penilaian
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>