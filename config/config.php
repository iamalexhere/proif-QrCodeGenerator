<?php
/**
 * Configuration Manager for QR Generator with URL Shortener
 * Handles environment-based configuration
 */

class Config {
    private static $config = null;
    
    /**
     * Load configuration from .env file
     */
    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new Exception('.env file not found. Please copy .env.example to .env and configure it.');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
        
        self::$config = $config;
        return $config;
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        $config = self::load();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Check if we're in development environment
     */
    public static function isDev() {
        return self::get('APP_ENV') === 'development';
    }
    
    /**
     * Check if we're in production environment
     */
    public static function isProd() {
        return self::get('APP_ENV') === 'production';
    }
    
    /**
     * Get database configuration
     */
    public static function getDbConfig() {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'port' => self::get('DB_PORT', '3306'),
            'dbname' => self::get('DB_NAME', 'qr_generator'),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASS'),
            'charset' => 'utf8mb4'
        ];
    }
    
    /**
     * Get base URL for the application
     */
    public static function getBaseUrl() {
        $baseUrl = self::get('BASE_URL', 'http://localhost:8000');
        return rtrim($baseUrl, '/') . '/';
    }
    
    /**
     * Get short domain URL
     */
    public static function getShortDomain() {
        if (self::isDev()) {
            return self::getBaseUrl();
        }
        
        $shortDomain = self::get('SHORT_DOMAIN');
        if (empty($shortDomain)) {
            return self::getBaseUrl();
        }
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $shortDomain)) {
            $protocol = self::isProd() ? 'https://' : 'http://';
            $shortDomain = $protocol . $shortDomain;
        }
        
        return rtrim($shortDomain, '/') . '/';
    }
}

/**
 * Database Connection Manager
 */
class Database {
    private static $pdo = null;
    
    /**
     * Get PDO database connection
     */
    public static function getConnection() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        
        $config = Config::getDbConfig();
        
        if (empty($config['username']) || empty($config['password'])) {
            throw new Exception('Database credentials not configured. Please check your .env file.');
        }
        
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        
        try {
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }
}

/**
 * Simple Rate Limiter
 */
class RateLimit {
    private static $pdo = null;
    
    public static function check($ip, $limit = null) {
        if ($limit === null) {
            $limit = (int) Config::get('RATE_LIMIT', 100);
        }
        
        self::$pdo = Database::getConnection();
        
        // Clean old entries (older than 1 hour)
        $stmt = self::$pdo->prepare("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        
        // Check current rate
        $stmt = self::$pdo->prepare("SELECT request_count FROM rate_limits WHERE ip_address = ? AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        
        if ($result) {
            if ($result['request_count'] >= $limit) {
                return false; // Rate limit exceeded
            }
            
            // Update counter
            $stmt = self::$pdo->prepare("UPDATE rate_limits SET request_count = request_count + 1, last_request = NOW() WHERE ip_address = ?");
            $stmt->execute([$ip]);
        } else {
            // Create new entry
            $stmt = self::$pdo->prepare("INSERT INTO rate_limits (ip_address, request_count, window_start) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE request_count = 1, window_start = NOW()");
            $stmt->execute([$ip]);
        }
        
        return true;
    }
}

/**
 * Utility Functions
 */
class Utils {
    /**
     * Get client IP address
     */
    public static function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (forwarded)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitizeUrl($url) {
        $url = trim($url);
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        return $url;
    }
    
    /**
     * Log error message
     */
    public static function logError($message) {
        $logPath = Config::get('LOG_PATH', __DIR__ . '/../logs/');
        
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        $logFile = $logPath . 'app.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}