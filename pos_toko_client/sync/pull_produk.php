<?php
// pos_toko_client/sync/pull_produk.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

try {
    // 1. CEK WAKTU TERAKHIR SYNC DI LOKAL
    $stmt = $db_lokal->query("SELECT MAX(updated_at_pusat) FROM produk");
    $last_sync = $stmt->fetchColumn();

    // Jika belum pernah sync, set ke tahun lama
    if (!$last_sync) {
        $last_sync = '2000-01-01 00:00:00';
    }

    // 2. PANGGIL API PUSAT (GET)
    $endpoint = "get_products.php?last_sync=" . urlencode($last_sync);
    $response = callAPI($endpoint, 'GET');

    // 3. PROSES DATA YANG DITERIMA
    if (isset($response['status']) && $response['status'] == 'success') {
        $dataProduk = $response['data']['produk'];
        $totalData = count($dataProduk);

        if ($totalData > 0) {
            $db_lokal->beginTransaction();

            // ========================================================
            // PERBAIKAN SQL: Menambahkan kolom 'stok_lokal'
            // ========================================================
            $sql = "INSERT INTO produk (id_produk, kode_produk, nama_produk, satuan, harga_jual, stok_lokal, updated_at_pusat) 
                    VALUES (:id, :kode, :nama, :satuan, :harga, :stok, :updated)
                    ON DUPLICATE KEY UPDATE 
                    kode_produk = :kode, 
                    nama_produk = :nama, 
                    satuan = :satuan, 
                    harga_jual = :harga, 
                    stok_lokal = :stok,  /* <--- Ini wajib ada agar stok ter-update */
                    updated_at_pusat = :updated";

            $stmt = $db_lokal->prepare($sql);

            foreach ($dataProduk as $p) {
                // TANGKAP STOK DARI SERVER
                // Server mengirim key 'stok_toko'. Kita tangkap dan masukkan ke variabel.
                $stok_fix = 0;
                if (isset($p['stok_toko'])) {
                    $stok_fix = $p['stok_toko'];
                } elseif (isset($p['stok_global'])) {
                    $stok_fix = $p['stok_global'];
                }

                $stmt->execute([
                    ':id'      => $p['id_produk'],
                    ':kode'    => $p['kode_produk'],
                    ':nama'    => $p['nama_produk'],
                    ':satuan'  => $p['satuan'],
                    ':harga'   => $p['harga_jual'],
                    ':stok'    => $stok_fix,        // <--- Masukkan nilai stok ke database
                    ':updated' => $p['updated_at']
                ]);
            }

            $db_lokal->commit();
            echo "Berhasil: $totalData produk & stok diperbarui.";
        } else {
            echo "Info: Data produk sudah mutakhir (Tidak ada perubahan baru).";
        }

    } else {
        // DEBUGGING: Tampilkan raw response jika error
        echo "Gagal: " . ($response['message'] ?? 'Respon tidak valid');
        if(isset($response['raw'])) {
            echo "<br><hr><strong>Raw Response dari Server:</strong><br>";
            echo "<pre>" . htmlspecialchars($response['raw']) . "</pre>";
        }
    }

} catch (Exception $e) {
    if ($db_lokal->inTransaction()) $db_lokal->rollBack();
    echo "Error System: " . $e->getMessage();
}

// // DEBUGGING MANUAL (Agar terlihat di bawah halaman)
// if (isset($response)) {
//     echo "<hr><h3>DEBUG HASIL JSON SERVER:</h3>";
//     echo "<pre>";
//     print_r($response); 
//     echo "</pre>";
// }
?>