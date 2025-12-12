<?php
class TokoController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM toko ORDER BY is_active DESC, nama_toko ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($data) {
        try {
            if (empty($data['id_toko'])) {
                // INSERT (Tambah Baru)
                // Generate API Key Otomatis
                $apiKey = bin2hex(random_bytes(16)); 
                
                $sql = "INSERT INTO toko (kode_toko, nama_toko, kepala_toko, kontak_hp, alamat, api_key, is_active) 
                        VALUES (:kode, :nama, :kepala, :hp, :alamat, :key, 1)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':kode' => strtoupper($data['kode_toko']),
                    ':nama' => $data['nama_toko'],
                    ':kepala' => $data['kepala_toko'],
                    ':hp' => $data['kontak_hp'],
                    ':alamat' => $data['alamat'],
                    ':key' => $apiKey
                ]);
                return ['status' => 'success', 'message' => 'Cabang berhasil ditambahkan!'];
            } else {
                // UPDATE (Edit)
                $sql = "UPDATE toko SET nama_toko=:nama, kepala_toko=:kepala, kontak_hp=:hp, alamat=:alamat 
                        WHERE id_toko=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':nama' => $data['nama_toko'],
                    ':kepala' => $data['kepala_toko'],
                    ':hp' => $data['kontak_hp'],
                    ':alamat' => $data['alamat'],
                    ':id' => $data['id_toko']
                ]);
                return ['status' => 'success', 'message' => 'Data cabang diperbarui!'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Error DB: ' . $e->getMessage()];
        }
    }

    public function setStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE toko SET is_active = :status WHERE id_toko = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        return ['status' => 'success', 'message' => 'Status cabang diubah.'];
    }

    public function regenerateKey($id) {
        $newKey = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare("UPDATE toko SET api_key = :key WHERE id_toko = :id");
        $stmt->execute([':key' => $newKey, ':id' => $id]);
        return ['status' => 'success', 'message' => 'API Key berhasil di-reset.'];
    }

    // [BARU] FUNGSI HAPUS
    public function delete($id) {
        try {
            // Cek dulu apakah toko ini punya transaksi?
            $cek = $this->db->prepare("SELECT id_transaksi FROM transaksi WHERE id_toko = ? LIMIT 1");
            $cek->execute([$id]);
            
            if ($cek->rowCount() > 0) {
                return ['status' => 'error', 'message' => 'Gagal hapus! Cabang ini memiliki riwayat transaksi. Nonaktifkan saja statusnya.'];
            }

            $stmt = $this->db->prepare("DELETE FROM toko WHERE id_toko = :id");
            $stmt->execute([':id' => $id]);
            return ['status' => 'success', 'message' => 'Cabang berhasil dihapus permanen.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Gagal hapus: ' . $e->getMessage()];
        }
    }
}
?>