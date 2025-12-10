<?php
// api/post_transaksi.php

// 1. Matikan Error Reporting Teks (PENTING!)
// Agar jika ada warning PHP, tidak merusak struktur JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/app.php';
require_once '../config/database.php';

// 2. Cek Keamanan (Auth) - WAJIB AKTIF
// Script ini memvalidasi API Key dan membuat variabel $toko_data
require_once 'auth.php'; 

// Ambil Model
require_once '../models/Transaksi.php';
$trxModel = new Transaksi($db);

// 3. Ambil Data JSON Raw dari Client
$json_input = file_get_contents('php://input');
$data_transaksi = json_decode($json_input, true);

// Validasi Format Data
if (!$data_transaksi || !is_array($data_transaksi)) {
    jsonResponse('error', 'Format data JSON tidak valid atau kosong', null, 400);
}

try {
    // 4. Validasi Keamanan Ekstra:
    // Pastikan data yang dikirim dipaksa menggunakan ID Toko dari Token (bukan dari inputan JSON)
    // Ini mencegah Toko A mengirim data seolah-olah dari Toko B
    $id_toko_valid = $toko_data['id_toko'];

    foreach ($data_transaksi as &$trx) {
        $trx['id_toko'] = $id_toko_valid; 
    }
    
    // 5. Simpan ke Database via Model
    // Pastikan method 'simpanTransaksiSync' ada di models/Transaksi.php
    $trxModel->simpanTransaksiSync($data_transaksi);
    
    // 6. Sukses
    jsonResponse('success', 'Transaksi berhasil disinkronisasi ke pusat.', [
        'diterima' => count($data_transaksi)
    ]);

} catch (Exception $e) {
    // Jika ada error SQL (misal Duplicate Entry), tangkap disini
    jsonResponse('error', 'Server Error: ' . $e->getMessage(), null, 500);
}
?>