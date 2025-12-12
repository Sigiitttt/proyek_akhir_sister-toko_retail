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
        
        // --- LOGIKA SORTING ---
        // 1. Prioritas Stok: (stok_lokal > 0) DESC 
        //    Artinya: Produk ada stok (True/1) muncul duluan, stok 0 di bawah.
        // 2. Urutan Abjad: nama_produk ASC
        $sql .= " ORDER BY (stok_lokal > 0) DESC, nama_produk ASC";

        // LIMIT dihapus agar semua produk (100+) muncul
        // $sql .= " LIMIT 50"; 

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
     * Dipanggil di: SyncController
     */
    public function upsertFromSync($dataProduk) {
        try {
            // INSERT INTO ... ON DUPLICATE KEY UPDATE
            // Tambahan: Kita juga update 'stok_lokal' jika server mengirim data 'stok_toko'
            // Ini penting agar saat pertama kali sync, stok tidak 0.
            
            $sql = "INSERT INTO produk (id_produk, kode_produk, nama_produk, satuan, harga_jual, stok_lokal, updated_at_pusat) 
                    VALUES (:id, :kode, :nama, :satuan, :harga, :stok, :updated)
                    ON DUPLICATE KEY UPDATE 
                    kode_produk = :kode, 
                    nama_produk = :nama, 
                    satuan = :satuan, 
                    harga_jual = :harga, 
                    stok_lokal = :stok, -- Update stok lokal sesuai jatah dari server
                    updated_at_pusat = :updated";

            $stmt = $this->db->prepare($sql);

            foreach ($dataProduk as $p) {
                // Pastikan ada nilai default untuk stok jika server tidak mengirimnya
                $stokDariServer = isset($p['stok_toko']) ? $p['stok_toko'] : 0;

                $stmt->execute([
                    ':id'      => $p['id_produk'],
                    ':kode'    => $p['kode_produk'],
                    ':nama'    => $p['nama_produk'],
                    ':satuan'  => $p['satuan'],
                    ':harga'   => $p['harga_jual'],
                    ':stok'    => $stokDariServer, 
                    ':updated' => $p['updated_at']
                ]);
            }
            return true;
        } catch (PDOException $e) {
            // Jika ingin debug, uncomment baris bawah:
            // echo "Error: " . $e->getMessage(); 
            return false;
        }
    }
}
?>