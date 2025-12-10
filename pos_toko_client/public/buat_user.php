<?php
require_once '../config/database.php';

// GANTI DATA DISINI
$username = 'kasir_baru';
$password = '123456'; 
$nama     = 'Kasir Percobaan';

try {
    // Cek dulu apakah username sudah ada
    $cek = $db_lokal->prepare("SELECT id_user FROM users WHERE username = ?");
    $cek->execute([$username]);
    
    if ($cek->rowCount() > 0) {
        echo "Gagal: Username '$username' sudah ada!";
    } else {
        // Insert User Baru
        $sql = "INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)";
        $stmt = $db_lokal->prepare($sql);
        $stmt->execute([$username, $password, $nama]);
        
        echo "✅ Sukses! User <b>$username</b> berhasil dibuat.<br>";
        echo "Password: <b>$password</b>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>