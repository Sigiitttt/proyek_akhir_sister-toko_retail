<?php
session_start();

// // 1. Cek Keamanan: Apakah sudah login?
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

// 2. Load Dependensi
require_once '../config/database.php';
require_once '../controllers/TransaksiController.php';

// 3. Inisialisasi Controller
$trxController = new TransaksiController($db);

// 4. Ambil Data untuk Dashboard
$omsetHariIni = $trxController->getOmsetHariIni();
$listTransaksi = $trxController->getAllTransaksi(date('Y-m-d')); // Transaksi hari ini saja

// Hitung ringkasan sederhana (Raw Query biar cepat)
$totalToko = $db->query("SELECT COUNT(*) FROM toko WHERE is_active=1")->fetchColumn();
$totalProduk = $db->query("SELECT COUNT(*) FROM produk WHERE status='aktif'")->fetchColumn();
$jmlTransaksi = count($listTransaksi);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Retail Pusat</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9; /* Abu-abu sangat muda */
            color: #333;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .navbar-brand {
            font-weight: 600;
            color: #2c3e50;
        }
        .card {
            border: none;
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.05);
        }
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .table thead th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            border-bottom-width: 1px;
        }
        .btn-custom-light {
            background: #f1f3f5;
            color: #495057;
            border: none;
        }
        .btn-custom-light:hover {
            background: #e9ecef;
        }
        .sidebar-link {
            text-decoration: none;
            color: #555;
            display: block;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #eef2f6;
            color: #0d6efd;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-network-wired text-primary me-2"></i> RETAIL PUSAT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-secondary">Halo, <b><?php echo $_SESSION['admin_nama']; ?></b></span>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; margin-bottom: 50px;">
        <div class="row">
            
            <div class="col-lg-3 mb-4">
                <div class="card p-3 h-100">
                    <h6 class="text-muted text-uppercase mb-3 px-2" style="font-size: 0.75rem;">Menu Utama</h6>
                    <a href="index.php" class="sidebar-link active"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                    <a href="products.php" class="sidebar-link"><i class="fa-solid fa-box me-2"></i> Data Produk</a>
                    <a href="harga.php" class="sidebar-link"><i class="fa-solid fa-tags me-2"></i> Manajemen Harga</a>
                    <a href="stok.php" class="sidebar-link"><i class="fa-solid fa-truck-ramp-box me-2"></i> Distribusi Stok</a>
                    <a href="laporan.php" class="sidebar-link"><i class="fa-solid fa-file-invoice me-2"></i> Laporan Transaksi</a>
                </div>
            </div>

            <div class="col-lg-9">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-0">Ringkasan Penjualan</h4>
                        <p class="text-muted small mb-0">Update Realtime: <?php echo date('d F Y'); ?></p>
                    </div>
                    <div>
                        <button class="btn btn-custom-light btn-sm"><i class="fa-solid fa-sync me-1"></i> Refresh</button>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card p-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0 small">Omset Hari Ini</h6>
                                    <h5 class="fw-bold mb-0">Rp <?php echo number_format($omsetHariIni, 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card p-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0 small">Transaksi Hari Ini</h6>
                                    <h5 class="fw-bold mb-0"><?php echo $jmlTransaksi; ?> <small class="text-muted fs-6">Nota</small></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card p-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0 small">Total Cabang</h6>
                                    <h5 class="fw-bold mb-0"><?php echo $totalToko; ?> <small class="text-muted fs-6">Aktif</small></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">Transaksi Masuk Terbaru</h5>
                        <a href="laporan.php" class="text-decoration-none small fw-bold">Lihat Semua →</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-top">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>ID Transaksi</th>
                                    <th>Toko / Cabang</th>
                                    <th class="text-end">Total Belanja</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($listTransaksi)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                            Belum ada transaksi hari ini
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($listTransaksi as $trx): ?>
                                    <tr>
                                        <td class="text-muted small">
                                            <?php echo date('H:i', strtotime($trx['waktu_transaksi'])); ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark small"><?php echo $trx['id_transaksi']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fa-solid fa-shop me-1 text-secondary"></i>
                                                <?php echo $trx['nama_toko']; ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            Rp <?php echo number_format($trx['total_transaksi'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Sukses</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>