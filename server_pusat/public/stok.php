<?php
// session_start();
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

require_once '../config/database.php';
require_once '../controllers/StokController.php';

$stokController = new StokController($db);
$pesan = '';

// 1. Handle Form Submit (Distribusi Stok)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'distribute') {
    $id_toko = $_POST['id_toko'];
    $id_produk = $_POST['id_produk'];
    $jumlah = $_POST['jumlah'];

    $result = $stokController->tambahStokKeToko($id_toko, $id_produk, $jumlah);
    
    if ($result['status'] == 'success') {
        $pesan = "<div class='alert alert-success bg-success bg-opacity-10 text-success border-0'>Stok berhasil dikirim ke toko!</div>";
    } else {
        $pesan = "<div class='alert alert-danger bg-danger bg-opacity-10 text-danger border-0'>{$result['message']}</div>";
    }
}

// 2. Ambil Data Pendukung (Dropdown)
// Ambil daftar toko aktif
$tokoList = $db->query("SELECT * FROM toko WHERE is_active = 1")->fetchAll();
// Ambil daftar produk aktif
$produkList = $db->query("SELECT * FROM produk WHERE status = 'aktif' ORDER BY nama_produk ASC")->fetchAll();

// 3. Filter Tampilan Tabel Stok (Default: Toko Pertama)
$selected_toko = isset($_GET['toko_id']) ? $_GET['toko_id'] : ($tokoList[0]['id_toko'] ?? 0);
$stokToko = $stokController->getStokPerToko($selected_toko);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi Stok - Retail Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #333; }
        .sidebar-link { text-decoration: none; color: #555; display: block; padding: 10px 15px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #eef2f6; color: #0d6efd; font-weight: 500; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .form-select, .form-control { padding: 0.6rem; border-radius: 8px; border-color: #e9ecef; }
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
                    <a href="harga.php" class="sidebar-link"><i class="fa-solid fa-tags me-2"></i> Manajemen Harga</a>
                    <a href="stok.php" class="sidebar-link active"><i class="fa-solid fa-truck-ramp-box me-2"></i> Distribusi Stok</a>
                    <a href="laporan.php" class="sidebar-link"><i class="fa-solid fa-file-invoice me-2"></i> Laporan Transaksi</a>
                </div>
            </div>

            <div class="col-lg-9">
                <?php echo $pesan; ?>

                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="card p-4 h-100">
                            <h5 class="fw-bold mb-4"><i class="fa-solid fa-paper-plane text-primary me-2"></i> Kirim Stok</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="distribute">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Tujuan Toko</label>
                                    <select name="id_toko" class="form-select" required>
                                        <option value="">-- Pilih Toko --</option>
                                        <?php foreach($tokoList as $t): ?>
                                            <option value="<?php echo $t['id_toko']; ?>"><?php echo $t['nama_toko']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Produk</label>
                                    <select name="id_produk" class="form-select" required>
                                        <option value="">-- Pilih Barang --</option>
                                        <?php foreach($produkList as $p): ?>
                                            <option value="<?php echo $p['id_produk']; ?>">
                                                <?php echo $p['nama_produk']; ?> (<?php echo $p['kode_produk']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Jumlah Dikirim</label>
                                    <input type="number" name="jumlah" class="form-control" placeholder="0" min="1" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="fa-solid fa-check me-1"></i> Proses Distribusi
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0">Monitor Stok</h5>
                                <form method="GET" class="d-flex">
                                    <select name="toko_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 150px;">
                                        <?php foreach($tokoList as $t): ?>
                                            <option value="<?php echo $t['id_toko']; ?>" <?php echo ($selected_toko == $t['id_toko']) ? 'selected' : ''; ?>>
                                                <?php echo $t['nama_toko']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-center">Jumlah Stok</th>
                                            <th class="text-end">Update Terakhir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($stokToko)): ?>
                                            <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada data stok di toko ini.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($stokToko as $s): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?php echo $s['nama_produk']; ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info px-3">
                                                        <?php echo $s['jumlah']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-muted" style="font-size: 0.75rem;">
                                                    <?php echo date('d/m/Y H:i', strtotime($s['last_update'])); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>