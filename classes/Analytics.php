<?php
// classes/Analytics.php

require_once __DIR__ . '/Database.php';

class Analytics {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Method untuk mencatat setiap klik (scan) ke dalam database.
     */
    public function recordClick($linkId) {
        // 1. Ambil Informasi Pengunjung
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // 2. Dapatkan Lokasi dari IP Address
        $geoData = @json_decode(@file_get_contents("http://ip-api.com/json/{$ipAddress}"), true);
        $country = $geoData['country'] ?? 'Unknown';
        $city = $geoData['city'] ?? 'Unknown';

        // 3. Tebak Jenis Perangkat
        $deviceType = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($userAgent))) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($userAgent))) {
            $deviceType = 'Mobile';
        }

        // 4. Simpan ke Tabel `clicks`
        $sql = "INSERT INTO clicks (link_id, ip_address, user_agent, country, city, device_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssss", $linkId, $ipAddress, $userAgent, $country, $city, $deviceType);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method untuk mendapatkan ringkasan statistik (total & hari ini).
     */
    public function getSummaryStats($linkId) {
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

    /**
     * Method untuk mendapatkan statistik berdasarkan perangkat.
     */
    public function getDeviceStats($linkId) {
        $stmt = $this->db->prepare("SELECT device_type, COUNT(*) as count FROM clicks WHERE link_id = ? GROUP BY device_type");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Method untuk mendapatkan statistik berdasarkan lokasi (negara & kota).
     */
    public function getLocationStats($linkId) {
        $stats = ['countries' => [], 'cities' => []];

        // Data per Negara (Top 5)
        $stmt = $this->db->prepare("SELECT country, COUNT(*) as count FROM clicks WHERE link_id = ? AND country != 'Unknown' GROUP BY country ORDER BY count DESC LIMIT 5");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $stats['countries'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Data per Kota (Top 5)
        $stmt = $this->db->prepare("SELECT city, COUNT(*) as count FROM clicks WHERE link_id = ? AND city != 'Unknown' GROUP BY city ORDER BY count DESC LIMIT 5");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $stats['cities'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $stats;
    }
    
    /**
     * Method untuk mendapatkan statistik berdasarkan pola waktu (per hari).
     */
    public function getTemporalStats($linkId, $days = 7) {
        $stmt = $this->db->prepare("SELECT DATE(click_time) as date, COUNT(*) as count FROM clicks WHERE link_id = ? AND click_time >= CURDATE() - INTERVAL ? DAY GROUP BY DATE(click_time) ORDER BY date ASC");
        $stmt->bind_param("ii", $linkId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Method utama untuk mengambil semua statistik sekaligus.
     */
    public function getLinkStatistics($linkId) {
        $summary = $this->getSummaryStats($linkId);
        $locations = $this->getLocationStats($linkId);

        return [
            'total_scans'   => $summary['total'],
            'today_scans'   => $summary['today'],
            'devices'       => $this->getDeviceStats($linkId),
            'countries'     => $locations['countries'],
            'cities'        => $locations['cities'],
            'scans_per_day' => $this->getTemporalStats($linkId)
        ];
    }
}