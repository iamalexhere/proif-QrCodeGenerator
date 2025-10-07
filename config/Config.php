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
    
    /**
     * Get the base URL for the application
     * @return string
     */
    public static function getBaseUrl() {
        return self::get('BASE_URL', 'http://localhost');
    }
    
    /**
     * Get the short domain configuration
     * @return string
     */
    public static function getShortDomain() {
        return self::get('SHORT_DOMAIN', 'localhost/r');
    }
    
    /**
     * Get the full short URL base (with http://)
     * @return string
     */
    public static function getShortUrlBase() {
        $shortDomain = self::getShortDomain();
        // If SHORT_DOMAIN doesn't include protocol, add it
        if (!preg_match('/^https?:\/\//', $shortDomain)) {
            return 'http://' . $shortDomain;
        }
        return $shortDomain;
    }
    
    /**
     * Get Google OAuth configuration
     * @return array Google OAuth settings
     */
    public static function getGoogleOAuthConfig() {
        return [
            'client_id' => self::get('GOOGLE_CLIENT_ID', ''),
            'client_secret' => self::get('GOOGLE_CLIENT_SECRET', ''),
            'redirect_uri' => self::get('GOOGLE_REDIRECT_URI', '')
        ];
    }
    
    /**
     * Get Google OAuth Client ID
     * @return string
     */
    public static function getGoogleClientId() {
        return self::get('GOOGLE_CLIENT_ID', '');
    }
    
    /**
     * Get Google OAuth Client Secret
     * @return string
     */
    public static function getGoogleClientSecret() {
        return self::get('GOOGLE_CLIENT_SECRET', '');
    }
    
    /**
     * Get Google OAuth Redirect URI
     * @return string
     */
    public static function getGoogleRedirectUri() {
        return self::get('GOOGLE_REDIRECT_URI', '');
    }
    
    /**
     * Check if Google OAuth is properly configured
     * @return bool
     */
    public static function isGoogleOAuthEnabled() {
        $config = self::getGoogleOAuthConfig();
        return !empty($config['client_id']) && 
               !empty($config['client_secret']) && 
               !empty($config['redirect_uri']);
    }
    
    /**
     * Get plan limits configuration
     * @return array Plan limits for each tier
     */
    public static function getPlanLimits() {
        return [
            'free' => [
                'qr_codes_per_month' => 10,
                'analytics_enabled' => false,
                'trial_days' => 7,
                'ads_enabled' => true,
                'custom_features' => true,
                'export_enabled' => false
            ],
            'starter' => [
                'qr_codes_per_month' => 200,
                'analytics_enabled' => true,
                'trial_days' => 0,
                'ads_enabled' => false,
                'custom_features' => true,
                'export_enabled' => false,
                'price' => 50000,
                'currency' => 'IDR'
            ],
            'pro' => [
                'qr_codes_per_month' => 500,
                'analytics_enabled' => true,
                'trial_days' => 0,
                'ads_enabled' => false,
                'custom_features' => true,
                'export_enabled' => true,
                'price' => 100000,
                'currency' => 'IDR'
            ]
        ];
    }
    
    /**
     * Get plan limit for specific plan and key
     * @param string $plan Plan name (free, starter, pro)
     * @param string $key Limit key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function getPlanLimit($plan, $key, $default = null) {
        $limits = self::getPlanLimits();
        return $limits[$plan][$key] ?? $default;
    }
}