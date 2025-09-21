<?php

/**
 * Class Database
 * 
 * Kelas untuk mengelola koneksi database menggunakan pola Singleton.
 * Memastikan hanya ada satu instance koneksi database yang digunakan
 * di seluruh aplikasi untuk efisiensi dan konsistensi.
 * 
 * Fitur:
 * - Singleton pattern untuk satu instance koneksi
 * - Auto-loading konfigurasi dari file .env
 * - Koneksi MySQL dengan charset utf8mb4
 * - Error handling untuk koneksi yang gagal
 */

require_once __DIR__ . '/../config/Config.php';

class Database {
    /** @var Database|null Instance tunggal dari class Database */
    private static $instance = null;
    
    /** @var mysqli Objek koneksi MySQL */
    private $connection;
    
    /**
     * Constructor private untuk mencegah instansiasi langsung
     * Menggunakan pola Singleton untuk memastikan hanya ada satu koneksi
     * 
     * @throws Exception Jika koneksi database gagal
     */
    private function __construct() {
        // Ambil konfigurasi database dari file .env
        $config = Config::getDatabaseConfig();
        
        // Buat koneksi ke MySQL
        $this->connection = new mysqli(
            $config['host'],      // Host database (localhost)
            $config['username'],  // Username database (root)
            $config['password'],  // Password database (kosong)
            $config['database'],  // Nama database (qrcode_db)
            $config['port']       // Port database (3306)
        );
        
        // Cek apakah koneksi berhasil
        if ($this->connection->connect_error) {
            throw new Exception('Koneksi database gagal: ' . $this->connection->connect_error);
        }
        
        // Set charset ke utf8mb4 untuk support emoji dan karakter khusus
        $this->connection->set_charset('utf8mb4');
    }
    
    /**
     * Mendapatkan instance tunggal dari class Database
     * 
     * Metode static ini mengimplementasikan pola Singleton.
     * Jika instance belum ada, akan dibuat yang baru.
     * Jika sudah ada, akan mengembalikan instance yang sama.
     * 
     * @return Database Instance tunggal dari class Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Mendapatkan objek koneksi MySQL
     * 
     * Method ini mengembalikan objek mysqli yang dapat digunakan
     * untuk menjalankan query database.
     * 
     * @return mysqli Objek koneksi MySQL
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Destructor untuk menutup koneksi database
     * 
     * Method ini dipanggil otomatis ketika objek dihancurkan
     * untuk memastikan koneksi database ditutup dengan benar.
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}