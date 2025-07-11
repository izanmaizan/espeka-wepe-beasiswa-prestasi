<?php
// File untuk debugging masalah database
// Simpan sebagai database_debug.php di root folder

require_once './config/database.php';

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$debug_info = getDatabaseStatus();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostic - SPK Beasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-gear"></i>
                            Database Diagnostic
                        </h5>
                    </div>
                    <div class="card-body">

                        <!-- Connection Status -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="text-secondary">Status Koneksi Database</h6>
                                <?php if ($debug_info['connected']): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i>
                                    Database terhubung dengan sukses
                                </div>
                                <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle"></i>
                                    Database tidak terhubung
                                    <?php if ($debug_info['error']): ?>
                                    <br><strong>Error:</strong> <?php echo htmlspecialchars($debug_info['error']); ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Configuration -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="text-secondary">Konfigurasi Database</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Host:</strong></td>
                                            <td><?php echo htmlspecialchars($debug_info['config']['host']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Database:</strong></td>
                                            <td><?php echo htmlspecialchars($debug_info['config']['database']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Username:</strong></td>
                                            <td><?php echo htmlspecialchars($debug_info['config']['username']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Password:</strong></td>
                                            <td><?php echo $debug_info['config']['password_set'] ? 'Set' : 'Not Set'; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Database Tests -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="text-secondary">Test Koneksi</h6>

                                <?php
                                // Test MySQL Server Connection
                                try {
                                    $test_pdo = new PDO(
                                        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
                                        DB_USERNAME,
                                        DB_PASSWORD,
                                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                                    );
                                    echo '<div class="alert alert-success"><i class="bi bi-check"></i> MySQL Server: Connected</div>';
                                    
                                    // Test Database Exists
                                    if (checkDatabaseExists()) {
                                        echo '<div class="alert alert-success"><i class="bi bi-check"></i> Database Exists: Yes</div>';
                                        
                                        // Test Tables
                                        if (isset($debug_info['tables_exist']) && $debug_info['tables_exist']) {
                                            echo '<div class="alert alert-success"><i class="bi bi-check"></i> Required Tables: All Present</div>';
                                        } else {
                                            echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Required Tables: Missing some tables</div>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-danger"><i class="bi bi-x"></i> Database Exists: No</div>';
                                    }
                                    
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger"><i class="bi bi-x"></i> MySQL Server: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="text-secondary">Rekomendasi Perbaikan</h6>

                                <?php if (!$debug_info['connected']): ?>
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-lightbulb"></i> Langkah-langkah Perbaikan:</h6>
                                    <ol class="mb-0">
                                        <li>Pastikan MySQL/MariaDB server berjalan</li>
                                        <li>Periksa konfigurasi database di file <code>includes/app.php</code></li>
                                        <li>Pastikan username dan password database benar</li>
                                        <li>Pastikan database <code><?php echo DB_NAME; ?></code> sudah dibuat</li>
                                        <li>Pastikan user database memiliki akses ke database tersebut</li>
                                    </ol>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="bi bi-terminal"></i> Perintah MySQL untuk membuat database:</h6>
                                    <code>
                                        CREATE DATABASE <?php echo DB_NAME; ?> CHARACTER SET utf8mb4 COLLATE
                                        utf8mb4_unicode_ci;<br>
                                        GRANT ALL PRIVILEGES ON <?php echo DB_NAME; ?>.* TO
                                        '<?php echo DB_USERNAME; ?>'@'localhost';<br>
                                        FLUSH PRIVILEGES;
                                    </code>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i>
                                    Database berfungsi dengan baik! Anda dapat melanjutkan ke halaman login.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <a href="pages/auth/login.php" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Kembali ke Login
                                </a>
                                <button onclick="location.reload()" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Refresh Test
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>