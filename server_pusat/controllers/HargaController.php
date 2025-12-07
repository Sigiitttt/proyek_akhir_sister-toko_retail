<?php
// controllers/HargaController.php

class HargaController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // 1. Ambil daftar harga yang sedang AKTIF saat ini
    // Digunakan untuk tabel di halaman harga.php
    public function getCurrentPrices() {
        $query = "SELECT p.id_produk, p.kode_produk, p.nama_produk, h.harga_jual, h.tgl_berlaku 
                  FROM produk p
                  JOIN harga h ON p.id_produk = h.id_produk
                  WHERE h.aktif = 1
                  ORDER BY p.nama_produk ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 2. Proses Update Harga (Matikan harga lama, buat harga baru)
    public function updateHarga($id_produk, $harga_baru) {
        try {
            $this->db->beginTransaction();

            // A. Nonaktifkan harga lama
            $sqlOld = "UPDATE harga SET aktif = 0 WHERE id_produk = :id AND aktif = 1";
            $stmtOld = $this->db->prepare($sqlOld);
            $stmtOld->execute([':id' => $id_produk]);

            // B. Insert harga baru
            $sqlNew = "INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) 
                       VALUES (:id, :harga, NOW(), 1)";
            $stmtNew = $this->db->prepare($sqlNew);
            $stmtNew->execute([
                ':id' => $id_produk, 
                ':harga' => $harga_baru
            ]);

            // C. Update timestamp di tabel produk (PENTING untuk Sync Toko)
            // Agar saat toko request get_products, produk ini terdeteksi ada perubahan
            $sqlUpdateProd = "UPDATE produk SET updated_at = NOW() WHERE id_produk = :id";
            $this->db->prepare($sqlUpdateProd)->execute([':id' => $id_produk]);

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Harga berhasil diperbarui'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => 'Gagal update harga: ' . $e->getMessage()];
        }
    }

    // 3. Lihat History Perubahan Harga (Opsional, untuk audit)
    public function getHistoryHarga($id_produk) {
        $query = "SELECT * FROM harga WHERE id_produk = :id ORDER BY tgl_berlaku DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id_produk]);
        return $stmt->fetchAll();
    }
}
?>