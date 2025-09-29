<?php

class Config {
    private static $config = null;
    
    private static function load() {
        if (self::$config === null) {
            self::$config = [];
            
            // Load .env file
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue; // Skip comments
                    }
                    
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^"(.*)"$/', $value, $matches)) {
                        $value = $matches[1];
                    } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                        $value = $matches[1];
                    }
                    
                    self::$config[$name] = $value;
                }
            }
        }
    }
    
    public static function get($key, $default = null) {
        self::load();
        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }
    
    public static function getDatabaseConfig() {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'port' => self::get('DB_PORT', 3306),
            'database' => self::get('DB_NAME', 'qrcode_db'),
            'username' => self::get('DB_USER', 'root'),
            'password' => self::get('DB_PASS', '')
        ];
    }
    
    /**
     * Get Google AdSense configuration
     * @return array AdSense configuration settings
     */
    public static function getAdSenseConfig() {
        return [
            'enabled' => filter_var(self::get('ADSENSE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
            'client_id' => self::get('ADSENSE_CLIENT_ID', ''),
            'banner_slot' => self::get('ADSENSE_BANNER_SLOT', ''),
            'rectangle_slot' => self::get('ADSENSE_RECTANGLE_SLOT', ''),
            'mobile_banner_slot' => self::get('ADSENSE_MOBILE_BANNER_SLOT', ''),
            'auto_ads' => filter_var(self::get('ADSENSE_AUTO_ADS', 'false'), FILTER_VALIDATE_BOOLEAN)
        ];
    }
    
    /**
     * Check if AdSense is enabled and properly configured
     * @return bool
     */
    public static function isAdSenseEnabled() {
        $config = self::getAdSenseConfig();
        return $config['enabled'] && !empty($config['client_id']);
    }
}