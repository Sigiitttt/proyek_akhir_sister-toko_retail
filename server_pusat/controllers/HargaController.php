<?php
class HargaController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function getTabelHarga($filter_toko = '') {
        // PERBAIKAN: Menambahkan p.kategori di SELECT
        $sql = "SELECT h.*, p.kode_produk, p.nama_produk, p.kategori, t.nama_toko 
                FROM harga h
                JOIN produk p ON h.id_produk = p.id_produk
                LEFT JOIN toko t ON h.id_toko = t.id_toko
                WHERE h.aktif = 1";

        // Jika ada filter toko tertentu
        if ($filter_toko != '') {
            if ($filter_toko == 'global') {
                $sql .= " AND h.id_toko IS NULL";
            } else {
                $sql .= " AND h.id_toko = :toko";
            }
        }

        $sql .= " ORDER BY p.nama_produk ASC";

        $stmt = $this->db->prepare($sql);
        
        if ($filter_toko != '' && $filter_toko != 'global') {
            $stmt->bindValue(':toko', $filter_toko);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function prosesUpdate($post) {
        try {
            $id_produk = $post['id_produk'];
            $harga_baru = $post['harga_baru'];
            $id_toko = ($post['id_toko'] == 'global') ? null : $post['id_toko'];

            // 1. Cek apakah harga untuk kombinasi Produk+Toko ini sudah ada?
            $sqlCek = "SELECT id_harga FROM harga WHERE id_produk = :prod AND ";
            $sqlCek .= ($id_toko === null) ? "id_toko IS NULL" : "id_toko = :toko";
            
            $stmtCek = $this->db->prepare($sqlCek);
            $stmtCek->bindValue(':prod', $id_produk);
            if ($id_toko !== null) $stmtCek->bindValue(':toko', $id_toko);
            $stmtCek->execute();

            if ($stmtCek->rowCount() > 0) {
                // UPDATE (Jika sudah ada)
                $sqlUp = "UPDATE harga SET harga_jual = :harga, tgl_berlaku = NOW(), aktif = 1 
                          WHERE id_produk = :prod AND ";
                $sqlUp .= ($id_toko === null) ? "id_toko IS NULL" : "id_toko = :toko";
                
                $stmt = $this->db->prepare($sqlUp);
            } else {
                // INSERT (Jika belum ada harga khusus, buat baru)
                $sqlIn = "INSERT INTO harga (id_produk, id_toko, harga_jual, tgl_berlaku, aktif) 
                          VALUES (:prod, :toko, :harga, NOW(), 1)";
                $stmt = $this->db->prepare($sqlIn);
            }

            // Bind Parameter Eksekusi
            $stmt->bindValue(':prod', $id_produk);
            $stmt->bindValue(':harga', $harga_baru);
            if ($id_toko !== null) $stmt->bindValue(':toko', $id_toko);
            // Khusus Insert butuh bind toko walau null
            if ($stmtCek->rowCount() == 0 && $id_toko === null) $stmt->bindValue(':toko', null);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Harga berhasil diperbarui!'];
            } else {
                return ['status' => 'error', 'message' => 'Gagal update database.'];
            }

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?>