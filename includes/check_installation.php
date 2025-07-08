<?php
/**
 * Check if system is properly installed
 * Redirect to installer if not
 */

function checkInstallation() {
    // Skip check if we're already in installer
    if (strpos($_SERVER['REQUEST_URI'], '/install/') !== false) {
        return true;
    }
    
    // Skip check for static assets
    if (strpos($_SERVER['REQUEST_URI'], '/assets/') !== false) {
        return true;
    }
    
    try {
        // Check if database connection works
        $testPdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Check if users table exists and has data
        $stmt = $testPdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        
        if ($userCount == 0) {
            throw new Exception("No users found");
        }
        
        return true;
        
    } catch (Exception $e) {
        // Redirect to installer
        $installUrl = '/spk-beasiswa-prestasi/install/setup.php';
        
        // Adjust URL based on current path
        $currentPath = $_SERVER['REQUEST_URI'];
        if (strpos($currentPath, '/modules/') !== false) {
            $installUrl = '../../install/setup.php';
        } elseif (strpos($currentPath, '/spk-beasiswa-prestasi/') !== false) {
            $installUrl = 'install/setup.php';
        }
        
        header("Location: $installUrl");
        exit();
    }
}

// Run the check (but only if we're not already in installer)
if (!defined('SKIP_INSTALLATION_CHECK')) {
    checkInstallation();
}
?>