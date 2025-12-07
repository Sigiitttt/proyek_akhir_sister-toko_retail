<?php
class TransaksiController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Ambil semua transaksi (bisa difilter tanggal)
    public function getAllTransaksi($filterTanggal = null, $filterToko = null) {
        $sql = "SELECT t.*, k.nama_toko 
                FROM transaksi t
                JOIN toko k ON t.id_toko = k.id_toko ";
        
        $conditions = [];
        $params = [];

        // Filter by Tanggal
        if ($filterTanggal) {
            $conditions[] = "DATE(t.waktu_transaksi) = :tgl";
            $params[':tgl'] = $filterTanggal;
        }

        // Filter by Toko
        if ($filterToko) {
            $conditions[] = "t.id_toko = :toko";
            $params[':toko'] = $filterToko;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY t.waktu_transaksi DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Lihat Detail Item per Transaksi
    public function getDetailTransaksi($id_transaksi) {
        $sql = "SELECT d.*, p.nama_produk, p.kode_produk 
                FROM detail_transaksi d
                JOIN produk p ON d.id_produk = p.id_produk
                WHERE d.id_transaksi = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id_transaksi]);
        return $stmt->fetchAll();
    }
    
    // Hitung Total Omset Hari Ini
    public function getOmsetHariIni() {
        $sql = "SELECT SUM(total_transaksi) as total FROM transaksi WHERE DATE(waktu_transaksi) = CURDATE()";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }
}
?>