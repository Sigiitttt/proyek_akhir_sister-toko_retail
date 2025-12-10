<?php
// pos_toko_client/controllers/TransaksiController.php

class TransaksiController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * 1. Cari Produk (Untuk Search Bar Kasir)
     */
    public function cariProduk($keyword = '') {
        try {
            $sql = "SELECT * FROM produk";
            
            if (!empty($keyword)) {
                $sql .= " WHERE nama_produk LIKE :cari OR kode_produk LIKE :cari";
            }
            
            $sql .= " LIMIT 20"; 

            $stmt = $this->db->prepare($sql);
            
            if (!empty($keyword)) {
                $stmt->bindValue(':cari', "%$keyword%");
            }
            
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * 2. Simpan Transaksi
     * Logic: Hitung Total -> Validasi -> Generate ID -> Insert Header -> Insert Detail -> KURANGI STOK
     */
    public function simpanTransaksi($cart, $bayar, $kasir_id, $metode = 'Tunai') {
        // Validasi Keranjang
        if (empty($cart)) {
            return ['status' => 'error', 'message' => 'Keranjang belanja kosong!'];
        }

        try {
            // A. Mulai Database Transaction (Safety)
            $this->db->beginTransaction();

            // B. Hitung Total Belanja
            $total_belanja = 0;
            foreach ($cart as $item) {
                $total_belanja += ($item['harga'] * $item['qty']);
            }

            // Validasi Pembayaran
            if ($metode != 'Tunai') {
                $bayar = $total_belanja; // Auto lunas jika non-tunai
            }

            $kembalian = $bayar - $total_belanja;
            
            if ($metode == 'Tunai' && $kembalian < 0) {
                $this->db->rollBack(); 
                return ['status' => 'error', 'message' => 'Uang pembayaran kurang!'];
            }

            // C. Generate ID Transaksi Unik
            $toko_id  = defined('ID_TOKO') ? ID_TOKO : 'LOKAL';
            $waktu    = date('YmdHis');
            $random   = rand(100, 999);
            $id_trx   = "TRX-{$toko_id}-{$waktu}-{$random}";
            $no_struk = "STR-" . date('ymd') . rand(1000,9999);

            // D. Insert Header Transaksi
            $sqlHeader = "INSERT INTO transaksi 
                          (id_transaksi, no_struk, total_transaksi, bayar, kembalian, metode_pembayaran, waktu_transaksi, kasir_id, is_synced) 
                          VALUES 
                          (:id, :struk, :total, :bayar, :kembali, :metode, NOW(), :kasir, 0)";
            
            $stmtHeader = $this->db->prepare($sqlHeader);
            $stmtHeader->execute([
                ':id'      => $id_trx,
                ':struk'   => $no_struk,
                ':total'   => $total_belanja,
                ':bayar'   => $bayar,
                ':kembali' => $kembalian,
                ':metode'  => $metode,
                ':kasir'   => $kasir_id
            ]);

            // E. Insert Detail Items & KURANGI STOK LOKAL
            $sqlDetail = "INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga_satuan, subtotal) VALUES (:idtrx, :idprod, :qty, :harga, :subtotal)";
            $stmtDetail = $this->db->prepare($sqlDetail);

            // Query Kurangi Stok
            $sqlStok = "UPDATE produk SET stok_lokal = stok_lokal - :qty WHERE id_produk = :idprod";
            $stmtStok = $this->db->prepare($sqlStok);

            foreach ($cart as $item) {
                $subtotal = $item['harga'] * $item['qty'];
                
                // 1. Simpan Detail
                $stmtDetail->execute([
                    ':idtrx'    => $id_trx,
                    ':idprod'   => $item['id'],
                    ':qty'      => $item['qty'],
                    ':harga'    => $item['harga'],
                    ':subtotal' => $subtotal
                ]);

                // 2. Kurangi Stok
                $stmtStok->execute([
                    ':qty'    => $item['qty'],
                    ':idprod' => $item['id']
                ]);
            }

            // F. Commit (Simpan Permanen)
            $this->db->commit();

            return [
                'status'       => 'success',
                'message'      => 'Transaksi Berhasil Disimpan',
                'id_transaksi' => $id_trx,
                'kembalian'    => $kembalian
            ];

        } catch (Exception $e) {
            // G. Rollback jika error
            $this->db->rollBack();
            return [
                'status'  => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ];
        }
    }
}
?>