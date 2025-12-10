<?php
// controllers/HargaController.php

require_once '../models/Harga.php';

class HargaController {
    private $db;
    private $hargaModel;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        // Inisialisasi Model Harga agar query SQL tidak menumpuk di Controller
        $this->hargaModel = new Harga($dbConnection);
    }

    // 1. Ambil Data Harga (Bisa difilter Global / Per Toko)
    // Dipanggil oleh public/harga.php
    public function getTabelHarga($filter_toko) {
        // Memanggil fungsi di Model yang sudah kita update sebelumnya
        return $this->hargaModel->getHargaList($filter_toko);
    }

    // 2. Proses Update Harga (Menangani Input Form)
    public function prosesUpdate($postData) {
        // Ambil data dari $_POST
        $id_produk = $postData['id_produk'];
        $harga_baru = $postData['harga_baru'];
        
        // Logika PENTING untuk membedakan Harga Global vs Harga Cabang
        // Di form (view), value="global" dikirim jika user memilih "Semua Cabang"
        // Kita ubah jadi NULL agar sesuai struktur database
        if (isset($postData['id_toko']) && $postData['id_toko'] == 'global') {
            $id_toko = null;
        } else {
            $id_toko = $postData['id_toko'];
        }

        // Panggil Model untuk eksekusi simpan ke DB
        return $this->hargaModel->setHarga($id_produk, $harga_baru, $id_toko);
    }
}
?>