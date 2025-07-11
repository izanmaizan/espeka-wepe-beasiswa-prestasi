<?php
require_once '../../includes/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect ke dashboard
                header('Location: ../dashboard/index.php');
                exit();
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SPK Beasiswa Prestasi</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    body {
        /* Background image dengan overlay */
        background-image:
            linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url('./smp.jpeg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    .login-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    .school-header {
        background: #2563eb;
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 2rem;
        text-align: center;
    }

    .form-control {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
    }

    .btn-primary {
        background-color: #2563eb;
        border-color: #2563eb;
        border-radius: 8px;
        padding: 0.75rem;
        font-weight: 500;
    }

    .btn-primary:hover {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
    }

    .info-card {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0">
                <!-- Left Column - School Info -->
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="school-header">
                            <div class="mb-3">
                                <i class="bi bi-mortarboard" style="font-size: 3rem;"></i>
                            </div>
                            <h4 class="mb-2">SMP Negeri 2 Ampek Angkek</h4>
                            <p class="mb-0 opacity-90">Sistem Pendukung Keputusan<br>Beasiswa Prestasi</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="info-card p-3 mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="bi bi-info-circle"></i>
                                    Tentang Sistem
                                </h6>
                                <p class="mb-0 small text-muted">
                                    Sistem ini membantu dalam proses seleksi beasiswa prestasi menggunakan
                                    metode Weighted Product untuk memberikan hasil yang objektif dan akurat.
                                </p>
                            </div>

                            <div class="info-card p-3">
                                <h6 class="text-primary mb-2">
                                    <i class="bi bi-shield-check"></i>
                                    Akses Terbatas
                                </h6>
                                <p class="mb-2 small text-muted">
                                    Silakan gunakan akun yang telah disediakan:
                                </p>
                                <div class="row text-center small">
                                    <div class="col-6">
                                        <div class="badge bg-primary mb-1">Admin</div>
                                        <div class="text-muted">admin / password</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="badge bg-info mb-1">Kepala Sekolah</div>
                                        <div class="text-muted">kepala_sekolah / password</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Login Form -->
                <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h3 class="text-dark mb-2">Masuk ke Sistem</h3>
                                <p class="text-muted">Silakan masukkan kredensial Anda</p>
                            </div>

                            <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label fw-medium">
                                        <i class="bi bi-person"></i> Username
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
                                        autocomplete="username" placeholder="Masukkan username">
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label fw-medium">
                                        <i class="bi bi-lock"></i> Password
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" required
                                        autocomplete="current-password" placeholder="Masukkan password">
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        Masuk ke Sistem
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-shield-lock"></i>
                                    Sistem dilindungi dan hanya untuk pengguna yang berwenang
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Focus ke username saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('username').focus();
    });

    // Handle enter key pada form
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const form = e.target.closest('form');
            if (form) {
                form.submit();
            }
        }
    });

    // Simple form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        if (!username || !password) {
            e.preventDefault();
            alert('Harap isi username dan password!');
            return false;
        }
    });
    </script>
</body>

</html>