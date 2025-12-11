<?php
// pos_toko_client/models/TransaksiModel.php

class TransaksiModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Simpan Transaksi Baru (Header + Detail)
     * Menggunakan Transaction Atomic (Begin -> Commit)
     */
    public function create($id_trx, $no_struk, $total, $bayar, $kembalian, $kasir_id, $items) {
        try {
            $this->db->beginTransaction();

            // 1. Insert Header
            $sqlHeader = "INSERT INTO transaksi 
                          (id_transaksi, no_struk, total_transaksi, bayar, kembalian, waktu_transaksi, kasir_id, is_synced) 
                          VALUES 
                          (:id, :struk, :total, :bayar, :kembali, NOW(), :kasir, 0)";
            
            $stmtHeader = $this->db->prepare($sqlHeader);
            $stmtHeader->execute([
                ':id'      => $id_trx,
                ':struk'   => $no_struk,
                ':total'   => $total,
                ':bayar'   => $bayar,
                ':kembali' => $kembalian,
                ':kasir'   => $kasir_id
            ]);

            // 2. Insert Detail Items
            $sqlDetail = "INSERT INTO detail_transaksi 
                          (id_transaksi, id_produk, qty, harga_satuan, subtotal) 
                          VALUES (:idtrx, :idprod, :qty, :harga, :subtotal)";
            $stmtDetail = $this->db->prepare($sqlDetail);

            foreach ($items as $item) {
                $stmtDetail->execute([
                    ':idtrx'    => $id_trx,
                    ':idprod'   => $item['id'],
                    ':qty'      => $item['qty'],
                    ':harga'    => $item['harga'],
                    ':subtotal' => $item['harga'] * $item['qty']
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Ambil transaksi pending (belum dikirim ke pusat)
     * Dipanggil di: SyncController
     */
    public function getPending($limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM transaksi WHERE is_synced = 0 LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ambil detail item berdasarkan ID Transaksi
     * Dipanggil di: SyncController (untuk payload JSON)
     */
    public function getDetailItems($id_trx) {
        $stmt = $this->db->prepare("SELECT id_produk, qty, harga_satuan, subtotal FROM detail_transaksi WHERE id_transaksi = :id");
        $stmt->execute([':id' => $id_trx]);
        return $stmt->fetchAll();
    }

    /**
     * Tandai transaksi sebagai SUDAH TERKIRIM (Synced)
     * Dipanggil di: SyncController (setelah sukses upload)
     */
    public function markAsSynced($id_transaksi_array) {
        if (empty($id_transaksi_array)) return false;

        // Buat placeholder (?,?,?) untuk query IN
        $placeholders = implode(',', array_fill(0, count($id_transaksi_array), '?'));
        
        $sql = "UPDATE transaksi SET is_synced = 1 WHERE id_transaksi IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($id_transaksi_array);
    }

    /**
     * Hitung jumlah pending
     * Dipanggil di: Dashboard / Sync UI
     */
    public function countPending() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM transaksi WHERE is_synced = 0");
        return $stmt->fetchColumn();
    }
}
?>