<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';

// Cek Login
// if (!isset($_SESSION['kasir_logged_in'])) {
//     header("Location: index.php");
//     exit;
// }

// Hitung data ringkasan dari DB Lokal
// 1. Hitung transaksi yang belum di-sync (Pending)
$stmt = $db_lokal->query("SELECT COUNT(*) FROM transaksi WHERE is_synced = 0");
$pendingSync = $stmt->fetchColumn();

// 2. Hitung total produk lokal
$stmt = $db_lokal->query("SELECT COUNT(*) FROM produk");
$totalProduk = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - <?php echo ID_TOKO; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .menu-card {
            border: none;
            border-radius: 16px;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid transparent;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            border-color: #e9ecef;
        }
        .icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        /* Warna-warni Menu */
        .menu-kasir .icon-circle { background: #d1e7dd; color: #198754; }
        .menu-sync .icon-circle { background: #cfe2ff; color: #0d6efd; }
        .menu-history .icon-circle { background: #e2e3e5; color: #495057; }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-success" href="#">
                <i class="fa-solid fa-store me-2"></i>POS CABANG
            </a>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <div class="fw-bold text-dark small"><?php echo $_SESSION['kasir_nama']; ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Kasir ID: <?php echo $_SESSION['kasir_id']; ?></div>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-power-off"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px;">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center mb-0">
                    <div>
                        <small class="text-muted d-block">Status Data Lokal</small>
                        <span class="fw-bold text-dark"><?php echo $totalProduk; ?> Produk Tersedia</span>
                    </div>
                    <?php if($pendingSync > 0): ?>
                        <div class="text-end text-danger">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            <b><?php echo $pendingSync; ?> Transaksi</b> Belum Diupload
                        </div>
                    <?php else: ?>
                        <div class="text-end text-success">
                            <i class="fa-solid fa-check-circle me-1"></i> Semua Data Terkirim
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            
            <div class="col-md-4 col-sm-6">
                <a href="kasir.php" class="menu-card menu-kasir">
                    <div class="icon-circle">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Kasir Baru</h5>
                    <p class="text-muted small mb-0 text-center">Buat transaksi penjualan</p>
                </a>
            </div>

            <div class="col-md-4 col-sm-6 position-relative">
                <a href="sync_ui.php" class="menu-card menu-sync">
                    <?php if($pendingSync > 0): ?>
                        <span class="badge bg-danger rounded-pill status-badge"><?php echo $pendingSync; ?> Pending</span>
                    <?php endif; ?>
                    
                    <div class="icon-circle">
                        <i class="fa-solid fa-rotate"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Sinkronisasi</h5>
                    <p class="text-muted small mb-0 text-center">Download Produk & Upload Laporan</p>
                </a>
            </div>

            <div class="col-md-4 col-sm-6">
                <a href="riwayat.php" class="menu-card menu-history">
                    <div class="icon-circle">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Riwayat</h5>
                    <p class="text-muted small mb-0 text-center">Lihat transaksi hari ini</p>
                </a>
            </div>

        </div>

        <div class="text-center mt-5 text-muted small opacity-50">
            &copy; 2024 Retail POS Client System
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>