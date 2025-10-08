<?php

/**
 * Authentication Class with Google OAuth Support
 * 
 * Handles user authentication, session management, and Google OAuth integration
 * Supports both traditional login and Google OAuth login flows
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/Config.php';

class Auth {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start session if not already started
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current logged in user ID
     * @return int|null
     */
    public static function getCurrentUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current logged in user data
     * @return array|null
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $auth = self::getInstance();
        $userId = self::getCurrentUserId();
        
        $stmt = $auth->db->prepare("
            SELECT id, google_id, email, name, picture, username, plan, 
                   plan_expires_at, trial_ends_at, last_login, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Require authentication - redirect to login if not logged in
     * @param string $redirectTo URL to redirect after login
     */
    public static function requireAuth($redirectTo = null) {
        if (!self::isLoggedIn()) {
            $currentUrl = $redirectTo ?? $_SERVER['REQUEST_URI'] ?? 'dashboardAll.php';
            $loginUrl = 'login.php?redirect=' . urlencode($currentUrl);
            header('Location: ' . $loginUrl);
            exit;
        }
    }
    
    /**
     * Login user with Google OAuth
     * @param string $authCode Authorization code from Google
     * @return array Result with success status and user data
     */
    public function loginWithGoogle($authCode) {
        try {
            // Exchange authorization code for access token
            $tokenData = $this->exchangeCodeForToken($authCode);
            
            if (!$tokenData) {
                return ['success' => false, 'error' => 'Failed to exchange code for token'];
            }
            
            // Get user info from Google
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
            
            if (!$userInfo) {
                return ['success' => false, 'error' => 'Failed to get user info from Google'];
            }
            
            // Create or update user in database
            $user = $this->createOrUpdateGoogleUser($userInfo);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Failed to create or update user'];
            }
            
            // Set session
            self::startSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_plan'] = $user['plan'];
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            error_log('Google OAuth login error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Authentication failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Exchange authorization code for access token
     * @param string $authCode
     * @return array|null
     */
    private function exchangeCodeForToken($authCode) {
        $config = Config::getGoogleOAuthConfig();
        
        $postData = [
            'code' => $authCode,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Google token exchange failed. HTTP Code: ' . $httpCode . ', Response: ' . $response);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get user info from Google API
     * @param string $accessToken
     * @return array|null
     */
    private function getGoogleUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Google user info failed. HTTP Code: ' . $httpCode . ', Response: ' . $response);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create or update user from Google OAuth data
     * @param array $googleUser
     * @return array|null
     */
    private function createOrUpdateGoogleUser($googleUser) {
        $googleId = $googleUser['id'];
        $email = $googleUser['email'];
        $name = $googleUser['name'] ?? '';
        $picture = $googleUser['picture'] ?? '';
        
        // Check if user exists by Google ID
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->bind_param("s", $googleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();
        $stmt->close();
        
        if ($existingUser) {
            // Update existing user
            $stmt = $this->db->prepare("
                UPDATE users 
                SET email = ?, name = ?, picture = ?, last_login = NOW()
                WHERE google_id = ?
            ");
            $stmt->bind_param("ssss", $email, $name, $picture, $googleId);
            $stmt->execute();
            $stmt->close();
            
            return $existingUser;
        } else {
            // Check if user exists by email (for account linking)
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $emailUser = $result->fetch_assoc();
            $stmt->close();
            
            if ($emailUser) {
                // Link Google account to existing email account
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET google_id = ?, name = ?, picture = ?, last_login = NOW()
                    WHERE email = ?
                ");
                $stmt->bind_param("ssss", $googleId, $name, $picture, $email);
                $stmt->execute();
                $stmt->close();
                
                return $emailUser;
            } else {
                // Create new user with 30-day analytics trial
                $trialEndsAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $this->db->prepare("
                    INSERT INTO users (google_id, email, name, picture, plan, trial_ends_at, last_login)
                    VALUES (?, ?, ?, ?, 'free', ?, NOW())
                ");
                $stmt->bind_param("sssss", $googleId, $email, $name, $picture, $trialEndsAt);
                $stmt->execute();
                $userId = $this->db->insert_id;
                $stmt->close();
                
                // Get the created user
                $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $newUser = $result->fetch_assoc();
                $stmt->close();
                
                return $newUser;
            }
        }
    }
    
    /**
     * Update user's last login timestamp
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::startSession();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    /**
     * Check if user has access to analytics
     * @param array|null $user User data (optional, will get current user if not provided)
     * @return bool
     */
    public static function hasAnalyticsAccess($user = null) {
        if (!$user) {
            $user = self::getCurrentUser();
        }
        
        if (!$user) {
            return false;
        }
        
        // Paid plans always have analytics
        if (in_array($user['plan'], ['starter', 'pro'])) {
            return true;
        }
        
        // Free plan: check if trial is still active
        if ($user['plan'] === 'free' && $user['trial_ends_at']) {
            return strtotime($user['trial_ends_at']) > time();
        }
        
        return false;
    }
    
    /**
     * Check if user can create more QR codes this month
     * @param int|null $userId
     * @return array [canCreate => bool, used => int, limit => int]
     */
    public static function canCreateQRCode($userId = null) {
        if (!$userId) {
            $userId = self::getCurrentUserId();
        }
        
        if (!$userId) {
            return ['canCreate' => false, 'used' => 0, 'limit' => 0];
        }
        
        $user = self::getCurrentUser();
        if (!$user) {
            return ['canCreate' => false, 'used' => 0, 'limit' => 0];
        }
        
        $limit = Config::getPlanLimit($user['plan'], 'qr_codes_per_month', 10);
        $monthYear = date('Y-m');
        
        $auth = self::getInstance();
        $stmt = $auth->db->prepare("
            SELECT qr_codes_created 
            FROM user_quotas 
            WHERE user_id = ? AND month_year = ?
        ");
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $quota = $result->fetch_assoc();
        $stmt->close();
        
        $used = $quota ? $quota['qr_codes_created'] : 0;
        
        return [
            'canCreate' => $used < $limit,
            'used' => $used,
            'limit' => $limit
        ];
    }
    
    /**
     * Increment user's QR code usage for current month
     * @param int|null $userId
     */
    public static function incrementQRCodeUsage($userId = null) {
        if (!$userId) {
            $userId = self::getCurrentUserId();
        }
        
        if (!$userId) {
            return;
        }
        
        $monthYear = date('Y-m');
        $auth = self::getInstance();
        
        $stmt = $auth->db->prepare("
            INSERT INTO user_quotas (user_id, month_year, qr_codes_created)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE qr_codes_created = qr_codes_created + 1
        ");
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Decrement user's QR code usage for current month (when deleting QR codes)
     * @param int|null $userId
     */
    public static function decrementQRCodeUsage($userId = null) {
        if (!$userId) {
            $userId = self::getCurrentUserId();
        }
        
        if (!$userId) {
            return;
        }
        
        $monthYear = date('Y-m');
        $auth = self::getInstance();
        
        // Only decrement if count is greater than 0
        $stmt = $auth->db->prepare("
            UPDATE user_quotas 
            SET qr_codes_created = GREATEST(0, qr_codes_created - 1)
            WHERE user_id = ? AND month_year = ? AND qr_codes_created > 0
        ");
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $stmt->close();
    }
}
