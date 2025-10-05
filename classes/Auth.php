<?php

/**
 * Class Auth
 * 
 * Handles user authentication with Google OAuth 2.0
 * Manages sessions and user authorization
 */

require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    private static $instance = null;
    
    // Plan limits
    const FREE_PLAN_LIMIT = 10; // 10 QR codes per month
    const PRO_PLAN_LIMIT = -1;  // Unlimited
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Login or register user with Google OAuth data
     */
    public function loginWithGoogle($googleData) {
        $googleId = $googleData['sub'] ?? $googleData['id'];
        $email = $googleData['email'];
        $name = $googleData['name'] ?? '';
        $picture = $googleData['picture'] ?? '';
        
        // Check if user exists
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->bind_param("s", $googleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            // Update last login and user info
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW(), name = ?, picture = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $picture, $user['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Create new user
            $stmt = $this->db->prepare("INSERT INTO users (google_id, email, name, picture, plan, last_login) VALUES (?, ?, ?, ?, 'free', NOW())");
            $stmt->bind_param("ssss", $googleId, $email, $name, $picture);
            $stmt->execute();
            $user = [
                'id' => $this->db->insert_id,
                'google_id' => $googleId,
                'email' => $email,
                'name' => $name,
                'picture' => $picture,
                'plan' => 'free'
            ];
            $stmt->close();
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_picture'] = $picture;
        $_SESSION['user_plan'] = $user['plan'];
        
        return $user;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Require authentication (redirect to login if not logged in)
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            // Get the current script directory
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            // Build relative path to login
            $loginPath = rtrim($scriptDir, '/') . '/login.php';
            header('Location: ' . $loginPath . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
    
    /**
     * Check if user can create more QR codes based on their plan
     */
    public function canCreateQR() {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Pro plan has unlimited QR codes
        if ($user['plan'] === 'pro') {
            // Check if plan is still active
            if ($user['plan_expires_at'] && strtotime($user['plan_expires_at']) < time()) {
                return false; // Plan expired
            }
            return true;
        }
        
        // Free plan: check monthly limit
        $userId = $user['id'];
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM links 
            WHERE user_id = ? 
            AND YEAR(created_at) = YEAR(NOW()) 
            AND MONTH(created_at) = MONTH(NOW())
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] < self::FREE_PLAN_LIMIT;
    }
    
    /**
     * Get remaining QR codes for current month
     */
    public function getRemainingQRCodes() {
        $user = $this->getCurrentUser();
        if (!$user) {
            return 0;
        }
        
        if ($user['plan'] === 'pro') {
            return -1; // Unlimited
        }
        
        $userId = $user['id'];
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM links 
            WHERE user_id = ? 
            AND YEAR(created_at) = YEAR(NOW()) 
            AND MONTH(created_at) = MONTH(NOW())
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return max(0, self::FREE_PLAN_LIMIT - $row['count']);
    }
    
    /**
     * Upgrade user to pro plan
     */
    public function upgradeToPro($userId, $months = 1) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$months months"));
        $stmt = $this->db->prepare("UPDATE users SET plan = 'pro', plan_expires_at = ? WHERE id = ?");
        $stmt->bind_param("si", $expiresAt, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        // Update session
        if ($success && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['user_plan'] = 'pro';
        }
        
        return $success;
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        $stats = [];
        
        // Total QR codes
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM links WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_qr_codes'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Total clicks
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM clicks c 
            JOIN links l ON c.link_id = l.id 
            WHERE l.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_clicks'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // This month's QR codes
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM links 
            WHERE user_id = ? 
            AND YEAR(created_at) = YEAR(NOW()) 
            AND MONTH(created_at) = MONTH(NOW())
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['this_month_qr_codes'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $stats;
    }
}
