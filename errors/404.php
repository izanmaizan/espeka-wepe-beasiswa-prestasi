<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | SPK Beasiswa Prestasi</title>

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
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .error-container {
        max-width: 600px;
        margin: 0 auto;
        text-align: center;
    }

    .error-code {
        font-size: 8rem;
        font-weight: bold;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        margin-bottom: 0;
        line-height: 1;
    }

    .error-title {
        font-size: 2.5rem;
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

    .floating-elements {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }

    .floating-elements i {
        position: absolute;
        color: rgba(255, 255, 255, 0.1);
        animation: float 6s ease-in-out infinite;
    }

    .floating-elements i:nth-child(1) {
        top: 20%;
        left: 10%;
        font-size: 3rem;
        animation-delay: 0s;
    }

    .floating-elements i:nth-child(2) {
        top: 60%;
        left: 80%;
        font-size: 2rem;
        animation-delay: 2s;
    }

    .floating-elements i:nth-child(3) {
        top: 80%;
        left: 20%;
        font-size: 2.5rem;
        animation-delay: 4s;
    }

    .floating-elements i:nth-child(4) {
        top: 30%;
        left: 70%;
        font-size: 1.5rem;
        animation-delay: 1s;
    }

    .floating-elements i:nth-child(5) {
        top: 70%;
        left: 60%;
        font-size: 2rem;
        animation-delay: 3s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(10deg);
        }
    }

    .school-info {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    </style>
</head>

<body>
    <div class="floating-elements">
        <i class="bi bi-mortarboard"></i>
        <i class="bi bi-book"></i>
        <i class="bi bi-trophy"></i>
        <i class="bi bi-star"></i>
        <i class="bi bi-award"></i>
    </div>

    <div class="container">
        <div class="error-container">
            <div class="error-code">404</div>
            <h1 class="error-title">Halaman Tidak Ditemukan</h1>
            <p class="error-description">
                Maaf, halaman yang Anda cari tidak dapat ditemukan.
                Mungkin halaman telah dipindahkan, dihapus, atau URL yang Anda masukkan salah.
            </p>

            <div class="error-actions">
                <a href="javascript:history.back()" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-arrow-left"></i>
                    Kembali
                </a>
                <a href="/modules/dashboard/index.php" class="btn btn-light btn-lg">
                    <i class="bi bi-house"></i>
                    Ke Dashboard
                </a>
            </div>

            <div class="school-info">
                <h6 class="text-white mb-2">
                    <i class="bi bi-building"></i>
                    SMP Negeri 2 Ampek Angkek
                </h6>
                <p class="text-light mb-0 small">
                    Sistem Pendukung Keputusan Beasiswa Prestasi
                </p>
            </div>

            <div class="mt-4">
                <small class="text-light">
                    Jika masalah berlanjut, silakan hubungi administrator sistem.
                </small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Add some interactive effects
    document.addEventListener('DOMContentLoaded', function() {
        // Animate error code on load
        const errorCode = document.querySelector('.error-code');
        errorCode.style.opacity = '0';
        errorCode.style.transform = 'scale(0.5)';

        setTimeout(() => {
            errorCode.style.transition = 'all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
            errorCode.style.opacity = '1';
            errorCode.style.transform = 'scale(1)';
        }, 200);

        // Animate other elements
        const elements = document.querySelectorAll(
            '.error-title, .error-description, .error-actions, .school-info');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';

            setTimeout(() => {
                el.style.transition = 'all 0.6s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 300 + (index * 100));
        });
    });

    // Track 404 errors (optional - for analytics)
    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            'page_title': '404 Error',
            'page_location': window.location.href
        });
    }
    </script>
</body>

</html>