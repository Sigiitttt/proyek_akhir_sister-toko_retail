<?php
require_once '../models/Produk.php';

class ProductController
{
    private $db;
    private $produkModel;

    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
        $this->produkModel = new Produk($dbConnection);
    }

    // TAMPILKAN DATA
    public function getAllProduk()
    {
        return $this->produkModel->getAll();
    }

    // HANDLE UPLOAD GAMBAR
    private function uploadImage($file)
    {
        $targetDir = "../public/assets/uploads/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); // Buat folder jika belum ada

        $fileName = time() . '_' . basename($file["name"]);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validasi ekstensi
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($imageFileType, $allowed)) return null;

        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $fileName;
        }
        return null;
    }

    // TAMBAH / EDIT PRODUK
    public function save($data, $file = null)
    {
        try {
            $this->db->beginTransaction();

            // Handle Upload Gambar (Kode sama seperti sebelumnya)
            $gambar = null;
            if ($file && $file['name'] != '') {
                $gambar = $this->uploadImage($file);
            }

            // INSERT (Baru)
            if (empty($data['id_produk'])) {
                // TAMBAHKAN stok_global DI SINI
                $sql = "INSERT INTO produk (kode_produk, nama_produk, kategori, satuan, gambar, stok_global, status, created_at) 
                        VALUES (:kode, :nama, :kategori, :satuan, :gambar, :stok, :status, NOW())";
                $params = [
                    ':kode' => $data['kode_produk'],
                    ':nama' => $data['nama_produk'],
                    ':kategori' => $data['kategori'],
                    ':satuan' => $data['satuan'],
                    ':gambar' => $gambar,
                    ':stok'   => $data['stok_global'], // Input Stok Awal Pusat
                    ':status' => $data['status']
                ];

                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $id_produk = $this->db->lastInsertId();

                // Insert Harga Awal
                $stmtH = $this->db->prepare("INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) VALUES (?, ?, NOW(), 1)");
                $stmtH->execute([$id_produk, $data['harga_jual']]);
            }
            // UPDATE (Edit)
            else {
                // TAMBAHKAN stok_global DI SINI JUGA (Biar bisa revisi stok manual)
                $sql = "UPDATE produk SET kode_produk=:kode, nama_produk=:nama, kategori=:kategori, 
                        satuan=:satuan, stok_global=:stok, status=:status, updated_at=NOW()";

                $params = [
                    ':kode' => $data['kode_produk'],
                    ':nama' => $data['nama_produk'],
                    ':kategori' => $data['kategori'],
                    ':satuan' => $data['satuan'],
                    ':stok'   => $data['stok_global'], // Update stok
                    ':status' => $data['status'],
                    ':id' => $data['id_produk']
                ];

                if ($gambar) {
                    $sql .= ", gambar=:gambar";
                    $params[':gambar'] = $gambar;
                }

                $sql .= " WHERE id_produk=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Update Harga (Logic Versioning)
                if (isset($data['harga_jual'])) {
                    $this->db->prepare("UPDATE harga SET aktif=0 WHERE id_produk=?")->execute([$data['id_produk']]);
                    $this->db->prepare("INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) VALUES (?, ?, NOW(), 1)")
                        ->execute([$data['id_produk'], $data['harga_jual']]);
                }
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Produk berhasil disimpan'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // HAPUS PRODUK
    public function delete($id)
    {
        try {
            // Hapus gambar fisik (opsional)
            // $stmt = $this->db->prepare("SELECT gambar FROM produk WHERE id_produk=?"); ... unlink() ...

            $stmt = $this->db->prepare("DELETE FROM produk WHERE id_produk = ?");
            $stmt->execute([$id]);
            return ['status' => 'success', 'message' => 'Produk dihapus permanen'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal hapus: ' . $e->getMessage()];
        }
    }

    // IMPORT CSV
    public function importCSV($file)
    {
        if ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
            return ['status' => 'error', 'message' => 'Format file harus CSV'];
        }

        try {
            $handle = fopen($file['tmp_name'], "r");
            fgetcsv($handle); // Skip header row

            $this->db->beginTransaction();
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Format CSV: Kode, Nama, Kategori, Satuan, Harga
                $kode = $row[0];
                $nama = $row[1];
                $kategori = $row[2];
                $satuan = $row[3];
                $harga = $row[4];

                // Cek duplikat kode
                $cek = $this->db->prepare("SELECT id_produk FROM produk WHERE kode_produk = ?");
                $cek->execute([$kode]);
                if ($cek->rowCount() > 0) continue; // Skip jika sudah ada

                // Insert Produk
                $stmt = $this->db->prepare("INSERT INTO produk (kode_produk, nama_produk, kategori, satuan, status, created_at) VALUES (?, ?, ?, ?, 'aktif', NOW())");
                $stmt->execute([$kode, $nama, $kategori, $satuan]);
                $id = $this->db->lastInsertId();

                // Insert Harga
                $stmtH = $this->db->prepare("INSERT INTO harga (id_produk, harga_jual, tgl_berlaku, aktif) VALUES (?, ?, NOW(), 1)");
                $stmtH->execute([$id, $harga]);
            }
            $this->db->commit();
            fclose($handle);
            return ['status' => 'success', 'message' => 'Import CSV Berhasil'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => 'Import Gagal: ' . $e->getMessage()];
        }
    }
}
