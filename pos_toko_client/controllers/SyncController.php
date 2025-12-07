<?php
// pos_toko_client/controllers/SyncController.php

class SyncController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * 1. DOWNLOAD PRODUK (PULL)
     * Mengambil data produk terbaru dari server pusat
     */
    public function syncProduk() {
        try {
            // A. Cek kapan terakhir kali update
            $stmt = $this->db->query("SELECT MAX(updated_at_pusat) FROM produk");
            $last_sync = $stmt->fetchColumn();

            // Default jika belum pernah sync
            if (!$last_sync) {
                $last_sync = '2000-01-01 00:00:00';
            }

            // B. Panggil API Pusat (Menggunakan fungsi callAPI dari config/app.php)
            $endpoint = "get_products.php?last_sync=" . urlencode($last_sync);
            $response = callAPI($endpoint, 'GET');

            // C. Proses Data
            if (isset($response['status']) && $response['status'] == 'success') {
                $dataProduk = $response['data']['produk'];
                $totalData = count($dataProduk);

                if ($totalData > 0) {
                    $this->db->beginTransaction();

                    // Query Upsert (Insert atau Update jika ada)
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

                    $this->db->commit();
                    return ['status' => 'success', 'message' => "Berhasil memperbarui $totalData produk."];
                } else {
                    return ['status' => 'success', 'message' => "Data produk sudah mutakhir."];
                }
            } else {
                return ['status' => 'error', 'message' => "Gagal koneksi API: " . ($response['message'] ?? 'Unknown error')];
            }

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => 'error', 'message' => "Error Lokal: " . $e->getMessage()];
        }
    }

    /**
     * 2. UPLOAD TRANSAKSI (PUSH)
     * Mengirim transaksi offline ke server pusat
     */
    public function uploadTransaksi() {
        try {
            // A. Ambil transaksi yang belum di-sync (Limit 50 biar ringan)
            $stmt = $this->db->query("SELECT * FROM transaksi WHERE is_synced = 0 LIMIT 50");
            $pendingTrx = $stmt->fetchAll();

            if (count($pendingTrx) == 0) {
                return ['status' => 'info', 'message' => "Tidak ada transaksi pending."];
            }

            // B. Susun Payload JSON (Header + Detail)
            $payload = [];
            $sqlDetail = "SELECT id_produk, qty, harga_satuan, subtotal 
                          FROM detail_transaksi WHERE id_transaksi = :id_trx";
            $stmtDetail = $this->db->prepare($sqlDetail);

            foreach ($pendingTrx as $trx) {
                // Ambil item belanjaan
                $stmtDetail->execute([':id_trx' => $trx['id_transaksi']]);
                $items = $stmtDetail->fetchAll();

                $payload[] = [
                    'id_transaksi'    => $trx['id_transaksi'],
                    'id_toko'         => defined('ID_TOKO') ? ID_TOKO : 1,
                    'no_struk'        => $trx['no_struk'],
                    'total_transaksi' => $trx['total_transaksi'],
                    'bayar'           => $trx['bayar'],
                    'kembalian'       => $trx['kembalian'],
                    'waktu_transaksi' => $trx['waktu_transaksi'],
                    'items'           => $items
                ];
            }

            // C. Kirim ke API Pusat
            $response = callAPI('post_transaksi.php', 'POST', $payload);

            // D. Update Status Lokal jika Sukses
            if (isset($response['status']) && $response['status'] == 'success') {
                $ids_terkirim = array_column($payload, 'id_transaksi');
                
                // Update status batch
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