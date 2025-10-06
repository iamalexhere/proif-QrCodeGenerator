<?php

/**
 * Class UrlShortener
 * 
 * Kelas untuk mengelola layanan pemendek URL (URL Shortener).
 * Mengkonversi URL panjang menjadi kode pendek yang mudah dibagikan
 * dan dapat dilacak untuk statistik klik.
 * 
 * Fitur:
 * - Pembuatan kode pendek unik (6 karakter)
 * - Penyimpanan mapping URL di database
 * - Pencarian URL asli berdasarkan kode pendek
 * - Pencatatan klik untuk statistik sederhana
 * - Support untuk custom URL dan metadata QR code
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/Config.php';

class UrlShortener {
    /** @var mysqli Koneksi database */
    private $db;
    
    /**
     * Constructor - Inisialisasi koneksi database
     * 
     * Mengambil instance database menggunakan pola Singleton
     * untuk memastikan efisiensi koneksi.
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Membuat kode pendek secara acak
     * 
     * Fungsi private ini menghasilkan string acak yang terdiri dari
     * huruf kecil, huruf besar, dan angka untuk digunakan sebagai
     * kode pendek URL.
     * 
     * @param int $length Panjang kode yang diinginkan (default: 6)
     * @return string Kode pendek yang dihasilkan
     */
    private function generateShortCode($length = 6) {
        // Karakter yang digunakan untuk membuat kode pendek
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $shortCode = '';
        
        // Loop untuk membuat kode sepanjang $length
        for ($i = 0; $i < $length; $i++) {
            // Pilih karakter secara acak dari daftar karakter
            $shortCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $shortCode;
    }
    
    /**
     * Membuat URL pendek dan menyimpannya ke database
     * 
     * Fungsi utama untuk mengkonversi URL panjang menjadi URL pendek.
     * Proses yang dilakukan:
     * 1. Generate kode pendek yang unik
     * 2. Cek keunikan kode di database
     * 3. Buat URL pendek lengkap
     * 4. Simpan semua data ke database
     * 
     * @param string $originalUrl URL asli yang akan dipendekkan
     * @param string $customUrl URL custom (opsional)
     * @param string $logoPath Path ke file logo untuk QR code (opsional)
     * @param string $qrColor Warna QR code dalam format hex (default: #000000)
     * @return array Data URL pendek yang berhasil dibuat
     * @throws Exception Jika gagal menyimpan ke database
     */
    public function createShortUrl($originalUrl, $customUrl = '', $logoPath = '', $qrColor = '#000000') {
        // === GENERATE KODE PENDEK YANG UNIK ===
        // Loop sampai mendapat kode yang belum ada di database
        do {
            $shortCode = $this->generateShortCode();
            
            // Cek apakah kode sudah ada di database
            $stmt = $this->db->prepare("SELECT id FROM links WHERE short_url = ?");
            $stmt->bind_param("s", $shortCode);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } while ($result->num_rows > 0); // Ulangi jika kode sudah ada
        
        // === BUAT URL PENDEK LENGKAP ===
        $baseUrl = Config::get('SHORT_DOMAIN', 'localhost/qr/r');
        // Tambahkan http:// jika belum ada protocol
        $shortUrl = (strpos($baseUrl, 'http') === 0 ? '' : 'http://') . $baseUrl . '/' . $shortCode;
        
        // === SIMPAN KE DATABASE ===
        $sql = "INSERT INTO links (original_url, short_url, custom_url, logo_path, qr_color) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssss", $originalUrl, $shortCode, $customUrl, $logoPath, $qrColor);
        
        if ($stmt->execute()) {
            $insertId = $this->db->insert_id;
            $stmt->close();
            
            // Return data lengkap URL yang berhasil dibuat
            return [
                'id' => $insertId,
                'short_url' => $shortUrl,
                'short_code' => $shortCode,
                'original_url' => $originalUrl
            ];
        } else {
            $stmt->close();
            throw new Exception('Gagal membuat URL pendek');
        }
    }
    
    /**
     * Mendapatkan URL asli berdasarkan kode pendek
     * 
     * Fungsi untuk mencari URL asli yang tersimpan di database
     * berdasarkan kode pendek yang diberikan.
     * 
     * @param string $shortCode Kode pendek URL
     * @return string|null URL asli jika ditemukan, null jika tidak ada
     */
    public function getOriginalUrl($shortCode) {
        // Query untuk mencari URL asli berdasarkan kode pendek
        $stmt = $this->db->prepare("SELECT original_url FROM links WHERE short_url = ?");
        $stmt->bind_param("s", $shortCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Jika ditemukan, kembalikan URL asli
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['original_url'];
        }
        
        // Jika tidak ditemukan, kembalikan null
        $stmt->close();
        return null;
    }
    
    /**
     * Mencatat klik dan mengembalikan URL asli
     * 
     * Fungsi untuk mencatat aktivitas klik pada URL pendek
     * dan mengembalikan URL asli untuk proses redirect.
     * 
     * Versi sederhana: hanya mengembalikan URL asli tanpa
     * menyimpan statistik klik. Bisa dikembangkan lebih lanjut
     * untuk menambahkan fitur analytics.
     * 
     * @param string $shortCode Kode pendek URL yang diklik
     * @return string|null URL asli jika valid, null jika tidak ditemukan
     */
    public function recordClick($shortCode) {
        // Ambil URL asli berdasarkan kode pendek
        $originalUrl = $this->getOriginalUrl($shortCode);
        
        if ($originalUrl) {
            // === TEMPAT UNTUK MENAMBAH STATISTIK KLIK ===
            // Di sini bisa ditambahkan logic untuk:
            // - Menyimpan waktu klik
            // - Mencatat IP address pengunjung
            // - Menyimpan user agent browser
            // - Menghitung total klik per URL
            
            // Untuk saat ini, hanya kembalikan URL asli
            return $originalUrl;
        }
        
        // Jika kode pendek tidak valid/tidak ditemukan
        return null;
    }

    public function getLinkDataByShortCode($shortCode){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT id, original_url, short_url, custom_url, status 
            FROM links 
            WHERE short_url = ? OR custom_url = ? 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $shortCode, $shortCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $linkData = $result->fetch_assoc();
        $stmt->close();
        return $linkData ?: null;
    }
}