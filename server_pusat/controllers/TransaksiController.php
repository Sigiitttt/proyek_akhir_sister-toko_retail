<?php
class TransaksiController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // 1. Ambil Laporan dengan Filter Lengkap
    public function getLaporanFilter($filters) {
        $sql = "SELECT t.*, k.nama_toko, k.kode_toko 
                FROM transaksi t
                JOIN toko k ON t.id_toko = k.id_toko
                WHERE 1=1 ";
        
        $params = [];

        // Filter Tanggal
        if (!empty($filters['tgl_mulai']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND DATE(t.waktu_transaksi) BETWEEN :mulai AND :akhir";
            $params[':mulai'] = $filters['tgl_mulai'];
            $params[':akhir'] = $filters['tgl_akhir'];
        }

        // Filter Cabang
        if (!empty($filters['id_toko'])) {
            $sql .= " AND t.id_toko = :toko";
            $params[':toko'] = $filters['id_toko'];
        }

        // Filter Metode Pembayaran
        if (!empty($filters['metode'])) {
            $sql .= " AND t.metode_pembayaran = :metode";
            $params[':metode'] = $filters['metode'];
        }

        $sql .= " ORDER BY t.waktu_transaksi DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 2. Ambil Detail Lengkap (Header + Items)
    public function getDetailLengkap($id_transaksi) {
        // Ambil Header
        $sqlHead = "SELECT t.*, k.nama_toko, k.alamat 
                    FROM transaksi t 
                    JOIN toko k ON t.id_toko = k.id_toko 
                    WHERE t.id_transaksi = :id";
        $stmtHead = $this->db->prepare($sqlHead);
        $stmtHead->execute([':id' => $id_transaksi]);
        $header = $stmtHead->fetch();

        // Ambil Items
        $sqlItem = "SELECT d.*, p.nama_produk, p.kode_produk 
                    FROM detail_transaksi d
                    JOIN produk p ON d.id_produk = p.id_produk
                    WHERE d.id_transaksi = :id";
        $stmtItem = $this->db->prepare($sqlItem);
        $stmtItem->execute([':id' => $id_transaksi]);
        $items = $stmtItem->fetchAll();

        return ['header' => $header, 'items' => $items];
    }
}
?>