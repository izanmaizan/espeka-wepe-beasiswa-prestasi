<?php
require_once __DIR__ . '/functions.php';

// Helper function untuk mendeteksi halaman aktif
function isActivePage($page_path) {
    $current_path = $_SERVER['REQUEST_URI'];
    $current_file = basename($_SERVER['PHP_SELF']);
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    
    // Normalisasi path
    $page_path = ltrim($page_path, '/');
    
    // Jika page_path adalah direktori (seperti 'siswa', 'kriteria')
    if (strpos($page_path, '.php') === false) {
        return $current_dir === $page_path;
    }
    
    // Jika page_path adalah file spesifik
    return strpos($current_path, $page_path) !== false;
}

// Helper function untuk mendeteksi menu group aktif
function isActiveSection($section) {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    return $current_dir === $section;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>SPK Beasiswa Prestasi</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
    .navbar-brand {
        font-weight: bold;
    }

    .sidebar {
        min-height: calc(100vh - 56px);
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
    }

    .nav-link {
        color: #6c757d;
        border-radius: 0.375rem;
        margin-bottom: 0.25rem;
        transition: all 0.2s;
    }

    .nav-link:hover,
    .nav-link.active {
        color: #0d6efd;
        background-color: #e7f1ff;
        font-weight: 600;
    }

    .nav-link i {
        width: 16px;
        margin-right: 8px;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .btn-sm {
        font-size: 0.875rem;
    }

    .table th {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        font-weight: 600;
    }

    .badge {
        font-size: 0.75em;
    }

    .alert {
        border: none;
        border-left: 4px solid;
    }

    .alert-success {
        border-left-color: #198754;
    }

    .alert-danger {
        border-left-color: #dc3545;
    }

    .alert-warning {
        border-left-color: #ffc107;
    }

    .alert-info {
        border-left-color: #0dcaf0;
    }

    .nav-section-header {
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        padding-left: 0.75rem;
    }

    .nav-link.disabled {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Highlight active section */
    .nav-section-header.active {
        color: #0d6efd;
    }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard/index.php">
                <i class="bi bi-mortarboard"></i>
                SPK Beasiswa Prestasi
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
                            <span
                                class="badge bg-<?php echo $_SESSION['role'] === 'admin' ? 'warning' : 'info'; ?> ms-1">
                                <?php echo $_SESSION['role'] === 'admin' ? 'Admin' : 'Kepala Sekolah'; ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">
                                    Role: <?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? '')); ?>
                                </span></li>
                            <li><span class="dropdown-item-text small text-muted">
                                    Login: <?php echo date('d/m/Y H:i'); ?>
                                </span></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('dashboard') ? 'active' : ''; ?>"
                                href="../dashboard/index.php">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>

                        <?php if (hasRole('admin')): ?>
                        <!-- Admin Only Section -->
                        <div
                            class="nav-section-header <?php echo in_array(basename(dirname($_SERVER['PHP_SELF'])), ['siswa', 'kriteria', 'penilaian']) ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i> Management
                        </div>

                        <!-- Data Siswa -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveSection('siswa') ? 'active' : ''; ?>"
                                href="../siswa/index.php">
                                <i class="bi bi-people"></i>
                                Data Siswa
                            </a>
                        </li>

                        <!-- Kriteria Penilaian -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveSection('kriteria') ? 'active' : ''; ?>"
                                href="../kriteria/index.php">
                                <i class="bi bi-list-check"></i>
                                Kriteria Penilaian
                            </a>
                        </li>

                        <!-- Input Penilaian -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveSection('penilaian') ? 'active' : ''; ?>"
                                href="../penilaian/index.php">
                                <i class="bi bi-clipboard-data"></i>
                                Input Penilaian
                            </a>
                        </li>

                        <!-- Perhitungan Section -->
                        <div
                            class="nav-section-header <?php echo isActivePage('laporan/hitung.php') ? 'active' : ''; ?>">
                            <i class="bi bi-calculator"></i> Perhitungan
                        </div>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isActivePage('laporan/hitung.php') ? 'active' : ''; ?>"
                                href="../laporan/hitung.php">
                                <i class="bi bi-calculator"></i>
                                Hitung Ranking
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Reports Section - Available for both roles -->
                        <div
                            class="nav-section-header <?php echo isActiveSection('laporan') && !isActivePage('laporan/hitung.php') ? 'active' : ''; ?>">
                            <i class="bi bi-file-text"></i> Laporan
                        </div>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveSection('laporan') && basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"
                                href="../laporan/index.php">
                                <i class="bi bi-file-earmark-text"></i>
                                Hasil Beasiswa
                            </a>
                        </li>

                        <?php if (hasRole('admin')): ?>
                        <!-- System Admin Section -->
                        <div class="nav-section-header <?php echo isActiveSection('users') ? 'active' : ''; ?>">
                            <i class="bi bi-shield"></i> System
                        </div>

                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveSection('users') ? 'active' : ''; ?>"
                                href="../users/index.php">
                                <i class="bi bi-people-fill"></i>
                                User Management
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <!-- System Info -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="px-3">
                            <h6 class="text-muted small">Sistem Info</h6>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-diagram-2"></i>
                                Metode: Weighted Product
                            </p>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-trophy"></i>
                                Target: 10 Penerima Global
                            </p>
                            <p class="small text-muted mb-0">
                                <i class="bi bi-star"></i>
                                3 Terbaik per Tingkat
                            </p>
                        </div>
                    </div>

                    <?php if (hasRole('admin')): ?>
                    <!-- Quick Stats for Admin -->
                    <div class="mt-3 px-3">
                        <h6 class="text-muted small">Quick Stats</h6>
                        <?php
                        try {
                            // Get quick stats
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM siswa WHERE status = 'aktif'");
                            $total_siswa = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM kriteria");
                            $total_kriteria = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(DISTINCT siswa_id) as total FROM penilaian");
                            $siswa_dinilai = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $total_siswa = 0;
                            $total_kriteria = 0;
                            $siswa_dinilai = 0;
                        }
                        ?>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Siswa:</span>
                            <span class="small fw-semibold"><?php echo $total_siswa; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Kriteria:</span>
                            <span class="small fw-semibold"><?php echo $total_kriteria; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Dinilai:</span>
                            <span class="small fw-semibold"><?php echo $siswa_dinilai; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="pt-3 pb-2 mb-3">

                    <?php
                    // Tampilkan alert jika ada
                    $alert = getAlert();
                    if ($alert):
                    ?>
                    <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($alert['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Role-based Welcome Message -->
                    <?php if (!isset($_SESSION['welcome_shown'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <h6 class="alert-heading">
                            <i class="bi bi-hand-thumbs-up"></i>
                            Selamat datang, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>!
                        </h6>
                        <?php if (hasRole('admin')): ?>
                        <p class="mb-0">Anda login sebagai <strong>Administrator</strong>. Anda memiliki akses penuh
                            untuk mengelola data siswa, kriteria, penilaian, dan menjalankan perhitungan beasiswa.</p>
                        <?php else: ?>
                        <p class="mb-0">Anda login sebagai <strong>Kepala Sekolah</strong>. Anda dapat melihat dan
                            mencetak laporan hasil perhitungan beasiswa prestasi.</p>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php $_SESSION['welcome_shown'] = true; endif; ?>

                    <!-- Debug info (bisa dihapus di production) -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-warning">
                        <strong>Debug Info:</strong><br>
                        Current Directory: <?php echo basename(dirname($_SERVER['PHP_SELF'])); ?><br>
                        Current File: <?php echo basename($_SERVER['PHP_SELF']); ?><br>
                        Request URI: <?php echo $_SERVER['REQUEST_URI']; ?><br>
                        PHP Self: <?php echo $_SERVER['PHP_SELF']; ?>
                    </div>
                    <?php endif; ?>