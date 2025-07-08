<?php
// Check if user is admin (allow admin access during maintenance)
session_start();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// If admin, allow normal access
if ($isAdmin && isset($_GET['admin_access'])) {
    header('Location: modules/dashboard/index.php');
    exit();
}

// Load maintenance settings
$settingsFile = __DIR__ . '/config/settings.json';
$maintenanceMessage = 'Sistem sedang dalam pemeliharaan. Silakan coba lagi nanti.';

if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    if ($settings && isset($settings['app']['maintenance_message'])) {
        $maintenanceMessage = $settings['app']['maintenance_message'];
    }
}

http_response_code(503);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - SPK Beasiswa Prestasi</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <meta http-equiv="refresh" content="300"> <!-- Auto refresh every 5 minutes -->

    <style>
    body {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .maintenance-container {
        max-width: 600px;
        margin: 0 auto;
        text-align: center;
    }

    .maintenance-icon {
        font-size: 6rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        animation: rotate 3s linear infinite;
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .maintenance-title {
        font-size: 2.5rem;
        color: white;
        margin-bottom: 1rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        font-weight: 700;
    }

    .maintenance-description {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.125rem;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .status-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .progress-bar-custom {
        background: rgba(255, 255, 255, 0.3);
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        margin: 1rem 0;
    }

    .progress-fill {
        background: white;
        height: 100%;
        border-radius: 4px;
        width: 0%;
        animation: progress 4s ease-in-out infinite;
    }

    @keyframes progress {
        0% {
            width: 0%;
        }

        50% {
            width: 70%;
        }

        100% {
            width: 0%;
        }
    }

    .maintenance-info {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .countdown {
        font-size: 1.2rem;
        font-weight: 600;
        color: white;
        margin: 1rem 0;
    }

    .admin-access {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.875rem;
        border: none;
        transition: all 0.3s ease;
    }

    .admin-access:hover {
        background: rgba(0, 0, 0, 0.9);
        color: white;
        transform: translateY(-2px);
    }

    .floating-icons {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }

    .floating-icons i {
        position: absolute;
        color: rgba(255, 255, 255, 0.1);
        animation: float 8s ease-in-out infinite;
    }

    .floating-icons i:nth-child(1) {
        top: 15%;
        left: 10%;
        font-size: 2rem;
        animation-delay: 0s;
    }

    .floating-icons i:nth-child(2) {
        top: 70%;
        left: 85%;
        font-size: 1.5rem;
        animation-delay: 2s;
    }

    .floating-icons i:nth-child(3) {
        top: 50%;
        left: 5%;
        font-size: 2.5rem;
        animation-delay: 4s;
    }

    .floating-icons i:nth-child(4) {
        top: 25%;
        left: 80%;
        font-size: 1.8rem;
        animation-delay: 1s;
    }

    .floating-icons i:nth-child(5) {
        top: 80%;
        left: 15%;
        font-size: 2.2rem;
        animation-delay: 3s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }

        50% {
            transform: translateY(-30px) rotate(15deg);
            opacity: 0.1;
        }
    }
    </style>
</head>

<body>
    <div class="floating-icons">
        <i class="bi bi-gear"></i>
        <i class="bi bi-tools"></i>
        <i class="bi bi-wrench"></i>
        <i class="bi bi-hammer"></i>
        <i class="bi bi-screwdriver"></i>
    </div>

    <div class="container">
        <div class="maintenance-container">
            <div class="maintenance-icon">
                <i class="bi bi-gear-fill"></i>
            </div>

            <h1 class="maintenance-title">Sistem Dalam Pemeliharaan</h1>

            <div class="status-card">
                <p class="maintenance-description mb-3">
                    <?php echo htmlspecialchars($maintenanceMessage); ?>
                </p>

                <div class="progress-bar-custom">
                    <div class="progress-fill"></div>
                </div>

                <div class="countdown">
                    <i class="bi bi-clock"></i>
                    Halaman akan dimuat ulang otomatis
                </div>
            </div>

            <div class="maintenance-info">
                <h6 class="text-white mb-3">
                    <i class="bi bi-info-circle"></i>
                    Yang Sedang Kami Lakukan
                </h6>

                <div class="row text-start">
                    <div class="col-md-6">
                        <ul class="list-unstyled text-light">
                            <li class="mb-2">
                                <i class="bi bi-check2 text-success"></i>
                                Pemeliharaan database
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check2 text-success"></i>
                                Update keamanan sistem
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled text-light">
                            <li class="mb-2">
                                <i class="bi bi-arrow-clockwise text-warning"></i>
                                Optimasi performa
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-arrow-clockwise text-warning"></i>
                                Backup data
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="text-white">
                        <i class="bi bi-building"></i>
                        SMP Negeri 2 Ampek Angkek
                    </h6>
                    <p class="text-light mb-0 small">
                        Sistem Pendukung Keputusan Beasiswa Prestasi
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <small class="text-light">
                    Terima kasih atas kesabaran Anda. Sistem akan segera kembali normal.
                </small>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['username'])): ?>
    <a href="?admin_access=1" class="admin-access">
        <i class="bi bi-key"></i>
        Admin Access
    </a>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Add loading animation
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll(
            '.maintenance-icon, .maintenance-title, .status-card, .maintenance-info');

        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';

            setTimeout(() => {
                el.style.transition = 'all 0.8s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 200);
        });
    });

    // Countdown timer for auto refresh
    let countdown = 300; // 5 minutes
    function updateCountdown() {
        const minutes = Math.floor(countdown / 60);
        const seconds = countdown % 60;
        const countdownEl = document.querySelector('.countdown');

        if (countdownEl) {
            countdownEl.innerHTML = `
                    <i class="bi bi-clock"></i>
                    Refresh otomatis dalam ${minutes}:${seconds.toString().padStart(2, '0')}
                `;
        }

        if (countdown > 0) {
            countdown--;
            setTimeout(updateCountdown, 1000);
        } else {
            location.reload();
        }
    }

    updateCountdown();
    </script>
</body>

</html>