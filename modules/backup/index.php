<?php
$page_title = 'Backup & Restore';
require_once '../../includes/header.php';
require_once '../../includes/logger.php';

requireRole('admin');

// Backup functions
function createDatabaseBackup($pdo) {
    try {
        $backupDir = __DIR__ . '/../../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . $filename;
        
        // Get all tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- SPK Beasiswa Database Backup\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: " . DB_NAME . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sql .= "-- Table structure for `$table`\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $row['Create Table'] . ";\n\n";
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $sql .= "-- Data for table `$table`\n";
                $sql .= "INSERT INTO `$table` VALUES \n";
                
                $values = [];
                foreach ($rows as $row) {
                    $escapedValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $escapedValues[] = 'NULL';
                        } else {
                            $escapedValues[] = $pdo->quote($value);
                        }
                    }
                    $values[] = '(' . implode(', ', $escapedValues) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents($filepath, $sql);
        
        logger()->info('Database backup created', ['filename' => $filename, 'size' => filesize($filepath)]);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath)
        ];
        
    } catch (Exception $e) {
        logger()->error('Database backup failed', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function restoreDatabase($pdo, $filepath) {
    try {
        if (!file_exists($filepath)) {
            throw new Exception('Backup file not found');
        }
        
        $sql = file_get_contents($filepath);
        
        // Split SQL into individual statements
        $statements = explode(';', $sql);
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        $pdo->commit();
        
        logger()->info('Database restored', ['filepath' => $filepath]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        logger()->error('Database restore failed', ['error' => $e->getMessage(), 'filepath' => $filepath]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function getBackupFiles() {
    $backupDir = __DIR__ . '/../../backups/';
    if (!is_dir($backupDir)) {
        return [];
    }
    
    $files = glob($backupDir . '*.sql');
    $backups = [];
    
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'filepath' => $file,
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    return $backups;
}

function deleteOldBackups($retentionDays = 30) {
    $backupDir = __DIR__ . '/../../backups/';
    $files = glob($backupDir . '*.sql');
    $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
    $deletedCount = 0;
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            if (unlink($file)) {
                $deletedCount++;
            }
        }
    }
    
    return $deletedCount;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_backup':
                $result = createDatabaseBackup($pdo);
                if ($result['success']) {
                    setAlert('success', 'Backup berhasil dibuat: ' . $result['filename']);
                } else {
                    setAlert('danger', 'Gagal membuat backup: ' . $result['error']);
                }
                break;
                
            case 'restore_backup':
                if (isset($_POST['backup_file'])) {
                    $filepath = __DIR__ . '/../../backups/' . basename($_POST['backup_file']);
                    $result = restoreDatabase($pdo, $filepath);
                    if ($result['success']) {
                        setAlert('success', 'Database berhasil direstore!');
                    } else {
                        setAlert('danger', 'Gagal merestore database: ' . $result['error']);
                    }
                }
                break;
                
            case 'delete_backup':
                if (isset($_POST['backup_file'])) {
                    $filepath = __DIR__ . '/../../backups/' . basename($_POST['backup_file']);
                    if (file_exists($filepath) && unlink($filepath)) {
                        setAlert('success', 'Backup berhasil dihapus!');
                        logger()->info('Backup deleted', ['filename' => basename($_POST['backup_file'])]);
                    } else {
                        setAlert('danger', 'Gagal menghapus backup!');
                    }
                }
                break;
                
            case 'cleanup_old_backups':
                $deletedCount = deleteOldBackups(30);
                setAlert('success', "Berhasil menghapus $deletedCount backup lama.");
                logger()->info('Old backups cleaned up', ['deleted_count' => $deletedCount]);
                break;
        }
        
        header('Location: index.php');
        exit();
    }
}

// Handle download
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . '/../../backups/' . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    } else {
        setAlert('danger', 'File backup tidak ditemukan!');
    }
}

