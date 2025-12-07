<?php
// models/Stok.php

class Stok {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // [ADMIN/API] Lihat stok di toko tertentu
    public function getStokByToko($id_toko) {
        $query = "SELECT s.id_stok, p.kode_produk, p.nama_produk, s.jumlah, s.last_update
                  FROM stok_toko s
                  JOIN produk p ON s.id_produk = p.id_produk
                  WHERE s.id_toko = :id
                  ORDER BY p.nama_produk ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id_toko]);
        return $stmt->fetchAll();
    }

    // [ADMIN] Tambah/Update Stok ke Toko (Distribusi)
    public function distributeStok($id_toko, $id_produk, $qty) {
        // Cek dulu apakah stok sudah ada recordnya?
        $check = "SELECT id_stok FROM stok_toko WHERE id_toko = :toko AND id_produk = :prod";
        $stmt = $this->db->prepare($check);
        $stmt->execute([':toko' => $id_toko, ':prod' => $id_produk]);

        if ($stmt->rowCount() > 0) {
            // Update: Tambah stok lama dengan stok baru
            $sql = "UPDATE stok_toko SET jumlah = jumlah + :qty, last_update = NOW() 
                    WHERE id_toko = :toko AND id_produk = :prod";
        } else {
            // Insert: Buat record baru
            $sql = "INSERT INTO stok_toko (id_toko, id_produk, jumlah, last_update) 
                    VALUES (:toko, :prod, :qty, NOW())";
        }

        $stmtExec = $this->db->prepare($sql);
        return $stmtExec->execute([
            ':toko' => $id_toko,
            ':prod' => $id_produk,
            ':qty'  => $qty
        ]);
    }
    
    // [API] Update Stok (Overwrite/Adjustment dari Toko saat Opname)
    public function adjustStok($id_toko, $id_produk, $qty_baru) {
        // Menggunakan ON DUPLICATE KEY UPDATE (MySQL Specific)
        $sql = "INSERT INTO stok_toko (id_toko, id_produk, jumlah, last_update) 
                VALUES (:toko, :prod, :qty, NOW())
                ON DUPLICATE KEY UPDATE jumlah = :qty_update, last_update = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':toko' => $id_toko,
            ':prod' => $id_produk,
            ':qty'  => $qty_baru,
            ':qty_update' => $qty_baru
        ]);
    }
}
?>