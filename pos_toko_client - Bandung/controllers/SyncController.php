<?php
// pos_toko_client/controllers/SyncController.php

class SyncController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * 1. DOWNLOAD PRODUK (PULL)
     * Mengambil data produk + STOK JATAH dari server pusat
     */
    public function syncProduk() {
        try {
            // A. Cek kapan terakhir kali update di DB Lokal
            $stmt = $this->db->query("SELECT MAX(updated_at_pusat) FROM produk");
            $last_sync = $stmt->fetchColumn();

            // Default jika belum pernah sync
            if (!$last_sync) {
                $last_sync = '2000-01-01 00:00:00';
            }

            // B. Panggil API Pusat
            $endpoint = "get_products.php?last_sync=" . urlencode($last_sync);
            $response = callAPI($endpoint, 'GET');

            // C. Validasi Respon API
            if (isset($response['status']) && $response['status'] == 'success') {
                $dataProduk = $response['data']['produk'];
                $totalData = count($dataProduk);

                if ($totalData > 0) {
                    // Mulai Transaksi Database
                    $this->db->beginTransaction();

                    $sql = "INSERT INTO produk (id_produk, kode_produk, nama_produk, satuan, harga_jual, stok_lokal, updated_at_pusat) 
                            VALUES (:id, :kode, :nama, :satuan, :harga, :stok, :updated)
                            ON DUPLICATE KEY UPDATE 
                            kode_produk = :kode, 
                            nama_produk = :nama, 
                            satuan      = :satuan, 
                            harga_jual  = :harga, 
                            stok_lokal  = :stok, 
                            updated_at_pusat = :updated";

                    $stmt = $this->db->prepare($sql);

                    foreach ($dataProduk as $p) {
                        // LOGIC MAPPING STOK:
                        // Prioritaskan 'stok_toko' (jatah khusus), lalu 'stok_global'
                        $stok_fix = 0;
                        if (isset($p['stok_toko'])) {
                            $stok_fix = $p['stok_toko']; 
                        } elseif (isset($p['stok_global'])) {
                            $stok_fix = $p['stok_global'];
                        } elseif (isset($p['jumlah'])) {
                            $stok_fix = $p['jumlah'];
                        }

                        $stmt->execute([
                            ':id'      => $p['id_produk'],
                            ':kode'    => $p['kode_produk'],
                            ':nama'    => $p['nama_produk'],
                            ':satuan'  => $p['satuan'],
                            ':harga'   => $p['harga_jual'],
                            ':stok'    => $stok_fix,
                            ':updated' => $p['updated_at']
                        ]);
                    }

                    $this->db->commit();
                    return ['status' => 'success', 'message' => "Berhasil memperbarui $totalData produk & stok."];
                } else {
                    return ['status' => 'success', 'message' => "Data produk sudah mutakhir."];
                }
            } else {
                // Jika API mengembalikan error
                return ['status' => 'error', 'message' => "Gagal koneksi API: " . ($response['message'] ?? 'Respon tidak valid')];
            }

        } catch (Exception $e) {
            // Rollback jika ada error insert
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => 'error', 'message' => "Error Lokal: " . $e->getMessage()];
        }
    }

    /**
     * 2. UPLOAD TRANSAKSI (PUSH)
     * Mengirim data transaksi offline ke server pusat
     */
    public function uploadTransaksi() {
        try {
            // Ambil transaksi pending (Join Users untuk dapat nama kasir)
            $sql = "SELECT t.*, u.nama_lengkap as nama_kasir_asli 
                    FROM transaksi t
                    LEFT JOIN users u ON t.kasir_id = u.id_user 
                    WHERE t.is_synced = 0 LIMIT 50";
            
            $stmt = $this->db->query($sql);
            $pendingTrx = $stmt->fetchAll();

            if (count($pendingTrx) == 0) {
                return ['status' => 'info', 'message' => "Tidak ada transaksi pending."];
            }

            $payload = [];
            $sqlDetail = "SELECT id_produk, qty, harga_satuan, subtotal FROM detail_transaksi WHERE id_transaksi = :id_trx";
            $stmtDetail = $this->db->prepare($sqlDetail);

            foreach ($pendingTrx as $trx) {
                $stmtDetail->execute([':id_trx' => $trx['id_transaksi']]);
                $items = $stmtDetail->fetchAll();

                $payload[] = [
                    'id_transaksi'      => $trx['id_transaksi'],
                    'id_toko'           => defined('ID_TOKO') ? ID_TOKO : 1,
                    'no_struk'          => $trx['no_struk'],
                    'total_transaksi'   => $trx['total_transaksi'],
                    'bayar'             => $trx['bayar'],
                    'kembalian'         => $trx['kembalian'],
                    'metode_pembayaran' => $trx['metode_pembayaran'],
                    'kasir_id'          => $trx['kasir_id'],
                    'nama_kasir'        => $trx['nama_kasir_asli'] ?? 'Kasir',
                    'waktu_transaksi'   => $trx['waktu_transaksi'],
                    'items'             => $items
                ];
            }

            // Kirim ke API Pusat
            $response = callAPI('post_transaksi.php', 'POST', $payload);

            if (isset($response['status']) && $response['status'] == 'success') {
                // Update status sync menjadi 1
                $ids_terkirim = array_column($payload, 'id_transaksi');
                $placeholders = implode(',', array_fill(0, count($ids_terkirim), '?'));
                
                $sqlUpdate = "UPDATE transaksi SET is_synced = 1 WHERE id_transaksi IN ($placeholders)";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute($ids_terkirim);

                return ['status' => 'success', 'message' => count($ids_terkirim) . " transaksi berhasil diupload."];
            } else {
                return ['status' => 'error', 'message' => "Gagal Upload: " . ($response['message'] ?? 'API Error')];
            }

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => "Error Lokal: " . $e->getMessage()];
        }
    }
}
?>