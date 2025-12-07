<?php
require_once '../config/database.php';

class Toko {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fungsi: Validasi API Key (Auth)
    public function cekApiKey($api_key) {
        $query = "SELECT id_toko, nama_toko, kode_toko FROM toko WHERE api_key = :key AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':key', $api_key);
        $stmt->execute();
        
        return $stmt->fetch(); // Mengembalikan data toko jika valid, false jika tidak
    }
}
?>