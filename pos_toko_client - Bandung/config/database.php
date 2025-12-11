<?php
$host = 'localhost';
// $db_name = 'cabang-sby'; // Pastikan database ini sudah dibuat di phpMyAdmin
$db_name = 'cabang-bandung'; // Pastikan database ini sudah dibuat di phpMyAdmin
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ];
    
    // Variabel $db_lokal ini yang akan dipakai di seluruh model Client
    $db_lokal = new PDO($dsn, $username, $password, $options);
    
} catch (\PDOException $e) {
    // Tampilan error sederhana jika DB lokal mati
    die("<div style='padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px; font-family:sans-serif;'>
            <strong>Error Database Lokal:</strong><br>
            Aplikasi Kasir tidak bisa terhubung ke database lokal.<br>
            Pastikan XAMPP (MySQL) sudah nyala dan database 'retail_client_db' sudah dibuat.<br>
            <small>Detail: " . $e->getMessage() . "</small>
         </div>");
}
?>