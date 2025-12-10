<?php
require_once '../models/Toko.php';

class TokoController {
    private $db;
    private $tokoModel;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->tokoModel = new Toko($dbConnection);
    }

    public function getAll() {
        return $this->tokoModel->getAll();
    }

    // Handle Simpan / Edit
    public function save($data) {
        try {
            if (empty($data['id_toko'])) {
                // Insert
                $this->tokoModel->create($data);
                return ['status' => 'success', 'message' => 'Cabang baru berhasil dibuat. API Key telah digenerate.'];
            } else {
                // Update
                $this->tokoModel->update($data);
                return ['status' => 'success', 'message' => 'Data cabang berhasil diperbarui.'];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()];
        }
    }

    // Handle Perubahan Status
    public function setStatus($id, $status) {
        if ($this->tokoModel->toggleStatus($id, $status)) {
            return ['status' => 'success', 'message' => 'Status cabang diperbarui.'];
        }
        return ['status' => 'error', 'message' => 'Gagal update status.'];
    }

    // Handle Reset API Key
    public function regenerateKey($id) {
        if ($this->tokoModel->resetKey($id)) {
            return ['status' => 'success', 'message' => 'API Key baru berhasil digenerate. Segera update di komputer cabang!'];
        }
        return ['status' => 'error', 'message' => 'Gagal reset key.'];
    }
}
?>