<?php
$page_title = 'Data Siswa';
require_once '../../includes/header.php';

requireRole('admin');

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        setAlert('success', 'Data siswa berhasil dihapus!');
    } catch (PDOException $e) {
        setAlert('danger', 'Gagal menghapus data siswa!');
    }
    header('Location: index.php');
    exit();
}

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT status FROM siswa WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $current_status = $stmt->fetchColumn();
        
        // Toggle status
        $new_status = ($current_status === 'aktif') ? 'nonaktif' : 'aktif';
        $stmt = $pdo->prepare("UPDATE siswa SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $_GET['id']]);
        
        setAlert('success', 'Status siswa berhasil diubah!');
    } catch (PDOException $e) {
        setAlert('danger', 'Gagal mengubah status siswa!');
    }
    header('Location: index.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE nis LIKE ? OR nama LIKE ? OR kelas LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

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

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search"
                        placeholder="Cari berdasarkan NIS, nama, atau kelas..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Cari</button>
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
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($siswa_list)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-2">Tidak ada data siswa</h6>
            <?php if (empty($search)): ?>
            <p class="text-muted">Silakan tambah data siswa terlebih dahulu.</p>
            <a href="tambah.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Tambah Siswa
            </a>
            <?php else: ?>
            <p class="text-muted">Tidak ditemukan data siswa dengan kata kunci
                "<?php echo htmlspecialchars($search); ?>"</p>
            <a href="index.php" class="btn btn-outline-secondary">Reset Pencarian</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jenis Kelamin</th>
                        <th>No. HP</th>
                        <th>Tahun Ajaran</th>
                        <th>Status</th>
                        <th width="15%">Aksi</th>
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
                        <td><?php echo htmlspecialchars($siswa['nama']); ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($siswa['kelas']); ?></span>
                        </td>
                        <td>
                            <span
                                class="badge <?php echo $siswa['jenis_kelamin'] === 'L' ? 'bg-primary' : 'bg-danger'; ?>">
                                <?php echo $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($siswa['no_hp'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($siswa['tahun_ajaran']); ?></td>
                        <td>
                            <span
                                class="badge <?php echo $siswa['status'] === 'aktif' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($siswa['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="detail.php?id=<?php echo $siswa['id']; ?>" class="btn btn-outline-info"
                                    title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $siswa['id']; ?>" class="btn btn-outline-warning"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?action=toggle_status&id=<?php echo $siswa['id']; ?>"
                                    class="btn btn-outline-secondary"
                                    title="<?php echo $siswa['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>"
                                    onclick="return confirm('Yakin ingin mengubah status siswa ini?')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $siswa['id']; ?>" class="btn btn-outline-danger"
                                    title="Hapus"
                                    onclick="return confirmDelete('Yakin ingin menghapus data siswa ini? Semua data penilaian akan ikut terhapus!')">
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
                            href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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

<?php require_once '../../includes/footer.php'; ?>