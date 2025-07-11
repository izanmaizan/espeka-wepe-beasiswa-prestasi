<?php
$page_title = 'Data Siswa';
require_once '../../includes/header.php';

requireRole('admin');

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($id > 0) {
        try {
            // Get student name before deletion
            $stmt = $pdo->prepare("SELECT nama FROM siswa WHERE id = ?");
            $stmt->execute([$id]);
            $nama_siswa = $stmt->fetchColumn();
            
            if ($nama_siswa) {
                // Start transaction
                $pdo->beginTransaction();
                
                // Delete penilaian first (foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM penilaian WHERE siswa_id = ?");
                $stmt->execute([$id]);
                
                // Delete hasil_perhitungan if exists
                $stmt = $pdo->prepare("DELETE FROM hasil_perhitungan WHERE siswa_id = ?");
                $stmt->execute([$id]);
                
                // Delete siswa
                $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
                $stmt->execute([$id]);
                
                $pdo->commit();
                setAlert('success', "✅ Data siswa \"" . htmlspecialchars($nama_siswa) . "\" berhasil dihapus beserta semua data terkait!");
            } else {
                setAlert('danger', 'Data siswa tidak ditemukan!');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setAlert('danger', 'Gagal menghapus data siswa: ' . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setAlert('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    } else {
        setAlert('danger', 'ID siswa tidak valid!');
    }
    
    // Redirect to clean URL
    header('Location: index.php');
    exit();
}

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($id > 0) {
        try {
            // Get current status and student name
            $stmt = $pdo->prepare("SELECT status, nama FROM siswa WHERE id = ?");
            $stmt->execute([$id]);
            $siswa_data = $stmt->fetch();
            
            if ($siswa_data) {
                // Toggle status
                $new_status = ($siswa_data['status'] === 'aktif') ? 'nonaktif' : 'aktif';
                $stmt = $pdo->prepare("UPDATE siswa SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                
                $status_text = ($new_status === 'aktif') ? 'diaktifkan' : 'dinonaktifkan';
                setAlert('success', "✅ Status siswa \"" . htmlspecialchars($siswa_data['nama']) . "\" berhasil $status_text!");
            } else {
                setAlert('danger', 'Data siswa tidak ditemukan!');
            }
        } catch (PDOException $e) {
            setAlert('danger', 'Gagal mengubah status siswa: ' . $e->getMessage());
        }
    } else {
        setAlert('danger', 'ID siswa tidak valid!');
    }
    
    // Redirect to clean URL
    header('Location: index.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and Filter
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$tingkat_filter = isset($_GET['tingkat']) ? cleanInput($_GET['tingkat']) : '';

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(nis LIKE ? OR nama LIKE ? OR kelas LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($tingkat_filter)) {
    $where_clauses[] = "tingkat = ?";
    $params[] = $tingkat_filter;
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total records
$stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa $where_clause");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get data siswa
$stmt = $pdo->prepare("
    SELECT * FROM siswa 
    $where_clause
    ORDER BY nama ASC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$siswa_list = $stmt->fetchAll();

// Get statistics
$stats = [];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM siswa GROUP BY status");
    while ($row = $stmt->fetch()) {
        $stats[$row['status']] = $row['count'];
    }

    $stmt = $pdo->query("SELECT tingkat, COUNT(*) as count FROM siswa WHERE status = 'aktif' GROUP BY tingkat ORDER BY tingkat");
    $tingkat_stats = [];
    while ($row = $stmt->fetch()) {
        $tingkat_stats[$row['tingkat']] = $row['count'];
    }
} catch (Exception $e) {
    $stats = [];
    $tingkat_stats = [];
}

// Helper function for query string
function buildQueryString($params) {
    $queryParts = [];
    foreach ($params as $key => $value) {
        if (!empty($value)) {
            $queryParts[] = $key . '=' . urlencode($value);
        }
    }
    return !empty($queryParts) ? '&' . implode('&', $queryParts) : '';
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Data Siswa', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-people"></i>
        Data Siswa
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="tambah.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i>
                Tambah Siswa
            </a>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-bar-chart"></i>
                    Statistik Status
                </h6>
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-success"><?php echo $stats['aktif'] ?? 0; ?></h4>
                        <small class="text-muted">Siswa Aktif</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning"><?php echo $stats['nonaktif'] ?? 0; ?></h4>
                        <small class="text-muted">Siswa Non-aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-layers"></i>
                    Distribusi Tingkat (Aktif)
                </h6>
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="text-primary"><?php echo $tingkat_stats['7'] ?? 0; ?></h4>
                        <small class="text-muted">Tingkat VII</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-success"><?php echo $tingkat_stats['8'] ?? 0; ?></h4>
                        <small class="text-muted">Tingkat VIII</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-warning"><?php echo $tingkat_stats['9'] ?? 0; ?></h4>
                        <small class="text-muted">Tingkat IX</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Cari Siswa</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search"
                        placeholder="Cari berdasarkan NIS, nama, atau kelas..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Filter Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="nonaktif" <?php echo $status_filter === 'nonaktif' ? 'selected' : ''; ?>>Non-aktif
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="tingkat" class="form-label">Filter Tingkat</label>
                <select class="form-select" id="tingkat" name="tingkat">
                    <option value="">Semua Tingkat</option>
                    <option value="7" <?php echo $tingkat_filter === '7' ? 'selected' : ''; ?>>Tingkat VII</option>
                    <option value="8" <?php echo $tingkat_filter === '8' ? 'selected' : ''; ?>>Tingkat VIII</option>
                    <option value="9" <?php echo $tingkat_filter === '9' ? 'selected' : ''; ?>>Tingkat IX</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-table"></i>
            Daftar Siswa
            <span class="badge bg-secondary"><?php echo number_format($total_records); ?> data</span>
            <?php if (!empty($search) || !empty($status_filter) || !empty($tingkat_filter)): ?>
            <span class="badge bg-info">Filter Aktif</span>
            <?php endif; ?>
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($siswa_list)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-2">Tidak ada data siswa</h6>
            <?php if (empty($search) && empty($status_filter) && empty($tingkat_filter)): ?>
            <p class="text-muted">Silakan tambah data siswa terlebih dahulu.</p>
            <a href="tambah.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Tambah Siswa
            </a>
            <?php else: ?>
            <p class="text-muted">Tidak ditemukan data siswa dengan filter yang diterapkan</p>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise"></i> Reset Filter
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th>Jenis Kelamin</th>
                        <th>No. HP</th>
                        <th>Status</th>
                        <th width="18%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = $offset + 1;
                    foreach ($siswa_list as $siswa): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <span class="fw-semibold"><?php echo htmlspecialchars($siswa['nis']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($siswa['nama']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($siswa['kelas']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">T<?php echo $siswa['tingkat']; ?></span>
                        </td>
                        <td>
                            <span
                                class="badge <?php echo $siswa['jenis_kelamin'] === 'L' ? 'bg-primary' : 'bg-danger'; ?>">
                                <?php echo $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($siswa['no_hp'] ?: '-'); ?></td>
                        <td>
                            <span
                                class="badge <?php echo $siswa['status'] === 'aktif' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($siswa['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="detail.php?id=<?php echo $siswa['id']; ?>" class="btn btn-outline-info"
                                    title="Detail" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $siswa['id']; ?>" class="btn btn-outline-warning"
                                    title="Edit" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="index.php?action=toggle_status&id=<?php echo $siswa['id']; ?>"
                                    class="btn btn-outline-secondary confirm-toggle"
                                    data-nama="<?php echo htmlspecialchars($siswa['nama']); ?>"
                                    data-status="<?php echo $siswa['status']; ?>"
                                    title="<?php echo $siswa['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>"
                                    data-bs-toggle="tooltip">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                                <a href="index.php?action=delete&id=<?php echo $siswa['id']; ?>"
                                    class="btn btn-outline-danger confirm-delete"
                                    data-nama="<?php echo htmlspecialchars($siswa['nama']); ?>" title="Hapus"
                                    data-bs-toggle="tooltip">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page - 1; ?><?php echo buildQueryString(['search' => $search, 'status' => $status_filter, 'tingkat' => $tingkat_filter]); ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $i; ?><?php echo buildQueryString(['search' => $search, 'status' => $status_filter, 'tingkat' => $tingkat_filter]); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page + 1; ?><?php echo buildQueryString(['search' => $search, 'status' => $status_filter, 'tingkat' => $tingkat_filter]); ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript Functions -->
<script>
// Initialize tooltips dan event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle delete confirmation - Simple approach
    document.addEventListener('click', function(e) {
        if (e.target.closest('.confirm-delete')) {
            e.preventDefault();
            const link = e.target.closest('.confirm-delete');
            const nama = link.getAttribute('data-nama');
            const href = link.getAttribute('href');

            // Simple confirm dialog
            const confirmMessage = `KONFIRMASI HAPUS SISWA\n\n` +
                `Nama: ${nama}\n\n` +
                `PERINGATAN:\n` +
                `• Data penilaian akan ikut terhapus\n` +
                `• Hasil perhitungan akan ikut terhapus\n` +
                `• Tindakan tidak dapat dibatalkan\n\n` +
                `Yakin ingin menghapus?`;

            if (confirm(confirmMessage)) {
                // Show simple loading
                const originalText = link.innerHTML;
                link.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                link.style.pointerEvents = 'none';

                // Navigate to delete URL
                window.location.href = href;
            }
        }

        // Handle toggle status confirmation
        if (e.target.closest('.confirm-toggle')) {
            e.preventDefault();
            const link = e.target.closest('.confirm-toggle');
            const nama = link.getAttribute('data-nama');
            const status = link.getAttribute('data-status');
            const href = link.getAttribute('href');

            const newStatus = status === 'aktif' ? 'NON-AKTIF' : 'AKTIF';
            const action = status === 'aktif' ? 'menonaktifkan' : 'mengaktifkan';

            const confirmMessage = `KONFIRMASI UBAH STATUS\n\n` +
                `Nama: ${nama}\n` +
                `Status saat ini: ${status.toUpperCase()}\n` +
                `Status baru: ${newStatus}\n\n` +
                `Yakin ingin ${action} siswa ini?`;

            if (confirm(confirmMessage)) {
                // Show loading
                const originalText = link.innerHTML;
                link.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                link.style.pointerEvents = 'none';

                // Navigate to toggle URL
                window.location.href = href;
            }
        }
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentNode) {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                } catch (e) {
                    alert.style.display = 'none';
                }
            }
        }, 5000);
    });
});

// Debug function - bisa dipanggil dari console untuk testing
window.testConfirm = function(message) {
    alert('Test: ' + message);
    return confirm('Test confirm: ' + message);
};

// Log untuk debugging
console.log('Siswa index.php JavaScript loaded successfully');
</script>

<?php require_once '../../includes/footer.php'; ?>