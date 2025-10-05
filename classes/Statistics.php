<?php

/**
 * Class Statistics
 * 
 * Mengelola tracking dan analytics untuk QR code dan URL shortener.
 * Mencatat setiap klik/scan dengan informasi detail untuk analisis mendalam.
 * 
 * Fitur:
 * - Tracking klik dengan device detection
 * - Geolocation tracking
 * - Bot detection
 * - Analytics aggregation
 * - Statistics retrieval untuk dashboard
 */

require_once __DIR__ . '/Database.php';

class Statistics {
    /** @var mysqli Koneksi database */
    private $db;
    
    /**
     * Constructor - Inisialisasi koneksi database
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Mencatat klik/scan QR code dengan informasi lengkap
     * 
     * @param int $linkId ID link yang diklik
     * @param array $additionalData Data tambahan (opsional)
     * @return bool True jika berhasil
     */
    public function recordClick($linkId, $additionalData = []) {
        try {
            // Ambil informasi dari request
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            
            // Parse device information
            $deviceInfo = $this->parseUserAgent($userAgent);
            
            // Get geolocation (dapat diintegrasikan dengan API seperti ipapi.co)
            $locationInfo = $this->getLocationInfo($ipAddress);
            
            // Detect bot
            $isBot = $this->isBot($userAgent);
            
            // Get temporal information
            $now = new DateTime();
            $hourOfDay = (int)$now->format('G');
            $dayOfWeek = (int)$now->format('N');
            $weekOfYear = (int)$now->format('W');
            
            // Parse referrer domain
            $referrerDomain = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;
            
            // Insert click record
            $sql = "INSERT INTO clicks (
                link_id, ip_address, user_agent,
                device_type, device_os, device_brand, browser,
                country, country_code, city, region, latitude, longitude, timezone,
                referrer, referrer_domain,
                hour_of_day, day_of_week, week_of_year,
                is_bot, language
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "issssssssssddsssiiiis",
                $linkId,
                $ipAddress,
                $userAgent,
                $deviceInfo['type'],
                $deviceInfo['os'],
                $deviceInfo['brand'],
                $deviceInfo['browser'],
                $locationInfo['country'],
                $locationInfo['country_code'],
                $locationInfo['city'],
                $locationInfo['region'],
                $locationInfo['latitude'],
                $locationInfo['longitude'],
                $locationInfo['timezone'],
                $referrer,
                $referrerDomain,
                $hourOfDay,
                $dayOfWeek,
                $weekOfYear,
                $isBot,
                $locationInfo['language']
            );
            
            $success = $stmt->execute();
            $stmt->close();
            
            // Update analytics summary asynchronously (bisa dijadikan background job)
            if ($success) {
                $this->updateAnalyticsSummary($linkId);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Statistics recording error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mendapatkan statistik lengkap untuk sebuah link
     * 
     * @param int $linkId ID link
     * @return array Statistik lengkap
     */
    public function getLinkStatistics($linkId) {
        $stats = [
            'summary' => $this->getSummaryStats($linkId),
            'devices' => $this->getDeviceStats($linkId),
            'locations' => $this->getLocationStats($linkId),
            'temporal' => $this->getTemporalStats($linkId),
            'referrers' => $this->getReferrerStats($linkId)
        ];
        
        return $stats;
    }
    
    /**
     * Mendapatkan ringkasan statistik
     */
    private function getSummaryStats($linkId) {
        // Try to get from summary table first
        $sql = "SELECT 
            total_clicks,
            unique_clicks,
            clicks_today,
            clicks_this_week,
            clicks_this_month,
            mobile_clicks,
            tablet_clicks,
            desktop_clicks,
            last_click_at,
            first_click_at
        FROM link_analytics_summary
        WHERE link_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        // If no summary exists, calculate directly from clicks table
        if (!$data) {
            $sql = "SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT ip_address) as unique_clicks,
                SUM(CASE WHEN DATE(clicked_at) = CURDATE() THEN 1 ELSE 0 END) as clicks_today,
                SUM(CASE WHEN YEARWEEK(clicked_at) = YEARWEEK(NOW()) THEN 1 ELSE 0 END) as clicks_this_week,
                SUM(CASE WHEN YEAR(clicked_at) = YEAR(NOW()) AND MONTH(clicked_at) = MONTH(NOW()) THEN 1 ELSE 0 END) as clicks_this_month,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile_clicks,
                SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet_clicks,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop_clicks,
                MAX(clicked_at) as last_click_at,
                MIN(clicked_at) as first_click_at
            FROM clicks
            WHERE link_id = ? AND is_bot = FALSE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $linkId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
        }
        
        // Calculate percentages
        if ($data && $data['total_clicks'] > 0) {
            $total = $data['total_clicks'];
            $data['mobile_percentage'] = round(($data['mobile_clicks'] / $total) * 100, 1);
            $data['tablet_percentage'] = round(($data['tablet_clicks'] / $total) * 100, 1);
            $data['desktop_percentage'] = round(($data['desktop_clicks'] / $total) * 100, 1);
        }
        
        return $data ?: [
            'total_clicks' => 0,
            'unique_clicks' => 0,
            'clicks_today' => 0,
            'clicks_this_week' => 0,
            'clicks_this_month' => 0,
            'mobile_clicks' => 0,
            'tablet_clicks' => 0,
            'desktop_clicks' => 0,
            'mobile_percentage' => 0,
            'tablet_percentage' => 0,
            'desktop_percentage' => 0
        ];
    }
    
    /**
     * Mendapatkan statistik per device/OS
     */
    private function getDeviceStats($linkId) {
        $sql = "SELECT 
            device_os,
            COUNT(*) as count
        FROM clicks
        WHERE link_id = ? AND is_bot = FALSE AND device_os IS NOT NULL
        GROUP BY device_os
        ORDER BY count DESC
        LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $devices = [];
        while ($row = $result->fetch_assoc()) {
            $devices[] = $row;
        }
        $stmt->close();
        
        return $devices;
    }
    
    /**
     * Mendapatkan statistik lokasi (negara dan kota)
     */
    private function getLocationStats($linkId) {
        // Countries
        $sqlCountries = "SELECT 
            country,
            COUNT(*) as count
        FROM clicks
        WHERE link_id = ? AND is_bot = FALSE AND country IS NOT NULL
        GROUP BY country
        ORDER BY count DESC
        LIMIT 10";
        
        $stmt = $this->db->prepare($sqlCountries);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $countries = [];
        while ($row = $result->fetch_assoc()) {
            $countries[] = $row;
        }
        $stmt->close();
        
        // Cities
        $sqlCities = "SELECT 
            city,
            country,
            COUNT(*) as count
        FROM clicks
        WHERE link_id = ? AND is_bot = FALSE AND city IS NOT NULL
        GROUP BY city, country
        ORDER BY count DESC
        LIMIT 10";
        
        $stmt = $this->db->prepare($sqlCities);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cities = [];
        while ($row = $result->fetch_assoc()) {
            $cities[] = $row;
        }
        $stmt->close();
        
        return [
            'countries' => $countries,
            'cities' => $cities
        ];
    }
    
    /**
     * Mendapatkan statistik temporal (per minggu, per hari, per jam)
     */
    private function getTemporalStats($linkId) {
        // Clicks per week (last 4 weeks)
        $sqlWeeks = "SELECT 
            week_of_year,
            COUNT(*) as count
        FROM clicks
        WHERE link_id = ? 
            AND is_bot = FALSE 
            AND clicked_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
        GROUP BY week_of_year
        ORDER BY week_of_year ASC";
        
        $stmt = $this->db->prepare($sqlWeeks);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $weeks = [];
        while ($row = $result->fetch_assoc()) {
            $weeks[] = $row;
        }
        $stmt->close();
        
        // Clicks per day of week
        $sqlDays = "SELECT 
            day_of_week,
            COUNT(*) as count
        FROM clicks
        WHERE link_id = ? AND is_bot = FALSE
        GROUP BY day_of_week
        ORDER BY day_of_week ASC";
        
        $stmt = $this->db->prepare($sqlDays);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $days = [];
        while ($row = $result->fetch_assoc()) {
            $days[] = $row;
        }
        $stmt->close();
        
        return [
            'weeks' => $weeks,
            'days' => $days
        ];
    }
    
    /**
     * Mendapatkan statistik referrer
     */
    private function getReferrerStats($linkId) {
        $sql = "SELECT 
            referrer_domain,
            COUNT(*) as count
        FROM clicks
        WHERE link_id = ? 
            AND is_bot = FALSE 
            AND referrer_domain IS NOT NULL
        GROUP BY referrer_domain
        ORDER BY count DESC
        LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $referrers = [];
        while ($row = $result->fetch_assoc()) {
            $referrers[] = $row;
        }
        $stmt->close();
        
        return $referrers;
    }
    
    /**
     * Update analytics summary untuk link tertentu
     */
    private function updateAnalyticsSummary($linkId) {
        try {
            $sql = "CALL update_link_analytics(?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $linkId);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Analytics summary update error: " . $e->getMessage());
        }
    }
    
    /**
     * Mendapatkan IP address client yang sebenarnya
     */
    private function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',   // Proxy
            'HTTP_X_REAL_IP',         // Nginx
            'REMOTE_ADDR'             // Default
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Jika ada multiple IPs (proxy chain), ambil yang pertama
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validasi IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Parse user agent untuk mendapatkan informasi device
     */
    private function parseUserAgent($userAgent) {
        $info = [
            'type' => 'other',
            'os' => null,
            'brand' => null,
            'browser' => null
        ];
        
        if (empty($userAgent)) {
            return $info;
        }
        
        $ua = strtolower($userAgent);
        
        // Detect device type
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            $info['type'] = 'tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            $info['type'] = 'mobile';
        } elseif (preg_match('/(iphone|ipod)/i', $userAgent)) {
            $info['type'] = 'mobile';
        } else {
            $info['type'] = 'desktop';
        }
        
        // Detect OS
        if (preg_match('/windows nt/i', $ua)) {
            $info['os'] = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $info['os'] = 'macOS';
        } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $info['os'] = 'iOS';
        } elseif (preg_match('/android/i', $ua)) {
            $info['os'] = 'Android';
        } elseif (preg_match('/linux/i', $ua)) {
            $info['os'] = 'Linux';
        }
        
