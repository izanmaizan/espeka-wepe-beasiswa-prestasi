<?php
// Start output buffering
ob_start();

$page_title = 'Edit Siswa';
require_once '../../includes/header.php';

requireRole('admin');

// Get siswa ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    ob_end_clean();
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'ID siswa tidak valid!'];
    header('Location: index.php');
    exit();
}

// Get siswa data
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    ob_end_clean();
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Data siswa tidak ditemukan!'];
    header('Location: index.php');
    exit();
}

$errors = [];

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean output buffer
    ob_clean();
    
    $nis = cleanInput($_POST['nis']);
    $nama = cleanInput($_POST['nama']);
    $kelas = cleanInput($_POST['kelas']);
    $jenis_kelamin = cleanInput($_POST['jenis_kelamin']);
    $alamat = cleanInput($_POST['alamat']);
    $no_hp = cleanInput($_POST['no_hp']);
    $tahun_ajaran = cleanInput($_POST['tahun_ajaran']);
    $status = cleanInput($_POST['status']);
    
    try {
        // Validasi
        if (empty($nis)) {
            throw new Exception('NIS harus diisi!');
        } elseif (!preg_match('/^[0-9]+$/', $nis)) {
            throw new Exception('NIS hanya boleh berisi angka!');
        }
        
        // Cek duplikasi NIS (kecuali untuk siswa yang sedang diedit)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nis = ? AND id != ?");
        $stmt->execute([$nis, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('NIS sudah terdaftar!');
        }
        
        if (empty($nama)) {
            throw new Exception('Nama harus diisi!');
        } elseif (strlen($nama) < 3) {
            throw new Exception('Nama minimal 3 karakter!');
        }
        
        if (empty($kelas)) {
            throw new Exception('Kelas harus diisi!');
        }
        
        if (empty($jenis_kelamin) || !in_array($jenis_kelamin, ['L', 'P'])) {
            throw new Exception('Jenis kelamin harus dipilih!');
        }
        
        if (empty($tahun_ajaran)) {
            throw new Exception('Tahun ajaran harus diisi!');
        }
        
        if (empty($status) || !in_array($status, ['aktif', 'nonaktif'])) {
            throw new Exception('Status harus dipilih!');
        }
        
        if (!empty($no_hp) && !preg_match('/^[0-9+\-\s]+$/', $no_hp)) {
            throw new Exception('Format nomor HP tidak valid!');
        }
        
        // Update data
        $stmt = $pdo->prepare("
            UPDATE siswa 
            SET nis = ?, nama = ?, kelas = ?, jenis_kelamin = ?, 
                alamat = ?, no_hp = ?, tahun_ajaran = ?, status = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$nis, $nama, $kelas, $jenis_kelamin, $alamat, $no_hp, $tahun_ajaran, $status, $id])) {
            $_SESSION['alert'] = [
                'type' => 'success', 
                'message' => "✅ Data siswa \"$nama\" berhasil diperbarui!"
            ];
        } else {
            throw new Exception('Gagal memperbarui data ke database!');
        }
        
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'danger', 
            'message' => 'Gagal memperbarui siswa: ' . $e->getMessage()
        ];
    }
    
    // Clean redirect
    ob_end_clean();
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

// Set default values from database
$nis = $siswa['nis'];
$nama = $siswa['nama'];
$kelas = $siswa['kelas'];
$jenis_kelamin = $siswa['jenis_kelamin'];
$alamat = $siswa['alamat'];
$no_hp = $siswa['no_hp'];
$tahun_ajaran = $siswa['tahun_ajaran'];
$status = $siswa['status'];

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

<!-- Alert Messages -->
<?php 
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
    echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-' . ($alert['type'] === 'success' ? 'check-circle' : 'exclamation-triangle') . '"></i> ';
    echo $alert['message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}
?>

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
                <form method="POST" action="" id="formEditSiswa">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nis" class="form-label">
                                NIS <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nis" name="nis"
                                value="<?php echo htmlspecialchars($nis); ?>" placeholder="Masukkan NIS siswa" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tahun_ajaran" class="form-label">
                                Tahun Ajaran <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran"
                                value="<?php echo htmlspecialchars($tahun_ajaran); ?>" placeholder="2023/2024" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nama" class="form-label">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nama" name="nama"
                            value="<?php echo htmlspecialchars($nama); ?>" placeholder="Masukkan nama lengkap siswa"
                            required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="kelas" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="kelas" name="kelas" required>
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
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="jenis_kelamin" class="form-label">
                                Jenis Kelamin <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo $jenis_kelamin === 'L' ? 'selected' : ''; ?>>Laki-laki
                                </option>
                                <option value="P" <?php echo $jenis_kelamin === 'P' ? 'selected' : ''; ?>>Perempuan
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="aktif" <?php echo $status === 'aktif' ? 'selected' : ''; ?>>Aktif
                                </option>
                                <option value="nonaktif" <?php echo $status === 'nonaktif' ? 'selected' : ''; ?>>
                                    Non-aktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3"
                            placeholder="Masukkan alamat lengkap siswa"><?php echo htmlspecialchars($alamat); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor HP</label>
                        <input type="text" class="form-control" id="no_hp" name="no_hp"
                            value="<?php echo htmlspecialchars($no_hp); ?>"
                            placeholder="Masukkan nomor HP siswa/orangtua">
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-x-circle"></i>
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnUpdate">
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
                try {
                    // Check if siswa has penilaian
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE siswa_id = ?");
                    $stmt->execute([$id]);
                    $has_penilaian = $stmt->fetchColumn() > 0;
                } catch (Exception $e) {
                    $has_penilaian = false;
                }
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

<script>
// Form submission handling
document.getElementById('formEditSiswa').addEventListener('submit', function(e) {
    const btnUpdate = document.getElementById('btnUpdate');
    if (btnUpdate) {
        btnUpdate.disabled = true;
        btnUpdate.innerHTML = '<i class="bi bi-hourglass-split"></i> Memperbarui...';
    }
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
        }, 5000);
    });
});
</script>

<?php 
ob_end_flush();
require_once '../../includes/footer.php'; 
?>