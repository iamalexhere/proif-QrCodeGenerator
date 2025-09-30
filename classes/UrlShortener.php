<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/Config.php';

class UrlShortener {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    private function generateShortCode($length = 6) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $shortCode = '';
        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $shortCode;
    }
    
    // PERBAIKAN: Pastikan fungsi ini menerima 5 parameter
    public function createShortUrl($originalUrl, $customUrl = '', $logoPath = '', $qrColor = '#000000', $status = 'active') {
        // === GENERATE KODE PENDEK YANG UNIK ===
        do {
            $shortCode = $this->generateShortCode();
            
            $stmt = $this->db->prepare("SELECT id FROM links WHERE short_url = ?");
            $stmt->bind_param("s", $shortCode);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } while ($result->num_rows > 0);
        
        // === BUAT URL PENDEK LENGKAP ===
        $baseUrl = Config::get('SHORT_DOMAIN', 'localhost/qr/r');
        $shortUrl = (strpos($baseUrl, 'http') === 0 ? '' : 'http://') . $baseUrl . '/' . $shortCode;
        
        // === SIMPAN KE DATABASE ===
        $sql = "INSERT INTO links (original_url, short_url, custom_url, logo_path, qr_color, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssssss", $originalUrl, $shortCode, $customUrl, $logoPath, $qrColor, $status);
        
        if ($stmt->execute()) {
            $insertId = $this->db->insert_id;
            $stmt->close();
            
            return [
                'id' => $insertId,
                'short_url' => $shortUrl,
                'short_code' => $shortCode,
                'original_url' => $originalUrl
            ];
        } else {
            // --- BAGIAN PENTING UNTUK DEBUGGING ---
            // Baris ini akan mengambil pesan error spesifik dari MySQL
            $error = $this->db->error; 
            $stmt->close();
            // Lemparkan exception dengan pesan error yang detail
            throw new Exception('Gagal menyimpan ke DB: ' . $error);
        }
    }
    
    public function getLinkDataByShortCode($shortCode) {
        $stmt = $this->db->prepare("SELECT id, original_url FROM links WHERE short_url = ?");
        $stmt->bind_param("s", $shortCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        
        $stmt->close();
        return null;
    }
}