        // Detect brand (for mobile devices)
        if (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $info['brand'] = 'Apple';
        } elseif (preg_match('/samsung/i', $ua)) {
            $info['brand'] = 'Samsung';
        } elseif (preg_match('/huawei/i', $ua)) {
            $info['brand'] = 'Huawei';
        } elseif (preg_match('/xiaomi/i', $ua)) {
            $info['brand'] = 'Xiaomi';
        } elseif (preg_match('/oppo/i', $ua)) {
            $info['brand'] = 'Oppo';
        } elseif (preg_match('/vivo/i', $ua)) {
            $info['brand'] = 'Vivo';
        }
        
        // Detect browser
        if (preg_match('/edg/i', $ua)) {
            $info['browser'] = 'Edge';
        } elseif (preg_match('/chrome/i', $ua)) {
            $info['browser'] = 'Chrome';
        } elseif (preg_match('/safari/i', $ua)) {
            $info['browser'] = 'Safari';
        } elseif (preg_match('/firefox/i', $ua)) {
            $info['browser'] = 'Firefox';
        } elseif (preg_match('/opera|opr/i', $ua)) {
            $info['browser'] = 'Opera';
        }
        
        return $info;
    }
    
    /**
     * Mendapatkan informasi lokasi dari IP address
     * Menggunakan API gratis ipapi.co (1000 requests/day)
     */
    private function getLocationInfo($ipAddress) {
        // Default test data for localhost
        $testData = [
            'country' => 'Indonesia',
            'country_code' => 'ID',
            'city' => 'Jakarta',
            'region' => 'Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'timezone' => 'Asia/Jakarta',
            'language' => 'id'
        ];
        
        // For localhost/private IPs, return test data immediately
        $isLocalhost = ($ipAddress === '0.0.0.0' || 
                       $ipAddress === '127.0.0.1' || 
                       strpos($ipAddress, '192.168.') === 0 ||
                       strpos($ipAddress, '10.') === 0 ||
                       strpos($ipAddress, '172.') === 0);
        
        if ($isLocalhost) {
            return $testData;
        }
        
        // For real IPs, try to get geolocation
        try {
            $url = "https://ipapi.co/{$ipAddress}/json/";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_USERAGENT, 'QRCodeGenerator/1.0');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data && !isset($data['error'])) {
                    return [
                        'country' => $data['country_name'] ?? null,
                        'country_code' => $data['country_code'] ?? null,
                        'city' => $data['city'] ?? null,
                        'region' => $data['region'] ?? null,
                        'latitude' => isset($data['latitude']) ? (float)$data['latitude'] : null,
                        'longitude' => isset($data['longitude']) ? (float)$data['longitude'] : null,
                        'timezone' => $data['timezone'] ?? null,
                        'language' => $data['languages'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Geolocation API error: " . $e->getMessage());
        }
        
        // Return test data as fallback
        return $testData;
    }
    
    /**
     * Deteksi apakah request dari bot/crawler
     */
    private function isBot($userAgent) {
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'googlebot', 'bingbot', 'yahoo', 'baiduspider',
            'facebookexternalhit', 'twitterbot', 'whatsapp',
            'telegrambot', 'slackbot', 'linkedinbot'
        ];
        
        $ua = strtolower($userAgent);
        foreach ($botPatterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
