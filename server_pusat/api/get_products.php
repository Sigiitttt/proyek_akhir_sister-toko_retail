<?php
// api/get_products.php

require_once '../config/app.php';
require_once '../config/database.php';

// 1. Cek Keamanan (Auth)
// require_once 'auth.php'; // Script akan berhenti di sini jika auth gagal

require_once '../models/Produk.php';
$produkModel = new Produk($db);

// 2. Cek Parameter last_sync (Format: YYYY-MM-DD HH:MM:SS)
// Jika toko baru install, last_sync defaultnya tahun jebot (ambil semua data)
$last_sync = isset($_GET['last_sync']) ? $_GET['last_sync'] : '2000-01-01 00:00:00';

try {
    // 3. Ambil data dari Model
    $data_produk = $produkModel->getForSync($last_sync);
    
    // 4. Kirim Response JSON
    jsonResponse('success', 'Data produk berhasil diambil', [
        'total_data' => count($data_produk),
        'server_time' => date('Y-m-d H:i:s'), // Toko harus update last_sync-nya pakai jam ini
        'produk' => $data_produk
    ]);

} catch (Exception $e) {
    jsonResponse('error', $e->getMessage(), null, 500);
}
?>