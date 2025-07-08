<?php
/**
 * Application Configuration
 * SPK Beasiswa Prestasi - SMP Negeri 2 Ampek Angkek
 */

// Load environment variables from .env file
function loadEnv($file) {
    if (!file_exists($file)) {
        return;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"\'');
        
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Helper function to get environment variable
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Application Configuration
define('APP_NAME', env('APP_NAME', 'SPK Beasiswa Prestasi'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_NAME', env('DB_NAME', 'spk_beasiswa'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Session Configuration
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200));
define('SESSION_NAME', env('SESSION_NAME', 'spk_session'));
define('SESSION_SECURE', filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN));
define('SESSION_HTTPONLY', filter_var(env('SESSION_HTTPONLY', true), FILTER_VALIDATE_BOOLEAN));

// Security Configuration
define('ENCRYPTION_KEY', env('ENCRYPTION_KEY', 'default-key-change-in-production'));
define('CSRF_TOKEN_LIFETIME', (int)env('CSRF_TOKEN_LIFETIME', 3600));

// File Upload Configuration
define('MAX_UPLOAD_SIZE', (int)env('MAX_UPLOAD_SIZE', 10485760)); // 10MB
define('ALLOWED_EXTENSIONS', env('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,pdf,doc,docx'));

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', (int)env('DEFAULT_PAGE_SIZE', 10));
define('MAX_PAGE_SIZE', (int)env('MAX_PAGE_SIZE', 100));

// Logging Configuration
define('LOG_LEVEL', env('LOG_LEVEL', 'info'));
define('LOG_FILE', env('LOG_FILE', __DIR__ . '/../logs/app.log'));
define('LOG_MAX_SIZE', (int)env('LOG_MAX_SIZE', 10485760));

// Backup Configuration
define('BACKUP_PATH', env('BACKUP_PATH', __DIR__ . '/../backups/'));
define('BACKUP_RETENTION_DAYS', (int)env('BACKUP_RETENTION_DAYS', 30));

// School Information
define('SCHOOL_NAME', env('SCHOOL_NAME', 'SMP Negeri 2 Ampek Angkek'));
define('SCHOOL_ADDRESS', env('SCHOOL_ADDRESS', 'Jl. Pendidikan No. 123, Ampek Angkek'));
define('SCHOOL_PHONE', env('SCHOOL_PHONE', '(0751) 123456'));
define('SCHOOL_EMAIL', env('SCHOOL_EMAIL', 'info@smpn2ampekangkek.sch.id'));

// Localization Configuration
define('TIMEZONE', env('TIMEZONE', 'Asia/Jakarta'));
define('DATE_FORMAT', env('DATE_FORMAT', 'd/m/Y'));
define('DATETIME_FORMAT', env('DATETIME_FORMAT', 'd/m/Y H:i'));
define('CURRENCY', env('CURRENCY', 'IDR'));
define('LOCALE', env('LOCALE', 'id_ID'));

// Set timezone
date_default_timezone_set(TIMEZONE);

// Set locale
if (function_exists('setlocale')) {
    setlocale(LC_ALL, LOCALE);
}

// Error reporting based on environment
if (APP_ENV === 'development' && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Session configuration
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_httponly', SESSION_HTTPONLY);
ini_set('session.cookie_secure', SESSION_SECURE);
ini_set('session.use_strict_mode', 1);

// Memory and execution limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// File upload limits
ini_set('upload_max_filesize', MAX_UPLOAD_SIZE);
ini_set('post_max_size', MAX_UPLOAD_SIZE * 1.2);

/**
 * Application Helper Functions
 */

/**
 * Get application configuration value
 */
function config($key, $default = null) {
    $config = [
        'app' => [
            'name' => APP_NAME,
            'url' => APP_URL,
            'env' => APP_ENV,
            'debug' => APP_DEBUG
        ],
        'database' => [
            'host' => DB_HOST,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'name' => DB_NAME,
            'charset' => DB_CHARSET
        ],
        'session' => [
            'lifetime' => SESSION_LIFETIME,
            'name' => SESSION_NAME,
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY
        ],
        'school' => [
            'name' => SCHOOL_NAME,
            'address' => SCHOOL_ADDRESS,
            'phone' => SCHOOL_PHONE,
            'email' => SCHOOL_EMAIL
        ]
    ];
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }
    
    return $value;
}

/**
 * Check if application is in debug mode
 */
function isDebug() {
    return APP_DEBUG;
}

/**
 * Check if application is in production environment
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Get application URL
 */
function appUrl($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset($path) {
    return appUrl('assets/' . ltrim($path, '/'));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token) &&
           isset($_SESSION['csrf_token_time']) &&
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_LIFETIME;
}

/**
 * Create directories if not exists
 */
function createDirectories() {
    $directories = [
        dirname(LOG_FILE),
        BACKUP_PATH,
        __DIR__ . '/../uploads',
        __DIR__ . '/../cache'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Create necessary directories
createDirectories();

/**
 * Application Constants for Weighted Product
 */
define('WP_MIN_CRITERIA', 3);
define('WP_MAX_CRITERIA', 10);
define('WP_PRECISION', 6);
define('WP_WEIGHT_TOLERANCE', 0.0001);

/**
 * Status Constants
 */
define('STATUS_ACTIVE', 'aktif');
define('STATUS_INACTIVE', 'nonaktif');

/**
 * User Roles
 */
define('ROLE_ADMIN', 'admin');
define('ROLE_KEPALA_SEKOLAH', 'kepala_sekolah');

/**
 * Criteria Types
 */
define('CRITERIA_BENEFIT', 'benefit');
define('CRITERIA_COST', 'cost');

/**
 * File Type Validation
 */
function getAllowedExtensions() {
    return explode(',', ALLOWED_EXTENSIONS);
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get maximum upload size in readable format
 */
function getMaxUploadSize() {
    return formatFileSize(MAX_UPLOAD_SIZE);
}

/**
 * Application metadata
 */
function getAppInfo() {
    return [
        'name' => APP_NAME,
        'version' => '1.0.0',
        'author' => 'SMP Negeri 2 Ampek Angkek',
        'description' => 'Sistem Pendukung Keputusan Beasiswa Prestasi menggunakan metode Weighted Product',
        'php_version' => PHP_VERSION,
        'created_at' => '2024',
        'license' => 'Educational Use'
    ];
}
?>