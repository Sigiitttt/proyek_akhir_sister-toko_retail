<?php
// pos_toko_client/models/TokoModel.php

class TokoModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Ambil Info Toko (Dari Config Constant)
     * Karena di DB lokal tidak ada tabel 'toko' (adanya di config), kita wrap disini
     * agar Controller tidak perlu baca config langsung.
     */
    public function getInfoToko() {
        // Mengembalikan array data toko dari config/app.php
        return [
            'id_toko' => defined('ID_TOKO') ? ID_TOKO : 1,
            'api_key' => defined('TOKO_API_KEY') ? TOKO_API_KEY : '',
            'base_api_url' => defined('BASE_API_URL') ? BASE_API_URL : ''
        ];
    }

    /**
     * Cari User Kasir untuk Login
     * Dipanggil di: AuthController
     */
    public function getKasirByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        return $stmt->fetch();
    }

    /**
     * Tambah User Kasir Baru (Opsional, jika mau fitur register lokal)
     */
    public function createKasir($username, $password, $nama) {
        $sql = "INSERT INTO users (username, password, nama_lengkap) VALUES (:u, :p, :n)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':u' => $username,
            ':p' => $password, // Ingat hash password jika production
            ':n' => $nama
        ]);
    }
}
?>