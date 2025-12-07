<?php
// models/Admin.php

class Admin {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Cari admin berdasarkan Username (Untuk Login)
    public function getByUsername($username) {
        $query = "SELECT * FROM admin WHERE username = :user LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user' => $username]);
        return $stmt->fetch(); // Return row array atau false
    }

    // Tambah Admin Baru (Jika nanti butuh fitur register admin lain)
    public function create($nama, $username, $password_raw) {
        // Hash password sebelum simpan
        $hash = password_hash($password_raw, PASSWORD_BCRYPT);

        $query = "INSERT INTO admin (username, password, nama_admin) 
                  VALUES (:user, :pass, :nama)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':user' => $username,
            ':pass' => $hash,
            ':nama' => $nama
        ]);
    }

    // Update Password Admin
    public function updatePassword($id_admin, $password_baru) {
        $hash = password_hash($password_baru, PASSWORD_BCRYPT);
        
        $query = "UPDATE admin SET password = :pass WHERE id_admin = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':pass' => $hash,
            ':id'   => $id_admin
        ]);
    }
}
?>