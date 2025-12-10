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
// Cek dulu: Apakah ini untuk Server Pusat atau Client?

// Jika file ini di SERVER PUSAT, arahkan ke login.php
// Jika file ini di CLIENT TOKO, arahkan ke index.php (karena login kasir ada di index)

// Opsional: Deteksi otomatis atau set manual
if (file_exists('login.php')) {
    header("Location: login.php");
} else {
    header("Location: index.php");
}

exit;
?>