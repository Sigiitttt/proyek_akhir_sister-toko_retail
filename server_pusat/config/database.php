<?php
// config/database.php

$host = 'localhost';
$db_name = 'retail_pusat'; // Sesuai DB kamu
$username = 'root';        // Sesuaikan user db lokal
$password = '';            // Sesuaikan password db lokal

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    
    // Opsi agar error terlihat jelas dan data ditarik sebagai Array Associative
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $db = new PDO($dsn, $username, $password, $options);
    
} catch (\PDOException $e) {
    // Jika koneksi gagal, kembalikan JSON error (karena ini akan diakses API juga)
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Koneksi Database Gagal: ' . $e->getMessage()]);
    exit;
}
?>