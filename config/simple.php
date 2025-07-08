<?php
/**
 * Konfigurasi Sederhana SPK Beasiswa
 * Untuk mengatasi konflik konstanta
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'spk_beasiswa');

// Application Settings
define('APP_NAME', 'SPK Beasiswa Prestasi');
define('APP_URL', 'http://localhost/spk-beasiswa-prestasi');

// School Information
define('SCHOOL_NAME', 'SMP Negeri 2 Ampek Angkek');
define('SCHOOL_ADDRESS', 'Jl. Pendidikan No. 123, Ampek Angkek');
define('SCHOOL_PHONE', '(0751) 123456');
define('SCHOOL_EMAIL', 'info@smpn2ampekangkek.sch.id');

// Create database connection
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
        die("Koneksi database gagal: " . $e->getMessage() . "<br><br>
             <strong>Solusi:</strong><br>
             1. Pastikan MySQL sedang berjalan di Laragon<br>
             2. Buat database 'spk_beasiswa' di phpMyAdmin<br>
             3. Import file database.sql<br>
             4. Refresh halaman ini");
    }
}

// Initialize global connection
$pdo = getDBConnection();

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Helper functions
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>