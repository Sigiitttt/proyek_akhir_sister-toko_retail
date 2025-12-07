<?php
// config/app.php

// 1. Set Timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// 2. Helper untuk Response JSON (Dipakai di API)
function jsonResponse($status, $message, $data = null, $code = 200) {
    header("HTTP/1.1 $code");
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// 3. Helper untuk membersihkan input (Keamanan dasar)
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// 4. Base URL (Sesuaikan dengan folder project kamu)
// Jika di localhost/server_pusat, biarkan seperti ini
define('BASE_URL', 'http://localhost/server_pusat/'); 
?>