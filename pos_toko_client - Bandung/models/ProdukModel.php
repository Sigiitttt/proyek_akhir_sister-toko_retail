<?php
// pos_toko_client/models/ProdukModel.php

class ProdukModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Ambil semua produk (atau cari berdasarkan keyword)
     * Dipanggil di: KasirController (halaman transaksi)
     */
    public function getAll($keyword = null) {
        $sql = "SELECT * FROM produk";
        if ($keyword) {
            $sql .= " WHERE nama_produk LIKE :cari OR kode_produk LIKE :cari";
        }
        $sql .= " LIMIT 50"; // Limit biar ringan

        $stmt = $this->db->prepare($sql);
        if ($keyword) {
            $stmt->bindValue(':cari', "%$keyword%");
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Hitung total produk lokal
     * Dipanggil di: Dashboard
     */
    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM produk");
        return $stmt->fetchColumn();
    }

    /**
     * Cek kapan terakhir kali data di-sync dari pusat
     * Dipanggil di: SyncController
     */
    public function getLastSyncTime() {
        $stmt = $this->db->query("SELECT MAX(updated_at_pusat) FROM produk");
        $lastSync = $stmt->fetchColumn();
        return $lastSync ? $lastSync : '2000-01-01 00:00:00';
    }

    /**
     * Simpan/Update produk dari hasil download API Pusat
     * Menggunakan ON DUPLICATE KEY UPDATE (Upsert)
     * Dipanggil di: SyncController
     */
    public function upsertFromSync($dataProduk) {
        try {
            $sql = "INSERT INTO produk (id_produk, kode_produk, nama_produk, satuan, harga_jual, updated_at_pusat) 
                    VALUES (:id, :kode, :nama, :satuan, :harga, :updated)
                    ON DUPLICATE KEY UPDATE 
                    kode_produk = :kode, 
                    nama_produk = :nama, 
                    satuan = :satuan, 
                    harga_jual = :harga, 
                    updated_at_pusat = :updated";

            $stmt = $this->db->prepare($sql);

            foreach ($dataProduk as $p) {
                $stmt->execute([
                    ':id'      => $p['id_produk'],
                    ':kode'    => $p['kode_produk'],
                    ':nama'    => $p['nama_produk'],
                    ':satuan'  => $p['satuan'],
                    ':harga'   => $p['harga_jual'],
                    ':updated' => $p['updated_at']
                ]);
            }
            return true;
        } catch (PDOException $e) {
            // Log error jika perlu
            return false;
        }
    }
}
?>