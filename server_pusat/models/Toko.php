<?php
class Toko {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Ambil Semua Toko
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM toko ORDER BY id_toko DESC");
        return $stmt->fetchAll();
    }

    // Tambah Toko Baru
    public function create($data) {
        $apiKey = bin2hex(random_bytes(16)); 
        $sql = "INSERT INTO toko (nama_toko, kepala_toko, kontak_hp, kode_toko, alamat, api_key, is_active) 
                VALUES (:nama, :kepala, :kontak, :kode, :alamat, :key, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nama'   => $data['nama_toko'],
            ':kepala' => $data['kepala_toko'], // <--- BARU
            ':kontak' => $data['kontak_hp'],   // <--- BARU
            ':kode'   => strtoupper($data['kode_toko']),
            ':alamat' => $data['alamat'],
            ':key'    => $apiKey
        ]);
    }

    // Update Data Toko
    public function update($data) {
        $sql = "UPDATE toko SET nama_toko = :nama, kepala_toko = :kepala, kontak_hp = :kontak, 
                kode_toko = :kode, alamat = :alamat WHERE id_toko = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nama'   => $data['nama_toko'],
            ':kepala' => $data['kepala_toko'], // <--- BARU
            ':kontak' => $data['kontak_hp'],   // <--- BARU
            ':kode'   => strtoupper($data['kode_toko']),
            ':alamat' => $data['alamat'],
            ':id'     => $data['id_toko']
        ]);
    }

    // Toggle Status Aktif/Nonaktif
    public function toggleStatus($id_toko, $status_baru) {
        $sql = "UPDATE toko SET is_active = :status WHERE id_toko = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':status' => $status_baru, ':id' => $id_toko]);
    }

    // Reset API Key (Jika key lama bocor)
    public function resetKey($id_toko) {
        $newKey = bin2hex(random_bytes(16));
        $sql = "UPDATE toko SET api_key = :key WHERE id_toko = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':key' => $newKey, ':id' => $id_toko]);
    }
}
?>