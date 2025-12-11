<?php
// pos_toko_client/sync/push_transaksi.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

try {
    // 1. CARI TRANSAKSI PENDING (JOIN KE TABEL USERS)
    // Kita perlu nama kasir, bukan cuma ID-nya.
    $sql = "SELECT t.*, u.nama_lengkap as nama_kasir_asli 
            FROM transaksi t
            LEFT JOIN users u ON t.kasir_id = u.id_user 
            WHERE t.is_synced = 0 
            LIMIT 50"; // Batasi 50 biar ringan

    $stmt = $db_lokal->query($sql);
    $pendingTrx = $stmt->fetchAll();

    if (count($pendingTrx) > 0) {

        // 2. SUSUN STRUKTUR DATA JSON
        // API Pusat butuh struktur: [ {header..., items: [detail...]} ]
        $payload = [];

        // Siapkan query detail
        $sqlDetail = "SELECT id_produk, qty, harga_satuan, subtotal 
                      FROM detail_transaksi WHERE id_transaksi = :id_trx";
        $stmtDetail = $db_lokal->prepare($sqlDetail);

        foreach ($pendingTrx as $trx) {
            // Ambil detail item per transaksi
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
                
                // INI BAGIAN PENTING: Kirim Nama Kasir ke Pusat
                'nama_kasir'        => $trx['nama_kasir_asli'] ?? 'Kasir Tidak Dikenal', 
                
                'waktu_transaksi'   => $trx['waktu_transaksi'],
                'items'             => $items
            ];
        }

        // 3. KIRIM KE API PUSAT (POST)
        // Pastikan server pusat sudah punya kolom 'nama_kasir' di tabel transaksi
        $response = callAPI('post_transaksi.php', 'POST', $payload);

        // 4. PROSES JAWABAN
        if (isset($response['status']) && $response['status'] == 'success') {

            // Tandai transaksi sebagai SUDAH TERKIRIM (is_synced = 1)
            $ids_terkirim = array_column($payload, 'id_transaksi');

            // Buat placeholder (?,?,?) untuk query IN
            $placeholders = implode(',', array_fill(0, count($ids_terkirim), '?'));

            $sqlUpdate = "UPDATE transaksi SET is_synced = 1 WHERE id_transaksi IN ($placeholders)";
            $stmtUpdate = $db_lokal->prepare($sqlUpdate);
            $stmtUpdate->execute($ids_terkirim);

            echo "Berhasil: " . count($ids_terkirim) . " transaksi terupload ke pusat.";
        } else {
            echo "Gagal Upload: " . ($response['message'] ?? 'API Error / Respon tidak valid');
        }

    } else {
        echo "Info: Data Kosong (Tidak ada transaksi pending).";
    }

} catch (Exception $e) {
    echo "Error System: " . $e->getMessage();
}
?>