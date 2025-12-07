<?php
// controllers/ProductController.php

// Pastikan meload model yang dibutuhkan
require_once '../models/Produk.php';

class ProductController {
    private $db;
    private $produkModel;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        // Inisialisasi Model Produk
        $this->produkModel = new Produk($dbConnection);
    }

    /**
     * 1. Menampilkan Semua Produk
     * Dipanggil oleh public/products.php untuk mengisi tabel
     */
    public function getAllProduk() {
        return $this->produkModel->getAll();
    }

    /**
     * 2. Menambah Produk Baru
     * Logic: Insert ke tabel 'produk', lalu ambil ID-nya, lalu insert ke tabel 'harga'
     */
    public function store($data) {
        try {
            // Mulai Transaksi Database (Agar atomik: semua sukses atau semua gagal)
            $this->db->beginTransaction();

            // A. Siapkan data untuk tabel produk
            $dataProduk = [
                'kode_produk' => $data['kode_produk'],
                'nama_produk' => $data['nama_produk'],
                'satuan'      => $data['satuan']
            ];

            // B. Panggil Model untuk Insert Produk
            // Method create() di model harus mengembalikan ID Produk yang baru dibuat
            $id_produk = $this->produkModel->create($dataProduk);

            // C. Insert Harga Awal ke tabel harga
            // Kita query manual disini atau bisa buat method di HargaModel, tapi ini cukup simpel
            $queryHarga = "INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) 
                           VALUES (:id, :harga, NOW(), 1)";
            $stmt = $this->db->prepare($queryHarga);
            $stmt->execute([
                ':id'    => $id_produk,
                ':harga' => $data['harga_jual']
            ]);

            // Jika semua lancar, Commit (Simpan Permanen)
            $this->db->commit();
            return ['status' => 'success', 'message' => 'Produk berhasil ditambahkan'];

        } catch (Exception $e) {
            // Jika ada error, Rollback (Batalkan semua perubahan)
            $this->db->rollBack();
            
            // Cek error duplicate entry (biasanya kode produk kembar)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['status' => 'error', 'message' => 'Kode Produk/Barcode sudah ada!'];
            }
            
            return ['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()];
        }
    }

    /**
     * 3. Update Produk
     * Logic: Update nama/satuan. Jika harga berubah, matikan harga lama & insert harga baru.
     */
    public function update($id_produk, $data) {
        try {
            $this->db->beginTransaction();

            // A. Update Data Dasar (Nama & Satuan)
            $this->produkModel->update($id_produk, [
                'nama_produk' => $data['nama_produk'],
                'satuan'      => $data['satuan']
            ]);

            // B. Cek apakah ada request update harga?
            if (isset($data['harga_baru']) && $data['harga_baru'] > 0) {
                // Matikan harga lama
                $sqlOff = "UPDATE harga SET aktif = 0 WHERE id_produk = :id";
                $this->db->prepare($sqlOff)->execute([':id' => $id_produk]);

                // Insert harga baru
                $sqlNew = "INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) 
                           VALUES (:id, :harga, NOW(), 1)";
                $this->db->prepare($sqlNew)->execute([
                    ':id'    => $id_produk,
                    ':harga' => $data['harga_baru']
                ]);
                
                // Trigger update timestamp di produk agar ter-sync ke toko
                $this->db->query("UPDATE produk SET updated_at = NOW() WHERE id_produk = $id_produk");
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Produk berhasil diperbarui'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => 'Gagal update: ' . $e->getMessage()];
        }
    }

    /**
     * 4. Hapus Produk (Soft Delete)
     */
    public function delete($id_produk) {
        try {
            $this->produkModel->delete($id_produk);
            return ['status' => 'success', 'message' => 'Produk berhasil dinonaktifkan'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()];
        }
    }
}
?>