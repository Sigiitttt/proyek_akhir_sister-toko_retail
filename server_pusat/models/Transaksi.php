<?php
// models/Transaksi.php

class Transaksi
{
    private $db;

    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
    }

    // [API] Simpan Transaksi dari Toko
    public function simpanTransaksiSync($data_transaksi)
    {
        try {
            $this->db->beginTransaction();

            foreach ($data_transaksi as $trx) {
                // 1. Cek duplikasi (Idempotency)
                $check = "SELECT id_transaksi FROM transaksi WHERE id_transaksi = :id";
                $stmtCheck = $this->db->prepare($check);
                $stmtCheck->execute([':id' => $trx['id_transaksi']]);

                if ($stmtCheck->rowCount() > 0) continue; // Skip jika sudah ada

                // 2. Insert Header (10 Parameter + NOW())
                // Total ada 11 Kolom, tapi 1 kolom (waktu_sync) pakai fungsi NOW() jadi tidak butuh parameter
                $sqlHeader = "INSERT INTO transaksi 
                             (id_transaksi, id_toko, no_struk, total_transaksi, bayar, kembalian, 
                              metode_pembayaran, kasir_id, nama_kasir, waktu_transaksi, waktu_sync) 
                             VALUES 
                             (:id, :idtoko, :struk, :total, :bayar, :kembali, 
                              :metode, :kasir, :nama, :waktu, NOW())";

                $this->db->prepare($sqlHeader)->execute([
                    ':id'      => $trx['id_transaksi'],
                    ':idtoko'  => $trx['id_toko'],
                    ':struk'   => $trx['no_struk'],
                    ':total'   => $trx['total_transaksi'],
                    ':bayar'   => $trx['bayar'],
                    ':kembali' => $trx['kembalian'],
                    ':metode'  => $trx['metode_pembayaran'] ?? 'Tunai',
                    ':kasir'   => $trx['kasir_id'] ?? 0,
                    ':nama'    => $trx['nama_kasir'] ?? '-',
                    ':waktu'   => $trx['waktu_transaksi']
                ]);

                // 3. Insert Detail
                $sqlDetail = "INSERT INTO detail_transaksi 
                              (id_transaksi, id_produk, qty, harga_satuan, subtotal) 
                              VALUES (:idtrx, :idprod, :qty, :harga, :subtotal)";
                $stmtDetail = $this->db->prepare($sqlDetail);

                foreach ($trx['items'] as $item) {
                    $stmtDetail->execute([
                        ':idtrx'    => $trx['id_transaksi'],
                        ':idprod'   => $item['id_produk'],
                        ':qty'      => $item['qty'],
                        ':harga'    => $item['harga_satuan'],
                        ':subtotal' => $item['subtotal']
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    // [ADMIN] Ambil Laporan Transaksi (Filter Tanggal & Toko)
    public function getAllLaporan($tgl_mulai = null, $tgl_akhir = null, $id_toko = null)
    {
        $sql = "SELECT t.*, k.nama_toko, k.kode_toko 
                FROM transaksi t
                JOIN toko k ON t.id_toko = k.id_toko
                WHERE 1=1 ";

        $params = [];

        if ($tgl_mulai && $tgl_akhir) {
            $sql .= " AND DATE(t.waktu_transaksi) BETWEEN :start AND :end";
            $params[':start'] = $tgl_mulai;
            $params[':end'] = $tgl_akhir;
        }

        if ($id_toko) {
            $sql .= " AND t.id_toko = :toko";
            $params[':toko'] = $id_toko;
        }

        $sql .= " ORDER BY t.waktu_transaksi DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // [ADMIN] Lihat Detail Barang per Transaksi
    public function getDetail($id_transaksi)
    {
        $sql = "SELECT d.*, p.nama_produk, p.kode_produk 
                FROM detail_transaksi d
                JOIN produk p ON d.id_produk = p.id_produk
                WHERE d.id_transaksi = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id_transaksi]);
        return $stmt->fetchAll();
    }
}
