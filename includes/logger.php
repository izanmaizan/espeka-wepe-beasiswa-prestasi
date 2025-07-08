<?php
/**
 * Simple Logger Class for SPK Beasiswa
 * SMP Negeri 2 Ampek Angkek
 */

class Logger {
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    
    private static $instance = null;
    private $logFile;
    private $logLevel;
    private $maxFileSize;
    
    private function __construct() {
        $this->logFile = defined('LOG_FILE') ? LOG_FILE : __DIR__ . '/../logs/app.log';
        $this->logLevel = $this->getLogLevelFromString(defined('LOG_LEVEL') ? LOG_LEVEL : 'info');
        $this->maxFileSize = defined('LOG_MAX_SIZE') ? LOG_MAX_SIZE : 10485760; // 10MB
        
        // Create log directory if not exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function getLogLevelFromString($level) {
        switch (strtolower($level)) {
            case 'debug': return self::LEVEL_DEBUG;
            case 'info': return self::LEVEL_INFO;
            case 'warning': return self::LEVEL_WARNING;
            case 'error': return self::LEVEL_ERROR;
            case 'critical': return self::LEVEL_CRITICAL;
            default: return self::LEVEL_INFO;
        }
    }
    
    private function getLevelString($level) {
        switch ($level) {
            case self::LEVEL_DEBUG: return 'DEBUG';
            case self::LEVEL_INFO: return 'INFO';
            case self::LEVEL_WARNING: return 'WARNING';
            case self::LEVEL_ERROR: return 'ERROR';
            case self::LEVEL_CRITICAL: return 'CRITICAL';
            default: return 'UNKNOWN';
        }
    }
    
    private function shouldLog($level) {
        return $level >= $this->logLevel;
    }
    
    private function rotateLogFile() {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $backupFile = $this->logFile . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($this->logFile, $backupFile);
            
            // Keep only last 5 backup files
            $logDir = dirname($this->logFile);
            $backupFiles = glob($logDir . '/*.bak');
            if (count($backupFiles) > 5) {
                usort($backupFiles, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                for ($i = 0; $i < count($backupFiles) - 5; $i++) {
                    unlink($backupFiles[$i]);
                }
            }
        }
    }
    
    private function log($level, $message, $context = []) {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $this->rotateLogFile();
        
        $timestamp = date('Y-m-d H:i:s');
        $levelString = $this->getLevelString($level);
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'guest';
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Format context as JSON if provided
        $contextString = '';
        if (!empty($context)) {
            $contextString = ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry = sprintf(
            "[%s] %s: %s | User: %s (%s) | IP: %s | URI: %s%s\n",
            $timestamp,
            $levelString,
            $message,
            $username,
            $userId,
            $ip,
            $requestUri,
            $contextString
        );
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function debug($message, $context = []) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    // Specific methods for common actions
    public function logLogin($username, $success = true) {
        if ($success) {
            $this->info("User login successful", ['username' => $username]);
        } else {
            $this->warning("User login failed", ['username' => $username]);
        }
    }
    
    public function logLogout($username) {
        $this->info("User logout", ['username' => $username]);
    }
    
    public function logDatabaseQuery($query, $params = [], $executionTime = null) {
        $context = ['query' => $query, 'params' => $params];
        if ($executionTime !== null) {
            $context['execution_time'] = $executionTime . 'ms';
        }
        $this->debug("Database query executed", $context);
    }
    
    public function logDataAccess($action, $table, $recordId = null) {
        $context = ['action' => $action, 'table' => $table];
        if ($recordId) {
            $context['record_id'] = $recordId;
        }
        $this->info("Data access", $context);
    }
    
    public function logCalculation($type, $parameters = []) {
        $this->info("Calculation performed", ['type' => $type, 'parameters' => $parameters]);
    }
    
    public function logFileUpload($filename, $size, $success = true) {
        if ($success) {
            $this->info("File uploaded", ['filename' => $filename, 'size' => $size]);
        } else {
            $this->error("File upload failed", ['filename' => $filename, 'size' => $size]);
        }
    }
    
    public function logSecurityEvent($event, $details = []) {
        $this->warning("Security event", array_merge(['event' => $event], $details));
    }
    
    public function logSystemError($error, $file = null, $line = null) {
        $context = ['error' => $error];
        if ($file) $context['file'] = $file;
        if ($line) $context['line'] = $line;
        $this->error("System error", $context);
    }
    
    // Method to get recent logs for admin view
    public function getRecentLogs($limit = 100, $level = null) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $lines = array_reverse($lines); // Show newest first
        
        if ($level !== null) {
            $levelString = $this->getLevelString($level);
            $lines = array_filter($lines, function($line) use ($levelString) {
                return strpos($line, $levelString . ':') !== false;
            });
        }
        
        return array_slice($lines, 0, $limit);
    }
    
    // Method to clear old logs
    public function clearOldLogs($days = 30) {
        $logDir = dirname($this->logFile);
        $files = glob($logDir . '/*.log*');
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
    
    // Method to get log statistics
    public function getLogStats() {
        if (!file_exists($this->logFile)) {
            return [
                'total_lines' => 0,
                'file_size' => 0,
                'last_modified' => null,
                'levels' => []
            ];
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $levels = [];
        
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] (.*?):/', $line, $matches)) {
                $level = $matches[2];
                $levels[$level] = ($levels[$level] ?? 0) + 1;
            }
        }
        
        return [
            'total_lines' => count($lines),
            'file_size' => filesize($this->logFile),
            'last_modified' => filemtime($this->logFile),
            'levels' => $levels
        ];
    }
}

// Global logging functions
function logger() {
    return Logger::getInstance();
}

function logInfo($message, $context = []) {
    Logger::getInstance()->info($message, $context);
}

function logError($message, $context = []) {
    Logger::getInstance()->error($message, $context);
}

function logWarning($message, $context = []) {
    Logger::getInstance()->warning($message, $context);
}

function logDebug($message, $context = []) {
    Logger::getInstance()->debug($message, $context);
}

function logCritical($message, $context = []) {
    Logger::getInstance()->critical($message, $context);
}

// Set up error handler to log PHP errors
function errorHandler($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER DEPRECATED'
    ];
    
    $errorType = $errorTypes[$errno] ?? 'UNKNOWN ERROR';
    $message = "$errorType: $errstr";
    
    Logger::getInstance()->logSystemError($message, $errfile, $errline);
    
    // Don't execute PHP internal error handler
    return true;
}

// Set up exception handler
function exceptionHandler($exception) {
    $message = "Uncaught exception: " . $exception->getMessage();
    Logger::getInstance()->logSystemError(
        $message, 
        $exception->getFile(), 
        $exception->getLine()
    );
}

// Register error and exception handlers
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
        Logger::getInstance()->logSystemError(
            "Fatal error: " . $error['message'],
            $error['file'],
            $error['line']
        );
    }
});
?>