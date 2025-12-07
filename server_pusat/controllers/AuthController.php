<?php
// controllers/AuthController.php

class AuthController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // 1. Proses Login
    public function login($username, $password) {
        // Cari user berdasarkan username
        $query = "SELECT * FROM admin WHERE username = :user LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user' => $username]);
        $user = $stmt->fetch();

        // Jika user ada
        if ($user) {
            // Verifikasi Password (Hash)
            // Catatan: Di SQL dummy data saya pakai hash bcrypt ($2y$10$...)
            if (password_verify($password, $user['password'])) {
                
                // Set Session
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id_admin'];
                $_SESSION['admin_nama'] = $user['nama_admin'];

                return ['status' => 'success', 'message' => 'Login berhasil'];
            }
        }

        return ['status' => 'error', 'message' => 'Username atau Password salah!'];
    }

    // 2. Proses Logout
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy(); // Hapus semua session
        return true;
    }

    // 3. Cek apakah sudah login? (Middleware sederhana)
    // Dipasang di setiap halaman admin (dashboard, produk, dll)
    public function checkAccess() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            // Jika belum login, tendang ke halaman login
            header("Location: login.php");
            exit;
        }
    }
}
?>