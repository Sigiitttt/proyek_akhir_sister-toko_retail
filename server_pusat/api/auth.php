<?php
// api/auth.php
// Pastikan file ini di-include SETELAH config/database.php

header('Content-Type: application/json');

// 1. Cek Header untuk API Key
$headers = getallheaders();
$api_key = null;

if (isset($headers['X-API-KEY'])) {
    $api_key = $headers['X-API-KEY'];
} elseif (isset($_GET['key'])) {
    // Fallback: Bisa via URL param jika header susah (untuk testing)
    $api_key = $_GET['key'];
}

if (!$api_key) {
    jsonResponse('error', 'API Key tidak ditemukan. Akses ditolak.', null, 401);
}

// 2. Validasi ke Database
require_once '../models/Toko.php';
$tokoModel = new Toko($db);
$toko_data = $tokoModel->cekApiKey($api_key);

if (!$toko_data) {
    jsonResponse('error', 'API Key tidak valid.', null, 401);
}

// 3. Jika lolos, variabel $toko_data bisa dipakai oleh script utama
// $toko_data berisi ['id_toko' => 1, 'nama_toko' => '...', 'kode_toko' => '...']
?>