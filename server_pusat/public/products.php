<?php
// session_start();
// // Cek Login
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

require_once '../config/database.php';
require_once '../controllers/ProductController.php';

$controller = new ProductController($db);
$pesan = '';

// Handle Tambah Produk Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $data = [
        'kode_produk' => $_POST['kode_produk'],
        'nama_produk' => $_POST['nama_produk'],
        'satuan'      => $_POST['satuan'],
        'harga_jual'  => $_POST['harga_jual']
    ];
    
    $hasil = $controller->store($data);
    if ($hasil['status'] == 'success') {
        $pesan = "<div class='alert alert-success bg-success bg-opacity-10 text-success border-0'>Produk berhasil ditambahkan!</div>";
    } else {
        $pesan = "<div class='alert alert-danger bg-danger bg-opacity-10 text-danger border-0'>{$hasil['message']}</div>";
    }
}

// Ambil data untuk tabel
$produkList = $controller->getAllProduk(); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Retail Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Menggunakan style yang sama dengan index.php */
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #333; }
        .sidebar-link { text-decoration: none; color: #555; display: block; padding: 10px 15px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #eef2f6; color: #0d6efd; font-weight: 500; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .table thead th { font-size: 0.85rem; text-transform: uppercase; color: #888; background: #fff; }
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
                    <a href="products.php" class="sidebar-link active"><i class="fa-solid fa-box me-2"></i> Data Produk</a>
                    <a href="harga.php" class="sidebar-link"><i class="fa-solid fa-tags me-2"></i> Manajemen Harga</a>
                    <a href="stok.php" class="sidebar-link"><i class="fa-solid fa-truck-ramp-box me-2"></i> Distribusi Stok</a>
                    <a href="laporan.php" class="sidebar-link"><i class="fa-solid fa-file-invoice me-2"></i> Laporan Transaksi</a>
                </div>
            </div>

            <div class="col-lg-9">
                
                <?php echo $pesan; ?>

                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1">Data Produk</h4>
                            <p class="text-muted small mb-0">Kelola master data barang untuk seluruh cabang.</p>
                        </div>
                        <button class="btn btn-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAdd">
                            <i class="fa-solid fa-plus me-1"></i> Tambah Produk
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Barcode / SKU</th>
                                    <th>Nama Produk</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Harga Aktif</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($produkList as $p): ?>
                                <tr>
                                    <td class="text-secondary small font-monospace"><?php echo $p['kode_produk']; ?></td>
                                    <td class="fw-bold text-dark"><?php echo $p['nama_produk']; ?></td>
                                    <td><span class="badge bg-light text-secondary border"><?php echo $p['satuan']; ?></span></td>
                                    <td class="text-end fw-bold text-success">
                                        Rp <?php echo number_format($p['harga_jual'] ?? 0, 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($p['status'] == 'aktif'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light text-primary"><i class="fa-solid fa-pen-to-square"></i></button>
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

    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Barcode / Kode SKU</label>
                            <input type="text" name="kode_produk" class="form-control" placeholder="Contoh: 899100100" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nama Produk</label>
                            <input type="text" name="nama_produk" class="form-control" placeholder="Contoh: Kopi Susu 200ml" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Satuan</label>
                                <select name="satuan" class="form-select">
                                    <option value="pcs">Pcs</option>
                                    <option value="botol">Botol</option>
                                    <option value="box">Box</option>
                                    <option value="kg">Kg</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Harga Jual (Rp)</label>
                                <input type="number" name="harga_jual" class="form-control" placeholder="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>