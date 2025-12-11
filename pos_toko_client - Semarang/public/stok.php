<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';

if (!isset($_SESSION['kasir_logged_in'])) { header("Location: index.php"); exit; }

// --- LOGIC PENCARIAN ---
$keyword = $_GET['cari'] ?? '';
$sql = "SELECT * FROM produk";

if ($keyword) {
    $sql .= " WHERE nama_produk LIKE :cari OR kode_produk LIKE :cari";
}

$sql .= " ORDER BY nama_produk ASC"; // Urutkan nama

$stmt = $db_lokal->prepare($sql);
if ($keyword) {
    $stmt->bindValue(':cari', "%$keyword%");
}
$stmt->execute();
$produkList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Stok - POS Client</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }

        /* Navbar */
        .navbar {
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        /* Card Table */
        .card-table {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            background-color: #fff;
            overflow: hidden;
        }

        /* Search Input */
        .search-input {
            border-radius: 2rem;
            background-color: #fff;
            border: 1px solid #e3e6f0;
            padding-left: 1.5rem;
            height: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .search-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            border-color: #4e73df;
        }

        /* Table Styling */
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #858796;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 1rem;
        }

        .table td {
            vertical-align: middle;
            color: #5a5c69;
            padding: 1rem;
        }

        .badge-stok {
            font-size: 0.9rem;
            padding: 0.5em 1em;
            border-radius: 50rem;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a href="dashboard.php" class="btn btn-light text-secondary rounded-pill fw-bold shadow-sm px-3">
                <i class="fa-solid fa-arrow-left me-2"></i>Dashboard
            </a>
            <span class="navbar-brand mb-0 h1 fw-bold text-info mx-auto">
                <i class="fa-solid fa-boxes-stacked me-2"></i> CEK STOK & HARGA
            </span>
            <div style="width: 100px;"></div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; padding-bottom: 50px;">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h5 class="fw-bold text-dark mb-1">Informasi Produk Lokal</h5>
                <p class="text-muted small mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i> 
                    Stok berkurang otomatis saat transaksi. Lakukan <b>Sinkronisasi</b> untuk update stok baru dari pusat.
                </p>
            </div>
            <div class="col-md-6">
                <form class="d-flex justify-content-end">
                    <div class="input-group" style="max-width: 400px;">
                        <input class="form-control search-input" type="search" name="cari" placeholder="Cari Nama / Kode Produk..." value="<?php echo htmlspecialchars($keyword); ?>">
                        <button class="btn btn-primary rounded-pill ms-2 px-4 shadow-sm" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-table">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Nama Produk</th>
                                <th>Kode SKU</th>
                                <th>Satuan</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($produkList)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Produk tidak ditemukan.</td></tr>
                            <?php else: ?>
                                <?php foreach($produkList as $p): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?php echo $p['nama_produk']; ?></td>
                                    <td>
                                        <span class="font-monospace text-primary bg-light px-2 py-1 rounded small border">
                                            <?php echo $p['kode_produk']; ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small text-uppercase"><?php echo $p['satuan']; ?></td>
                                    <td class="text-end fw-bold text-success">
                                        Rp <?php echo number_format($p['harga_jual']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-dark" style="font-size: 1.1rem;"><?php echo $p['stok_lokal']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if($p['stok_lokal'] > 10): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Aman</span>
                                        <?php elseif($p['stok_lokal'] > 0): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3">Menipis</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted small opacity-75">
            Menampilkan <?php echo count($produkList); ?> produk dari database lokal.
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>