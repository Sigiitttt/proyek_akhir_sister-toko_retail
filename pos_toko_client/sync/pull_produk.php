<?php
// pos_toko_client/sync/pull_produk.php

// Mencegah akses langsung jika tidak di-include (Opsional)
// if (!defined('BASE_API_URL')) exit('No direct script access allowed');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

try {
    // 1. CEK WAKTU TERAKHIR SYNC DI LOKAL
    // Kita cari tanggal 'updated_at_pusat' yang paling baru di tabel produk lokal
    $stmt = $db_lokal->query("SELECT MAX(updated_at_pusat) FROM produk");
    $last_sync = $stmt->fetchColumn();

    // Jika belum pernah sync, set ke tahun lama biar ketarik semua
    if (!$last_sync) {
        $last_sync = '2000-01-01 00:00:00';
    }

    // 2. PANGGIL API PUSAT (GET)
    // Kirim parameter ?last_sync=...
    $endpoint = "get_products.php?last_sync=" . urlencode($last_sync);
    $response = callAPI($endpoint, 'GET');

    // 3. PROSES DATA YANG DITERIMA
    if (isset($response['status']) && $response['status'] == 'success') {
        $dataProduk = $response['data']['produk'];
        $totalData = count($dataProduk);

        if ($totalData > 0) {
            $db_lokal->beginTransaction();

            // Query Insert / Update (Upsert)
            // Jika ID sudah ada, update datanya. Jika belum, insert baru.
            $sql = "INSERT INTO produk (id_produk, kode_produk, nama_produk, satuan, harga_jual, updated_at_pusat) 
                    VALUES (:id, :kode, :nama, :satuan, :harga, :updated)
                    ON DUPLICATE KEY UPDATE 
                    kode_produk = :kode, 
                    nama_produk = :nama, 
                    satuan = :satuan, 
                    harga_jual = :harga, 
                    updated_at_pusat = :updated";

            $stmt = $db_lokal->prepare($sql);

            foreach ($dataProduk as $p) {
                $stmt->execute([
                    ':id'      => $p['id_produk'],
                    ':kode'    => $p['kode_produk'],
                    ':nama'    => $p['nama_produk'],
                    ':satuan'  => $p['satuan'],
                    ':harga'   => $p['harga_jual'], // Harga aktif dari join tabel harga di pusat
                    ':updated' => $p['updated_at']  // Waktu update dari pusat
                ]);
            }

            $db_lokal->commit();
            echo "Berhasil: $totalData produk diperbarui.";
        } else {
            echo "Info: Data produk sudah mutakhir (Tidak ada perubahan baru).";
        }

    } else {
        // DEBUGGING: Tampilkan raw response biar tahu salahnya dimana
        echo "Gagal: " . ($response['message'] ?? 'Respon tidak valid');
        echo "<br><hr><strong>Raw Response dari Server:</strong><br>";
        echo "<pre>" . htmlspecialchars($response['raw'] ?? 'KOSONG') . "</pre>";
    }

} catch (Exception $e) {
    if ($db_lokal->inTransaction()) $db_lokal->rollBack();
    echo "Error System: " . $e->getMessage();
}
?>