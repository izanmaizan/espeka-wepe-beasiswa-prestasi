<?php
// Installer/Setup untuk Sistem SPK Beasiswa
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

// Fungsi untuk mengecek requirements
function checkRequirements() {
    $requirements = [
        'PHP Version' => [
            'current' => PHP_VERSION,
            'required' => '7.4.0',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'PDO Extension' => [
            'current' => extension_loaded('pdo') ? 'Loaded' : 'Not Loaded',
            'required' => 'Required',
            'status' => extension_loaded('pdo')
        ],
        'PDO MySQL Extension' => [
            'current' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Not Loaded',
            'required' => 'Required',
            'status' => extension_loaded('pdo_mysql')
        ],
        'Config Directory Writable' => [
            'current' => is_writable('../config') ? 'Writable' : 'Not Writable',
            'required' => 'Required',
            'status' => is_writable('../config')
        ]
    ];
    
    return $requirements;
}

// Fungsi untuk test koneksi database
function testDatabaseConnection($host, $username, $password, $database = null) {
    try {
        $dsn = "mysql:host=$host" . ($database ? ";dbname=$database" : "") . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Fungsi untuk membuat database
function createDatabase($pdo, $database_name) {
    try {
        $stmt = $pdo->prepare("CREATE DATABASE IF NOT EXISTS `$database_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk menjalankan SQL script
function runSQLScript($pdo, $sql_file) {
    try {
        $sql = file_get_contents($sql_file);
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Proses setup berdasarkan step
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        // Test database connection
        $host = $_POST['db_host'];
        $username = $_POST['db_username'];
        $password = $_POST['db_password'];
        $database = $_POST['db_name'];
        
        $test = testDatabaseConnection($host, $username, $password);
        
        if ($test['success']) {
            // Create database if not exists
            if (createDatabase($test['pdo'], $database)) {
                // Test connection to specific database
                $test_db = testDatabaseConnection($host, $username, $password, $database);
                
                if ($test_db['success']) {
                    $success[] = 'Koneksi database berhasil!';
                    
                    // Save database config
                    $config_content = "<?php\n";
                    $config_content .= "// Konfigurasi Database\n";
                    $config_content .= "define('DB_HOST', '" . addslashes($host) . "');\n";
                    $config_content .= "define('DB_USERNAME', '" . addslashes($username) . "');\n";
                    $config_content .= "define('DB_PASSWORD', '" . addslashes($password) . "');\n";
                    $config_content .= "define('DB_NAME', '" . addslashes($database) . "');\n\n";
                    $config_content .= "// Membuat koneksi database\n";
                    $config_content .= "function getDBConnection() {\n";
                    $config_content .= "    try {\n";
                    $config_content .= "        \$pdo = new PDO(\n";
                    $config_content .= "            \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",\n";
                    $config_content .= "            DB_USERNAME,\n";
                    $config_content .= "            DB_PASSWORD,\n";
                    $config_content .= "            [\n";
                    $config_content .= "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
                    $config_content .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
                    $config_content .= "                PDO::ATTR_EMULATE_PREPARES => false,\n";
                    $config_content .= "            ]\n";
                    $config_content .= "        );\n";
                    $config_content .= "        return \$pdo;\n";
                    $config_content .= "    } catch (PDOException \$e) {\n";
                    $config_content .= "        die(\"Koneksi database gagal: \" . \$e->getMessage());\n";
                    $config_content .= "    }\n";
                    $config_content .= "}\n\n";
                    $config_content .= "// Inisialisasi koneksi global\n";
                    $config_content .= "\$pdo = getDBConnection();\n";
                    $config_content .= "?>";

if (file_put_contents('../config/database.php', $config_content)) {
$success[] = 'File konfigurasi database berhasil dibuat!';
$step = 3;
} else {
$errors[] = 'Gagal menulis file konfigurasi database!';
}
} else {
$errors[] = 'Gagal terhubung ke database: ' . $test_db['error'];
}
} else {
$errors[] = 'Gagal membuat database!';
}
} else {
$errors[] = 'Koneksi database gagal: ' . $test['error'];
}
} elseif ($step === 3) {
// Import database schema
if (file_exists('../database.sql')) {
require_once '../config/database.php';

if (runSQLScript($pdo, '../database.sql')) {
$success[] = 'Database berhasil diimport!';
$step = 4;
} else {
$errors[] = 'Gagal mengimport database schema!';
}
} else {
$errors[] = 'File database.sql tidak ditemukan!';
}
}
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - SPK Beasiswa Prestasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .setup-container {
        max-width: 800px;
        margin: 50px auto;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .step-indicator {
        margin-bottom: 30px;
    }

    .step {
        display: inline-block;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        line-height: 40px;
        text-align: center;
        margin-right: 10px;
    }

    .step.active {
        background: #667eea;
        color: white;
    }

    .step.completed {
        background: #28a745;
        color: white;
    }

    .step.pending {
        background: #e9ecef;
        color: #6c757d;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="setup-container">
            <div class="text-center mb-4">
                <h2 class="text-white"><i class="bi bi-gear"></i> Setup SPK Beasiswa Prestasi</h2>
                <p class="text-light">SMP Negeri 2 Ampek Angkek</p>
            </div>

            <!-- Step Indicator -->
            <div class="text-center step-indicator">
                <span
                    class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : 'pending'; ?>">1</span>
                <span
                    class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : 'pending'; ?>">2</span>
                <span
                    class="step <?php echo $step >= 3 ? ($step == 3 ? 'active' : 'completed') : 'pending'; ?>">3</span>
                <span class="step <?php echo $step >= 4 ? 'active' : 'pending'; ?>">4</span>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6><i class="bi bi-exclamation-triangle"></i> Error:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php foreach ($success as $msg): ?>
                <div><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- Step 1: Requirements Check -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list-check"></i> Step 1: Cek Requirements</h5>
                </div>
                <div class="card-body">
                    <p>Memastikan server memenuhi requirement sistem:</p>

                    <?php $requirements = checkRequirements(); ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Requirement</th>
                                    <th>Current</th>
                                    <th>Required</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $name => $req): ?>
                                <tr>
                                    <td><?php echo $name; ?></td>
                                    <td><?php echo $req['current']; ?></td>
                                    <td><?php echo $req['required']; ?></td>
                                    <td>
                                        <?php if ($req['status']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> OK</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x"></i> Fail</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php 
                    $all_ok = true;
                    foreach ($requirements as $req) {
                        if (!$req['status']) {
                            $all_ok = false;
                            break;
                        }
                    }
                    ?>

                    <div class="text-end">
                        <?php if ($all_ok): ?>
                        <a href="?step=2" class="btn btn-primary">
                            <i class="bi bi-arrow-right"></i> Lanjut ke Database Setup
                        </a>
                        <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            Perbaiki Requirements Terlebih Dahulu
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php elseif ($step === 2): ?>
            <!-- Step 2: Database Configuration -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-database"></i> Step 2: Konfigurasi Database</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host"
                                    value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name"
                                    value="<?php echo $_POST['db_name'] ?? 'spk_beasiswa'; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_username" class="form-label">Database Username</label>
                                <input type="text" class="form-control" id="db_username" name="db_username"
                                    value="<?php echo $_POST['db_username'] ?? 'root'; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_password" class="form-label">Database Password</label>
                                <input type="password" class="form-control" id="db_password" name="db_password"
                                    value="<?php echo $_POST['db_password'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="?step=1" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check"></i> Test Koneksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($step === 3): ?>
            <!-- Step 3: Import Database -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-download"></i> Step 3: Import Database</h5>
                </div>
                <div class="card-body">
                    <p>Database konfigurasi berhasil! Sekarang akan mengimport schema dan data awal.</p>

                    <div class="alert alert-info">
                        <h6>Yang akan diimport:</h6>
                        <ul class="mb-0">
                            <li>Struktur tabel (users, siswa, kriteria, penilaian, hasil_perhitungan)</li>
                            <li>Data user default (admin & kepala_sekolah)</li>
                            <li>Data kriteria default</li>
                            <li>Data siswa contoh</li>
                            <li>Data penilaian contoh</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="d-flex justify-content-between">
                            <a href="?step=2" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-download"></i> Import Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($step === 4): ?>
            <!-- Step 4: Setup Complete -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-check-circle"></i> Setup Selesai!</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h5><i class="bi bi-party-popper"></i> Instalasi Berhasil!</h5>
                        <p>Sistem Pendukung Keputusan Beasiswa Prestasi telah berhasil diinstall.</p>
                    </div>

                    <h6>Informasi Login:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Admin</h6>
                                    <p class="card-text">
                                        <strong>Username:</strong> admin<br>
                                        <strong>Password:</strong> password
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Kepala Sekolah</h6>
                                    <p class="card-text">
                                        <strong>Username:</strong> kepala_sekolah<br>
                                        <strong>Password:</strong> password
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <h6><i class="bi bi-exclamation-triangle"></i> Penting:</h6>
                        <ul class="mb-0">
                            <li>Segera ubah password default setelah login</li>
                            <li>Hapus folder <code>install/</code> untuk keamanan</li>
                            <li>Backup database secara berkala</li>
                        </ul>
                    </div>

                    <div class="text-center mt-4">
                        <a href="../modules/auth/login.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login ke Sistem
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>