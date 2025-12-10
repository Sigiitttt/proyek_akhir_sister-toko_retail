<?php
// server_pusat/api/auth.php

// Pastikan tidak ada output sebelum JSON
header('Content-Type: application/json');

// 1. Ambil API Key dari Header
$headers = getallheaders();
$api_key = null;

// Cek berbagai kemungkinan penulisan header (X-API-KEY, x-api-key, dll)
foreach ($headers as $key => $value) {
    if (strtoupper($key) === 'X-API-KEY') {
        $api_key = $value;
        break;
    }
}

// Fallback: Cek via URL param (untuk testing browser)
if (!$api_key && isset($_GET['key'])) {
    $api_key = $_GET['key'];
}

if (!$api_key) {
    echo json_encode(['status' => 'error', 'message' => 'API Key tidak ditemukan.']);
    exit;
}

// 2. Validasi ke Database
// Kita query manual disini biar variabel $toko_data tersedia GLOBAL di file yang meng-include ini
try {
    $stmtAuth = $db->prepare("SELECT id_toko, nama_toko, kode_toko FROM toko WHERE api_key = :key AND is_active = 1 LIMIT 1");
    $stmtAuth->execute([':key' => $api_key]);
    $toko_data = $stmtAuth->fetch(PDO::FETCH_ASSOC);

    if (!$toko_data) {
        echo json_encode(['status' => 'error', 'message' => 'API Key tidak valid atau toko nonaktif.']);
        exit;
    }

    // Jika berhasil, kode di bawah ini tidak dieksekusi, 
    // dan variabel $toko_data akan terbawa ke file get_products.php

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Auth Error: ' . $e->getMessage()]);
    exit;
}
?>