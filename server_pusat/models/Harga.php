<?php
// models/Harga.php

class Harga {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Ambil harga aktif saat ini untuk produk tertentu
    public function getHargaAktif($id_produk) {
        $query = "SELECT harga_jual FROM harga 
                  WHERE id_produk = :id AND aktif = 1 
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id_produk]);
        $result = $stmt->fetch();
        return $result ? $result['harga_jual'] : 0;
    }

    // Ambil riwayat perubahan harga (Untuk Audit)
    public function getHistory($id_produk) {
        $query = "SELECT * FROM harga 
                  WHERE id_produk = :id 
                  ORDER BY tgl_berlaku DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id_produk]);
        return $stmt->fetchAll();
    }

    // Set Harga Baru (Matikan yang lama -> Buat yang baru)
    public function setHargaBaru($id_produk, $harga_baru) {
        try {
            // 1. Nonaktifkan harga lama
            $sqlNonaktif = "UPDATE harga SET aktif = 0 WHERE id_produk = :id";
            $this->db->prepare($sqlNonaktif)->execute([':id' => $id_produk]);

            // 2. Insert harga baru
            $sqlBaru = "INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) 
                        VALUES (:id, :harga, NOW(), 1)";
            $stmt = $this->db->prepare($sqlBaru);
            $stmt->execute([
                ':id'    => $id_produk,
                ':harga' => $harga_baru
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>