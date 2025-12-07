<?php
// api/update_stok.php

require_once '../config/app.php';
require_once '../config/database.php';
require_once 'auth.php';

// Menerima Input: { "kode_produk": "ABC", "qty_baru": 50 }
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['kode_produk']) || !isset($input['qty_baru'])) {
    jsonResponse('error', 'Parameter kode_produk dan qty_baru wajib ada', null, 400);
}

try {
    // 1. Cari ID Produk berdasarkan kode
    $stmtProd = $db->prepare("SELECT id_produk FROM produk WHERE kode_produk = ?");
    $stmtProd->execute([$input['kode_produk']]);
    $prod = $stmtProd->fetch();

    if (!$prod) {
        jsonResponse('error', 'Produk tidak ditemukan', null, 404);
    }

    // 2. Update Stok di tabel stok_toko
    // Menggunakan ON DUPLICATE KEY UPDATE agar jika belum ada record, dia buat baru
    $query = "INSERT INTO stok_toko (id_toko, id_produk, jumlah, last_update) 
              VALUES (:toko, :prod, :qty, NOW())
              ON DUPLICATE KEY UPDATE jumlah = :qty_update, last_update = NOW()";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':toko' => $toko_data['id_toko'],
        ':prod' => $prod['id_produk'],
        ':qty'  => $input['qty_baru'],
        ':qty_update' => $input['qty_baru']
    ]);

    jsonResponse('success', 'Stok berhasil diperbarui', [
        'produk' => $input['kode_produk'],
        'stok_sekarang' => $input['qty_baru']
    ]);
} catch (Exception $e) {
    jsonResponse('error', $e->getMessage(), null, 500);
}
