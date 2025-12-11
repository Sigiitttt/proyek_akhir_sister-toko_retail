<?php
// 1. Mulai session (Wajib ada untuk mengakses session yang mau dihapus)
session_start();

// 2. Kosongkan semua variabel session
$_SESSION = [];

// 3. Hapus cookie session (Best Practice Keamanan)
// Ini memastikan ID session di browser benar-benar hangus
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan session di server
session_destroy();

// 5. Redirect ke halaman login
// Script ini otomatis mendeteksi apakah dia berada di Server Pusat atau Client Toko

if (file_exists('login.php')) {
    // Jika ada file login.php, berarti ini SERVER PUSAT
    header("Location: login.php");
} else {
    // Jika tidak ada, berarti ini CLIENT TOKO (Login ada di index.php)
    header("Location: index.php");
}

exit;
?>