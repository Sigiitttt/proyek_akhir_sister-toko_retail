<?php
// controllers/StokController.php

class StokController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * 1. Ambil Data Stok per Toko
     * Digunakan untuk menampilkan tabel monitoring di public/stok.php
     */
    public function getStokPerToko($id_toko) {
        // Join ke tabel produk untuk dapat nama barang & kode
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

    /**
     * 2. Proses Distribusi Stok (Admin kirim barang ke Toko)
     * Logic: Cek dulu apakah data stok sudah ada?
     * - Jika ADA: Update jumlahnya (ditambahkan)
     * - Jika TIDAK: Insert baris baru
     */
    public function tambahStokKeToko($id_toko, $id_produk, $jumlah) {
        try {
            // A. Cek keberadaan stok
            $cekQuery = "SELECT id_stok FROM stok_toko WHERE id_toko = :toko AND id_produk = :prod";
            $stmtCek = $this->db->prepare($cekQuery);
            $stmtCek->execute([':toko' => $id_toko, ':prod' => $id_produk]);
            
            if ($stmtCek->rowCount() > 0) {
                // UPDATE: Tambahkan stok lama dengan yang baru dikirim
                $sql = "UPDATE stok_toko 
                        SET jumlah = jumlah + :qty, last_update = NOW() 
                        WHERE id_toko = :toko AND id_produk = :prod";
            } else {
                // INSERT: Buat data stok baru untuk toko ini
                $sql = "INSERT INTO stok_toko (id_toko, id_produk, jumlah, last_update) 
                        VALUES (:toko, :prod, :qty, NOW())";
            }

            // Eksekusi Query yang dipilih (Update/Insert)
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':toko' => $id_toko,
                ':prod' => $id_produk,
                ':qty'  => $jumlah
            ]);

            return ['status' => 'success', 'message' => 'Stok berhasil didistribusikan'];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>