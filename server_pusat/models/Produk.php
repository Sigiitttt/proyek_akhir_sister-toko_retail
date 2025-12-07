<?php
// models/Produk.php

class Produk {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // [ADMIN] Ambil semua produk + harga aktif (untuk Dashboard Admin)
    public function getAll() {
        $query = "SELECT p.*, h.harga_jual 
                  FROM produk p 
                  LEFT JOIN harga h ON p.id_produk = h.id_produk 
                  WHERE (h.aktif = 1 OR h.aktif IS NULL) 
                  AND p.status = 'aktif'
                  ORDER BY p.nama_produk ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // [API] Ambil produk untuk sinkronisasi ke Toko
    // Hanya ambil yang berubah setelah waktu terakhir sync
    public function getForSync($last_sync) {
        $query = "SELECT p.id_produk, p.kode_produk, p.nama_produk, p.satuan, 
                         h.harga_jual, p.updated_at 
                  FROM produk p
                  JOIN harga h ON p.id_produk = h.id_produk
                  WHERE h.aktif = 1 
                  AND p.status = 'aktif'
                  AND p.updated_at > :last_sync";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':last_sync', $last_sync);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // [ADMIN] Tambah Produk Baru
    // [ADMIN] Tambah Produk Baru
    public function create($data) {
        // HAPUS 'created_at' DARI SINI
        $query = "INSERT INTO produk (kode_produk, nama_produk, satuan, status) 
                  VALUES (:kode, :nama, :satuan, 'aktif')";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':kode' => $data['kode_produk'],
            ':nama' => $data['nama_produk'],
            ':satuan' => $data['satuan']
        ]);
        return $this->db->lastInsertId(); 
    }

    // [ADMIN] Update Data Produk
    public function update($id, $data) {
        $query = "UPDATE produk SET nama_produk = :nama, satuan = :satuan, updated_at = NOW() 
                  WHERE id_produk = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':nama' => $data['nama_produk'],
            ':satuan' => $data['satuan'],
            ':id' => $id
        ]);
    }

    // [ADMIN] Soft Delete (Nonaktifkan produk)
    public function delete($id) {
        $query = "UPDATE produk SET status = 'nonaktif', updated_at = NOW() WHERE id_produk = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
?>