<?php
// controllers/StokController.php

class StokController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // 1. Ambil Data Stok Gudang Pusat
    public function getStokPusat() {
        $sql = "SELECT p.id_produk, p.kode_produk, p.nama_produk, p.satuan, p.stok_global,
                COALESCE(SUM(s.jumlah), 0) as total_di_cabang
                FROM produk p
                LEFT JOIN stok_toko s ON p.id_produk = s.id_produk
                WHERE p.status = 'aktif'
                GROUP BY p.id_produk
                ORDER BY p.stok_global DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // 2. Ambil Riwayat Pengiriman
    public function getRiwayat() {
        $sql = "SELECT r.*, t.nama_toko, p.nama_produk, p.kode_produk 
                FROM riwayat_distribusi r
                JOIN toko t ON r.id_toko = t.id_toko
                JOIN produk p ON r.id_produk = p.id_produk
                ORDER BY r.tanggal DESC LIMIT 20";
        return $this->db->query($sql)->fetchAll();
    }

    // 3. Ambil Stok Per Toko
    public function getStokPerToko($id_toko) {
        $query = "SELECT s.id_stok, s.jumlah, s.last_update, 
                         p.nama_produk, p.kode_produk 
                  FROM stok_toko s
                  JOIN produk p ON s.id_produk = p.id_produk
                  WHERE s.id_toko = :id
                  ORDER BY p.nama_produk ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id_toko]);
        return $stmt->fetchAll();
    }

    public function getDetailSebaran($id_produk) {
        $sql = "SELECT t.nama_toko, s.jumlah, s.last_update 
                FROM stok_toko s
                JOIN toko t ON s.id_toko = t.id_toko
                WHERE s.id_produk = :id AND s.jumlah > 0
                ORDER BY s.jumlah DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id_produk]);
        return $stmt->fetchAll();
    }

    // 4. PROSES DISTRIBUSI STOK (INTI LOGIKA)
    public function distribute($id_toko, $id_produk, $jumlah) {
        try {
            $this->db->beginTransaction();

            // A. CEK STOK GLOBAL DULU
            $stmtProd = $this->db->prepare("SELECT stok_global, nama_produk FROM produk WHERE id_produk = :id");
            $stmtProd->execute([':id' => $id_produk]);
            $produk = $stmtProd->fetch();

            if (!$produk) {
                throw new Exception("Produk tidak ditemukan");
            }

            // Validasi: Apakah stok gudang cukup?
            if ($produk['stok_global'] < $jumlah) {
                throw new Exception("Stok Gudang Pusat tidak cukup! Sisa: " . number_format($produk['stok_global']));
            }

            // B. KURANGI STOK GLOBAL (Gudang Pusat)
            $kurangStok = "UPDATE produk SET stok_global = stok_global - :qty WHERE id_produk = :id";
            $this->db->prepare($kurangStok)->execute([':qty' => $jumlah, ':id' => $id_produk]);

            // C. TAMBAH STOK KE TOKO CABANG (Distribusi)
            $cek = $this->db->prepare("SELECT id_stok FROM stok_toko WHERE id_toko = :toko AND id_produk = :prod");
            $stmtCek = $cek; // Alias
            $stmtCek->execute([':toko' => $id_toko, ':prod' => $id_produk]);
            
            if ($stmtCek->rowCount() > 0) {
                // UPDATE
                $sql = "UPDATE stok_toko SET jumlah = jumlah + :qty, last_update = NOW() 
                        WHERE id_toko = :toko AND id_produk = :prod";
            } else {
                // INSERT
                $sql = "INSERT INTO stok_toko (id_toko, id_produk, jumlah, last_update) 
                        VALUES (:toko, :prod, :qty, NOW())";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':toko' => $id_toko,
                ':prod' => $id_produk,
                ':qty'  => $jumlah
            ]);

            // D. CATAT KE RIWAYAT
            $sqlRiwayat = "INSERT INTO riwayat_distribusi (id_toko, id_produk, jumlah, status, tanggal) 
                           VALUES (:toko, :prod, :qty, 'terkirim', NOW())";
            $this->db->prepare($sqlRiwayat)->execute([
                ':toko' => $id_toko,
                ':prod' => $id_produk,
                ':qty'  => $jumlah
            ]);

            // E. TRIGGER UPDATE TIMESTAMP (PENTING!!!)
            // Ini memaksa produk terdeteksi "Baru Diupdate" oleh Client
            $this->db->prepare("UPDATE produk SET updated_at = NOW() WHERE id_produk = :id")
                     ->execute([':id' => $id_produk]);

            $this->db->commit();
            return ['status' => 'success', 'message' => "Berhasil kirim $jumlah stok ke toko. Stok pusat berkurang."];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?>