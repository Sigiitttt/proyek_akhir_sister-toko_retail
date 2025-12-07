<?php
// pos_toko_client/controllers/AuthController.php

class AuthController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Proses Login Kasir
     * Mengecek username & password di database lokal
     */
    public function login($username, $password) {
        try {
            // 1. Cari user berdasarkan username
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            // 2. Verifikasi Password
            if ($user) {
                // CATATAN PENTING:
                // Jika di dummy data passwordnya plain text (misal: "123456"), gunakan perbandingan langsung:
                // if ($password === $user['password']) { ... }
                
                // Tapi jika production, gunakan password_verify() (Hash):
                // if (password_verify($password, $user['password'])) { ... }

                // Kita pakai logic sederhana (plain text) agar sesuai dengan dummy data SQL sebelumnya
                if ($password === $user['password']) {
                    
                    // Set Session
                    if (session_status() == PHP_SESSION_NONE) session_start();
                    
                    $_SESSION['kasir_logged_in'] = true;
                    $_SESSION['kasir_id'] = $user['id_user'];
                    $_SESSION['kasir_nama'] = $user['nama_lengkap'];

                    return ['status' => 'success', 'message' => 'Login berhasil'];
                }
            }

            return ['status' => 'error', 'message' => 'Username atau Password salah'];

        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }

    /**
     * Proses Logout
     */
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        session_destroy();
        return true;
    }

    /**
     * Middleware: Cek apakah user sudah login?
     * Dipanggil di atas halaman dashboard/kasir
     */
    public function checkAccess() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['kasir_logged_in']) || $_SESSION['kasir_logged_in'] !== true) {
            header("Location: index.php");
            exit;
        }
    }
}
?>