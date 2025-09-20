<?php
/**
 * URL Shortener Class
 * Handles creation and management of short URLs
 */

require_once __DIR__ . '/../config/config.php';

class UrlShortener {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Create a short URL
     * 
     * @param string $originalUrl The original long URL
     * @param string $customCode Optional custom short code
     * @return array Result with short_code and short_url
     */
    public function createShortUrl($originalUrl, $customCode = null) {
        // Sanitize the URL
        $originalUrl = Utils::sanitizeUrl($originalUrl);
        if (!$originalUrl) {
            throw new Exception('Invalid URL provided');
        }
        
        // Check if URL already exists
        $stmt = $this->pdo->prepare("SELECT short_code FROM short_urls WHERE original_url = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$originalUrl]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return [
                'short_code' => $existing['short_code'],
                'short_url' => Config::getShortDomain() . $existing['short_code'],
                'original_url' => $originalUrl,
                'is_new' => false
            ];
        }
        
        // Generate or validate custom code
        if ($customCode) {
            if (!$this->isValidShortCode($customCode)) {
                throw new Exception('Invalid custom code. Use only letters, numbers, and hyphens (3-10 characters)');
            }
            
            if ($this->shortCodeExists($customCode)) {
                throw new Exception('Custom code already exists');
            }
            
            $shortCode = $customCode;
        } else {
            $shortCode = $this->generateUniqueShortCode();
        }
        
        // Store in database
        $ip = Utils::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $this->pdo->prepare("
            INSERT INTO short_urls (original_url, short_code, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$originalUrl, $shortCode, $ip, $userAgent]);
        
        return [
            'short_code' => $shortCode,
            'short_url' => Config::getShortDomain() . $shortCode,
            'original_url' => $originalUrl,
            'is_new' => true
        ];
    }
    
    /**
     * Get original URL from short code
     * 
     * @param string $shortCode
     * @return array|false URL info or false if not found
     */
    public function getOriginalUrl($shortCode) {
        $stmt = $this->pdo->prepare("
            SELECT id, original_url, clicks, created_at, expires_at 
            FROM short_urls 
            WHERE short_code = ? AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        
        $stmt->execute([$shortCode]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        return $result;
    }
    
    /**
     * Record a click and return the original URL
     * 
     * @param string $shortCode
     * @return string|false Original URL or false if not found
     */
    public function recordClick($shortCode) {
        $urlInfo = $this->getOriginalUrl($shortCode);
        
        if (!$urlInfo) {
            return false;
        }
        
        // Update click count
        $stmt = $this->pdo->prepare("UPDATE short_urls SET clicks = clicks + 1 WHERE id = ?");
        $stmt->execute([$urlInfo['id']]);
        
        // Record analytics
        $this->recordAnalytics($urlInfo['id']);
        
        return $urlInfo['original_url'];
    }
    
    /**
     * Record click analytics
     * 
     * @param int $shortUrlId
     */
    private function recordAnalytics($shortUrlId) {
        $ip = Utils::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Simple device detection
        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        } elseif (preg_match('/bot|crawler|spider/i', $userAgent)) {
            $deviceType = 'bot';
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO url_analytics (short_url_id, ip_address, user_agent, referer, device_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$shortUrlId, $ip, $userAgent, $referer, $deviceType]);
    }
    
    /**
     * Generate a unique short code
     * 
     * @param int $length
     * @return string
     */
    private function generateUniqueShortCode($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxAttempts = 10;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $shortCode = '';
            for ($i = 0; $i < $length; $i++) {
                $shortCode .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            if (!$this->shortCodeExists($shortCode)) {
                return $shortCode;
            }
            
            // Increase length if we're having collisions
            if ($attempt > 5) {
                $length++;
            }
        }
        
        throw new Exception('Unable to generate unique short code');
    }
    
    /**
     * Check if short code already exists
     * 
     * @param string $shortCode
     * @return bool
     */
    private function shortCodeExists($shortCode) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM short_urls WHERE short_code = ? LIMIT 1");
        $stmt->execute([$shortCode]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Validate short code format
     * 
     * @param string $shortCode
     * @return bool
     */
    private function isValidShortCode($shortCode) {
        return preg_match('/^[a-zA-Z0-9\-]{3,10}$/', $shortCode);
    }
    
    /**
     * Get analytics for a short URL
     * 
     * @param string $shortCode
     * @return array
     */
    public function getAnalytics($shortCode) {
        $stmt = $this->pdo->prepare("
            SELECT 
                s.original_url,
                s.short_code,
                s.clicks,
                s.created_at,
                COUNT(a.id) as total_analytics,
                COUNT(CASE WHEN a.device_type = 'mobile' THEN 1 END) as mobile_clicks,
                COUNT(CASE WHEN a.device_type = 'desktop' THEN 1 END) as desktop_clicks,
                COUNT(CASE WHEN a.device_type = 'tablet' THEN 1 END) as tablet_clicks
            FROM short_urls s
            LEFT JOIN url_analytics a ON s.id = a.short_url_id
            WHERE s.short_code = ?
            GROUP BY s.id
        ");
        
        $stmt->execute([$shortCode]);
        return $stmt->fetch();
    }
    
    /**
     * Get recent clicks
     * 
     * @param string $shortCode
     * @param int $limit
     * @return array
     */
    public function getRecentClicks($shortCode, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT a.clicked_at, a.ip_address, a.device_type, a.referer
            FROM url_analytics a
            JOIN short_urls s ON a.short_url_id = s.id
            WHERE s.short_code = ?
            ORDER BY a.clicked_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$shortCode, $limit]);
        return $stmt->fetchAll();
    }
}