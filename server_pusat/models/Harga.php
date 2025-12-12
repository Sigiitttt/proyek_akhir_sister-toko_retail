<?php
// models/Harga.php

class Harga
{
    private $db;

    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
    }

    // 1. Ambil Harga (Revisi Bug Filter Global)
    public function getHargaList($id_toko_filter = null)
    {
        $sql = "SELECT p.id_produk, p.kode_produk, p.nama_produk, 
                       h.harga_jual, h.tgl_berlaku, h.id_toko, t.nama_toko
                FROM produk p
                JOIN harga h ON p.id_produk = h.id_produk
                LEFT JOIN toko t ON h.id_toko = t.id_toko
                WHERE h.aktif = 1 AND p.status = 'aktif' ";

        // LOGIKA PEMBUATAN QUERY
        if ($id_toko_filter === 'global') {
            // Jika filter 'global', cari yang NULL. (Tidak butuh parameter :toko)
            $sql .= " AND h.id_toko IS NULL";
        } elseif (is_numeric($id_toko_filter) && $id_toko_filter > 0) {
            // Jika filter angka (ID Toko), cari yang match. (Butuh parameter :toko)
            $sql .= " AND h.id_toko = :toko";
        }

        $sql .= " ORDER BY p.nama_produk ASC";

        $stmt = $this->db->prepare($sql);

        // LOGIKA BINDING PARAMETER (Harus sinkron dengan logika Query di atas)
        // Kita gunakan is_numeric() agar string "global" tidak masuk ke sini
        if (is_numeric($id_toko_filter) && $id_toko_filter > 0) {
            $stmt->bindValue(':toko', $id_toko_filter);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 2. Set Harga Baru
    public function setHarga($id_produk, $harga_baru, $id_toko = null)
    {
        try {
            $this->db->beginTransaction();

            // A. Matikan harga lama
            if ($id_toko) {
                // Matikan harga khusus toko ini
                $sqlOff = "UPDATE harga SET aktif = 0 WHERE id_produk = :p AND id_toko = :t AND aktif = 1";
                $stmt = $this->db->prepare($sqlOff);
                $stmt->execute([':p' => $id_produk, ':t' => $id_toko]);
            } else {
                // Matikan harga global (id_toko IS NULL)
                $sqlOff = "UPDATE harga SET aktif = 0 WHERE id_produk = :p AND id_toko IS NULL AND aktif = 1";
                $this->db->prepare($sqlOff)->execute([':p' => $id_produk]);
            }

            // B. Insert Harga Baru
            $sqlNew = "INSERT INTO harga (id_produk, id_toko, harga_jual, tgl_berlaku, aktif) 
                       VALUES (:p, :t, :h, NOW(), 1)";
            $this->db->prepare($sqlNew)->execute([
                ':p' => $id_produk,
                ':t' => $id_toko, // NULL jika global
                ':h' => $harga_baru
            ]);

            // C. Trigger Update di Produk
            $this->db->prepare("UPDATE produk SET updated_at = NOW() WHERE id_produk = ?")->execute([$id_produk]);

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Harga berhasil diperbarui'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
