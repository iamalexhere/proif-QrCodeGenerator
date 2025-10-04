<?php

//Mencatat scan qr ke tabel "clicks" 

require_once __DIR__ . '/Database.php';

class Statistics {
    /* Koneksi database */
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Method untuk mencatat setiap klik (scan) ke dalam database.
     * Ini adalah versi sederhana dari flow teman Anda.
     *
     * @param int $linkId ID dari link yang di-klik.
     */
    public function recordClick($linkId) {
        // 1. Get IP address & User Agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // 2. Deteksi Bot Sederhana
        // Jika user agent mengandung kata 'bot' atau 'spider', kita anggap itu bot dan tidak mencatatnya.
        if (preg_match('/(bot|crawl|spider|slurp)/i', $userAgent)) {
            return; // Hentikan proses
        }

        // 3. Get location dari IP (via API ip-api.com)
        // Tanda @ digunakan untuk menekan error jika API gagal
        $geoData = @json_decode(@file_get_contents("http://ip-api.com/json/{$ipAddress}"), true);
        $country = $geoData['country'] ?? 'Unknown';
        $city = $geoData['city'] ?? 'Unknown';

        // 4. Parse device info dari User Agent
        $deviceType = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($userAgent))) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($userAgent))) {
            $deviceType = 'Mobile';
        }

        // 5. INSERT ke tabel clicks
        // Perintah ini cocok persis dengan tabel 'clicks' yang sudah Anda buat.
        $sql = "INSERT INTO clicks (link_id, ip_address, user_agent, country, city, device_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssss", $linkId, $ipAddress, $userAgent, $country, $city, $deviceType);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method utama untuk mengambil semua statistik untuk satu link.
     *
     * @param int $linkId ID dari link yang ingin dilihat statistiknya.
     * @return array Kumpulan data statistik.
     */
    public function getLinkStatistics($linkId) {
        return [
            'summary'   => $this->getSummaryStats($linkId),
            'devices'   => $this->getDeviceStats($linkId),
            'locations' => $this->getLocationStats($linkId),
            'temporal'  => $this->getTemporalStats($linkId)
        ];
    }

    // --- Method-method privat untuk mengambil data ---

    private function getSummaryStats($linkId) {
        $stats = ['total' => 0, 'today' => 0];

        // Hitung total klik
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM clicks WHERE link_id = ?");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $stats['total'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Hitung klik hari ini
        $stmt = $this->db->prepare("SELECT COUNT(*) as today FROM clicks WHERE link_id = ? AND DATE(click_time) = CURDATE()");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $stats['today'] = $stmt->get_result()->fetch_assoc()['today'] ?? 0;
        $stmt->close();
        
        return $stats;
    }

    private function getDeviceStats($linkId) {
        $stmt = $this->db->prepare("SELECT device_type, COUNT(*) as count FROM clicks WHERE link_id = ? GROUP BY device_type");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getLocationStats($linkId) {
        $locations = ['countries' => [], 'cities' => []];
        
        // Data per Negara (Top 5)
        $stmt = $this->db->prepare("SELECT country, COUNT(*) as count FROM clicks WHERE link_id = ? AND country != 'Unknown' GROUP BY country ORDER BY count DESC LIMIT 5");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $locations['countries'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Data per Kota (Top 5)
        $stmt = $this->db->prepare("SELECT city, COUNT(*) as count FROM clicks WHERE link_id = ? AND city != 'Unknown' GROUP BY city ORDER BY count DESC LIMIT 5");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $locations['cities'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $locations;
    }
    
    private function getTemporalStats($linkId, $days = 7) {
        $stmt = $this->db->prepare("SELECT DATE(click_time) as date, COUNT(*) as count FROM clicks WHERE link_id = ? AND click_time >= CURDATE() - INTERVAL ? DAY GROUP BY DATE(click_time) ORDER BY date ASC");
        $stmt->bind_param("ii", $linkId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}