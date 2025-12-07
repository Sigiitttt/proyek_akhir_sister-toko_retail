<?php
// api/get_stok.php

require_once '../config/app.php';
require_once '../config/database.php';
require_once 'auth.php';

try {
    // Query langsung ke tabel stok_toko
    // Kita filter berdasarkan ID Toko yang sedang login ($toko_data['id_toko'])
    $query = "SELECT p.kode_produk, p.nama_produk, s.jumlah, s.last_update
              FROM stok_toko s
              JOIN produk p ON s.id_produk = p.id_produk
              WHERE s.id_toko = :id_toko";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':id_toko' => $toko_data['id_toko']]);
    $stok = $stmt->fetchAll();
    
    jsonResponse('success', 'Data stok toko berhasil diambil', $stok);

} catch (Exception $e) {
    jsonResponse('error', $e->getMessage(), null, 500);
}
?>