<?php
// pos_toko_client/controllers/TransaksiController.php

class TransaksiController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * 1. Cari Produk (Untuk Search Bar Kasir)
     * Mengambil data dari tabel 'produk' lokal
     */
    public function cariProduk($keyword = '') {
        try {
            $sql = "SELECT * FROM produk";
            
            // Filter jika ada keyword pencarian
            if (!empty($keyword)) {
                $sql .= " WHERE nama_produk LIKE :cari OR kode_produk LIKE :cari";
            }
            
            $sql .= " LIMIT 20"; // Batasi agar query ringan

            $stmt = $this->db->prepare($sql);
            
            if (!empty($keyword)) {
                $stmt->bindValue(':cari', "%$keyword%");
            }
            
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            return []; // Kembalikan array kosong jika error
        }
    }

    /**
     * 2. Simpan Transaksi (INTI SISTEM TERDISTRIBUSI)
     * Logic: 
     * - Hitung Total
     * - Buat ID Unik (UUID String) agar tidak bentrok dengan toko lain
     * - Insert Header & Detail ke DB Lokal
     * - Status 'is_synced' = 0 (Pending)
     */
    public function simpanTransaksi($cart, $bayar, $kasir_id) {
        // Validasi Keranjang
        if (empty($cart)) {
            return ['status' => 'error', 'message' => 'Keranjang belanja kosong!'];
        }

        try {
            // A. Mulai Database Transaction (Safety)
            $this->db->beginTransaction();

            // B. Hitung Total & Kembalian
            $total_belanja = 0;
            foreach ($cart as $item) {
                $total_belanja += ($item['harga'] * $item['qty']);
            }

            $kembalian = $bayar - $total_belanja;
            
            // Validasi Pembayaran
            if ($kembalian < 0) {
                // Batalkan transaksi database
                $this->db->rollBack(); 
                return ['status' => 'error', 'message' => 'Uang pembayaran kurang!'];
            }

            // C. Generate ID Transaksi Unik (Distributed ID)
            // Format: TRX-[ID_TOKO]-[TIMESTAMP]-[RANDOM]
            // Contoh: TRX-1-20231207123000-589
            // Pastikan ID_TOKO ada di config/app.php
            $toko_id  = defined('ID_TOKO') ? ID_TOKO : 'LOKAL';
            $waktu    = date('YmdHis');
            $random   = rand(100, 999);
            $id_trx   = "TRX-{$toko_id}-{$waktu}-{$random}";
            
            // Generate Nomor Struk (Untuk antrian harian)
            $no_struk = "STR-" . date('His') . "-" . rand(10,99);

            // D. Insert Header Transaksi
            // Penting: is_synced diset 0 (Belum terkirim ke pusat)
            $sqlHeader = "INSERT INTO transaksi 
                          (id_transaksi, no_struk, total_transaksi, bayar, kembalian, waktu_transaksi, kasir_id, is_synced) 
                          VALUES 
                          (:id, :struk, :total, :bayar, :kembali, NOW(), :kasir, 0)";
            
            $stmtHeader = $this->db->prepare($sqlHeader);
            $stmtHeader->execute([
                ':id'      => $id_trx,
                ':struk'   => $no_struk,
                ':total'   => $total_belanja,
                ':bayar'   => $bayar,
                ':kembali' => $kembalian,
                ':kasir'   => $kasir_id
            ]);

            // E. Insert Detail Items (Looping barang di keranjang)
            $sqlDetail = "INSERT INTO detail_transaksi 
                          (id_transaksi, id_produk, qty, harga_satuan, subtotal) 
                          VALUES (:idtrx, :idprod, :qty, :harga, :subtotal)";
            
            $stmtDetail = $this->db->prepare($sqlDetail);

            foreach ($cart as $item) {
                $subtotal = $item['harga'] * $item['qty'];
                $stmtDetail->execute([
                    ':idtrx'    => $id_trx,
                    ':idprod'   => $item['id'],
                    ':qty'      => $item['qty'],
                    ':harga'    => $item['harga'],
                    ':subtotal' => $subtotal
                ]);
            }

            // F. Commit (Simpan Permanen jika semua langkah di atas sukses)
            $this->db->commit();

            return [
                'status'       => 'success',
                'message'      => 'Transaksi Berhasil Disimpan',
                'id_transaksi' => $id_trx,
                'kembalian'    => $kembalian
            ];

        } catch (Exception $e) {
            // G. Rollback (Batalkan semua jika ada error di tengah jalan)
            $this->db->rollBack();
            return [
                'status'  => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ];
        }
    }
}
?>