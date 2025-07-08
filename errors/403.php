<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | SPK Beasiswa Prestasi</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    body {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .error-container {
        max-width: 600px;
        margin: 0 auto;
        text-align: center;
    }

    .error-icon {
        font-size: 6rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .error-code {
        font-size: 4rem;
        font-weight: bold;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        margin-bottom: 0.5rem;
    }

    .error-title {
        font-size: 2rem;
        color: white;
        margin-bottom: 1rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    .error-description {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.125rem;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .error-actions .btn {
        margin: 0.25rem;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: white;
        transform: translateY(-2px);
    }

    .btn-light:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .access-info {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .permission-list {
        text-align: left;
        max-width: 400px;
        margin: 0 auto;
    }

    .permission-list li {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0.5rem;
    }

    .permission-list i {
        color: rgba(255, 255, 255, 0.6);
        margin-right: 0.5rem;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="bi bi-shield-x"></i>
            </div>
            <div class="error-code">403</div>
            <h1 class="error-title">Akses Ditolak</h1>
            <p class="error-description">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
                Silakan login dengan akun yang memiliki hak akses yang sesuai.
            </p>

            <div class="error-actions">
                <a href="javascript:history.back()" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-arrow-left"></i>
                    Kembali
                </a>
                <a href="/modules/auth/login.php" class="btn btn-light btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Login
                </a>
            </div>

            <div class="access-info">
                <h6 class="text-white mb-3">
                    <i class="bi bi-info-circle"></i>
                    Informasi Hak Akses
                </h6>
                <div class="permission-list">
                    <ul class="list-unstyled">
                        <li><i class="bi bi-person-check"></i> Administrator: Akses penuh ke semua fitur</li>
                        <li><i class="bi bi-person"></i> Kepala Sekolah: Akses laporan dan hasil</li>
                        <li><i class="bi bi-shield-lock"></i> Guest: Tidak memiliki akses</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <small class="text-light">
                        Jika Anda merasa ini adalah kesalahan, hubungi administrator sistem.
                    </small>
                </div>
            </div>

            <div class="mt-4">
                <h6 class="text-white mb-2">
                    <i class="bi bi-building"></i>
                    SMP Negeri 2 Ampek Angkek
                </h6>
                <p class="text-light mb-0 small">
                    Sistem Pendukung Keputusan Beasiswa Prestasi
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Add animation effects
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll(
            '.error-icon, .error-code, .error-title, .error-description, .error-actions, .access-info');

        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';

            setTimeout(() => {
                el.style.transition = 'all 0.6s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 150);
        });
    });

    // Log access attempt for security monitoring
    console.warn('403 Forbidden access attempt logged');
    </script>
</body>

</html>