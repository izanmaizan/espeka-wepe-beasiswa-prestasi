<?php
// Load app configuration first if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/app.php';
}

// Database configuration constants should already be defined in app.php
// If not defined there, define them here as fallback
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'spk_beasiswa');
}

// Membuat koneksi database
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // If we're in installer, don't die - just return null
        if (defined('SKIP_INSTALLATION_CHECK')) {
            return null;
        }
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Inisialisasi koneksi global hanya jika bukan di installer
if (!defined('SKIP_INSTALLATION_CHECK')) {
    $pdo = getDBConnection();
} else {
    $pdo = null; // Will be initialized later in installer
}
?>