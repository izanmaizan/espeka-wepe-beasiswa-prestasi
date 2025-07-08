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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    .login-container {
        max-width: 400px;
        margin: 0 auto;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0 !important;
        text-align: center;
        padding: 2rem 1rem 1rem;
    }

    .card-body {
        padding: 2rem;
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-login {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 0.75rem;
        font-weight: 600;
        color: white;
        width: 100%;
    }

    .btn-login:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        color: white;
    }

    .school-info {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .login-info {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
        font-size: 0.875rem;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <!-- Informasi Sekolah -->
            <div class="school-info">
                <h4 class="text-primary mb-2">
                    <i class="bi bi-mortarboard"></i>
                    SMP Negeri 2 Ampek Angkek
                </h4>
                <p class="mb-0 text-muted">Sistem Pendukung Keputusan<br>Beasiswa Prestasi</p>
            </div>

            <!-- Form Login -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person-circle fs-3 d-block mb-2"></i>
                        Login Sistem
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person"></i> Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
                                autocomplete="username">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock"></i> Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required
                                autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Masuk Sistem
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informasi Login -->
            <div class="login-info text-center">
                <strong>Akun Default:</strong><br>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Admin</strong><br>
                        Username: admin<br>
                        Password: password
                    </div>
                    <div class="col-6">
                        <strong>Kepala Sekolah</strong><br>
                        Username: kepala_sekolah<br>
                        Password: password
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
            e.target.form.submit();
        }
    });
    </script>
</body>

</html>