$backupFiles = getBackupFiles();

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'Backup & Restore', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-shield-check"></i>
        Backup & Restore Database
    </h1>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-download text-primary" style="font-size: 2rem;"></i>
                <h6 class="mt-2">Buat Backup</h6>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-download"></i> Backup Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-files text-success" style="font-size: 2rem;"></i>
                <h6 class="mt-2">Total Backup</h6>
                <h4 class="text-success"><?php echo count($backupFiles); ?></h4>
                <small class="text-muted">file tersimpan</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="bi bi-hdd text-info" style="font-size: 2rem;"></i>
                <h6 class="mt-2">Total Ukuran</h6>
                <h6 class="text-info">
                    <?php 
                    $totalSize = array_sum(array_column($backupFiles, 'size'));
                    echo formatFileSize($totalSize);
                    ?>
                </h6>
                <small class="text-muted">space terpakai</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-trash text-warning" style="font-size: 2rem;"></i>
                <h6 class="mt-2">Cleanup</h6>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cleanup_old_backups">
                    <button type="submit" class="btn btn-warning btn-sm"
                        onclick="return confirm('Hapus backup yang lebih dari 30 hari?')">
                        <i class="bi bi-trash"></i> Bersihkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Upload Backup -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-upload"></i>
            Upload & Restore Backup
        </h6>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Peringatan!</strong>
            Proses restore akan menghapus semua data yang ada dan menggantinya dengan data dari backup.
            Pastikan Anda sudah membuat backup terbaru sebelum melakukan restore.
        </div>

        <form method="POST" enctype="multipart/form-data" id="restore-form">
            <input type="hidden" name="action" value="restore_backup">
            <div class="row">
                <div class="col-md-8">
                    <input type="file" class="form-control" name="backup_file" accept=".sql" required>
                    <div class="form-text">Upload file backup (.sql) untuk direstore</div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-danger w-100"
                        onclick="return confirm('PERINGATAN: Ini akan menghapus semua data yang ada! Yakin ingin melanjutkan?')">
                        <i class="bi bi-arrow-clockwise"></i> Restore Database
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Backup Files List -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-list"></i>
            Daftar File Backup
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($backupFiles)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-2">Belum ada file backup</h6>
            <p class="text-muted">Buat backup pertama Anda untuk mengamankan data.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Tanggal</th>
                        <th>Ukuran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backupFiles as $backup): ?>
                    <tr>
                        <td>
                            <i class="bi bi-file-earmark-zip text-primary"></i>
                            <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', $backup['date']); ?>
                            <?php if ($backup['date'] > time() - 86400): ?>
                            <span class="badge bg-success badge-sm">Baru</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatFileSize($backup['size']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?download=<?php echo urlencode($backup['filename']); ?>"
                                    class="btn btn-outline-primary" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>

                                <button type="button" class="btn btn-outline-warning" title="Restore"
                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                    data-filename="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_backup">
                                    <input type="hidden" name="backup_file"
                                        value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Hapus"
                                        onclick="return confirm('Yakin ingin menghapus backup ini?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Konfirmasi Restore
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>PERINGATAN PENTING!</strong>
                    <ul class="mb-0 mt-2">
                        <li>Semua data yang ada akan dihapus</li>
                        <li>Data akan diganti dengan data dari backup</li>
                        <li>Proses ini tidak dapat dibatalkan</li>
                        <li>Pastikan Anda sudah membuat backup terbaru</li>
                    </ul>
                </div>
                <p>Anda akan merestore database dengan file: <strong id="restore-filename"></strong></p>
                <p>Yakin ingin melanjutkan?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" id="restore-confirm-form">
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" name="backup_file" id="restore-backup-file">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-arrow-clockwise"></i> Ya, Restore Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Handle restore modal
document.getElementById('restoreModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const filename = button.getAttribute('data-filename');

    document.getElementById('restore-filename').textContent = filename;
    document.getElementById('restore-backup-file').value = filename;
});

// Loading state for backup creation
document.querySelector('form[action="create_backup"]')?.addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Membuat...';
    button.disabled = true;
});
</script>

<?php require_once '../../includes/footer.php'; ?>