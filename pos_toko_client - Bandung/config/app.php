<?php
date_default_timezone_set('Asia/Jakarta');
define('BASE_API_URL', 'http://localhost/retail-sister/server_pusat/api/');
define('TOKO_API_KEY', '673271e6bfccbb9d5657e3f5b3de1d20');
define('ID_TOKO', 4);
define('BASE_URL', 'http://localhost/pos_toko_client/public/');

function callAPI($endpoint, $method = 'GET', $data = null) {
    $url = BASE_API_URL . $endpoint;
    
    // Inisialisasi cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout 10 detik (Biar gak hang kalau internet lemot)
    
    // Set Header (Kirim API Key)
    $headers = [
        "X-API-KEY: " . TOKO_API_KEY,
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Pengaturan Method (POST/GET)
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    // Eksekusi
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_msg = curl_error($ch);
    curl_close($ch);

    // Jika koneksi error (Internet mati / Server down)
    if ($response === false) {
        return [
            'status' => 'offline',
            'message' => 'Gagal terhubung ke pusat: ' . $error_msg
        ];
    }

    // Decode jawaban JSON dari server
    $result = json_decode($response, true);
    
    // Tambahkan info HTTP Code untuk debugging
    if (is_array($result)) {
        $result['http_code'] = $http_code;
    } else {
        // Jika server error tapi tidak balikin JSON (misal error PHP text)
        return [
            'status' => 'error', 
            'message' => 'Respon server tidak valid', 
            'raw' => $response
        ];
    }

    return $result;
}

// Fungsi Format Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi Redirect
function redirect($page) {
    header("Location: " . BASE_URL . $page);
    exit;
}
?>