<?php
// session_start();
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

require_once '../config/database.php';
require_once '../controllers/HargaController.php';

$hargaController = new HargaController($db);
$pesan = '';

// 1. Handle Update Harga
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_harga') {
    $id_produk = $_POST['id_produk'];
    $harga_baru = $_POST['harga_baru'];

    $result = $hargaController->updateHarga($id_produk, $harga_baru);
    
    if ($result['status'] == 'success') {
        $pesan = "<div class='alert alert-success bg-success bg-opacity-10 text-success border-0'>Harga berhasil diperbarui! Toko akan menerima update saat sinkronisasi.</div>";
    } else {
        $pesan = "<div class='alert alert-danger bg-danger bg-opacity-10 text-danger border-0'>{$result['message']}</div>";
    }
}

// 2. Ambil Data Harga Aktif
$listHarga = $hargaController->getCurrentPrices();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Harga - Retail Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #333; }
        .sidebar-link { text-decoration: none; color: #555; display: block; padding: 10px 15px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #eef2f6; color: #0d6efd; font-weight: 500; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .table-price { font-family: 'Courier New', monospace; font-weight: bold; letter-spacing: -0.5px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white fixed-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark" href="index.php"><i class="fa-solid fa-network-wired text-primary me-2"></i> RETAIL PUSAT</a>
            <span class="text-secondary small">Halo, <?php echo $_SESSION['admin_nama']; ?></span>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; margin-bottom: 50px;">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="card p-3 h-100">
                    <h6 class="text-muted text-uppercase mb-3 px-2" style="font-size: 0.75rem;">Menu Utama</h6>
                    <a href="index.php" class="sidebar-link"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                    <a href="products.php" class="sidebar-link"><i class="fa-solid fa-box me-2"></i> Data Produk</a>
                    <a href="harga.php" class="sidebar-link active"><i class="fa-solid fa-tags me-2"></i> Manajemen Harga</a>
                    <a href="stok.php" class="sidebar-link"><i class="fa-solid fa-truck-ramp-box me-2"></i> Distribusi Stok</a>
                    <a href="laporan.php" class="sidebar-link"><i class="fa-solid fa-file-invoice me-2"></i> Laporan Transaksi</a>
                </div>
            </div>

            <div class="col-lg-9">
                <?php echo $pesan; ?>

                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1">Manajemen Harga</h4>
                            <p class="text-muted small mb-0">Atur harga jual produk. Perubahan akan disinkronkan ke toko.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode SKU</th>
                                    <th>Nama Produk</th>
                                    <th class="text-end">Harga Saat Ini</th>
                                    <th class="text-end">Berlaku Sejak</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($listHarga as $h): ?>
                                <tr>
                                    <td class="small text-secondary"><?php echo $h['kode_produk']; ?></td>
                                    <td class="fw-bold"><?php echo $h['nama_produk']; ?></td>
                                    <td class="text-end text-success table-price">
                                        Rp <?php echo number_format($h['harga_jual'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-end small text-muted">
                                        <?php echo date('d M Y, H:i', strtotime($h['tgl_berlaku'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-edit" 
                                                data-id="<?php echo $h['id_produk']; ?>"
                                                data-nama="<?php echo $h['nama_produk']; ?>"
                                                data-harga="<?php echo $h['harga_jual']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditHarga">
                                            <i class="fa-solid fa-pen me-1"></i> Ubah
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditHarga" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Perbarui Harga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_harga">
                        <input type="hidden" name="id_produk" id="edit_id_produk">
                        
                        <div class="mb-3 p-3 bg-light rounded text-center">
                            <label class="small text-muted mb-1">Nama Produk</label>
                            <h5 class="fw-bold mb-0 text-dark" id="edit_nama_produk">-</h5>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Harga Baru (Rp)</label>
                            <input type="number" name="harga_baru" class="form-control form-control-lg fw-bold" placeholder="0" required>
                            <div class="form-text text-success"><i class="fa-solid fa-info-circle me-1"></i>Harga lama akan dinonaktifkan (arsip).</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan Harga</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script sederhana untuk melempar data tombol ke dalam modal
        const editButtons = document.querySelectorAll('.btn-edit');
        const inputId = document.getElementById('edit_id_produk');
        const textNama = document.getElementById('edit_nama_produk');
        const inputHarga = document.querySelector('input[name="harga_baru"]');

        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                inputId.value = this.getAttribute('data-id');
                textNama.textContent = this.getAttribute('data-nama');
                inputHarga.value = this.getAttribute('data-harga'); // Isi default harga sekarang
            });
        });
    </script>
</body>
</html>