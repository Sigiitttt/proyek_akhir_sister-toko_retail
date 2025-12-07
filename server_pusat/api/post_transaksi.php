<?php
// api/post_transaksi.php

require_once '../config/app.php';
require_once '../config/database.php';

// 1. Cek Keamanan (Auth)
require_once 'auth.php'; // Kita dapat $toko_data dari sini

require_once '../models/Transaksi.php';
$trxModel = new Transaksi($db);

// 2. Ambil Data JSON Raw dari Body Request
$json_input = file_get_contents('php://input');
$data_transaksi = json_decode($json_input, true);

if (!$data_transaksi || !is_array($data_transaksi)) {
    jsonResponse('error', 'Format data JSON tidak valid', null, 400);
}

try {
    // 3. Validasi Keamanan Ekstra:
    // Pastikan data yang dikirim benar-benar milik toko yang sedang login
    foreach ($data_transaksi as &$trx) {
        $trx['id_toko'] = $toko_data['id_toko']; // Paksa ID Toko sesuai Token
    }
    
    // 4. Simpan ke Database via Model
    $trxModel->simpanTransaksiDariToko($data_transaksi);
    
    // 5. Sukses
    jsonResponse('success', 'Transaksi berhasil disinkronisasi ke pusat.', [
        'diterima' => count($data_transaksi)
    ]);

} catch (Exception $e) {
    jsonResponse('error', $e->getMessage(), null, 500);
}
?>