<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';

// Cek Login
if (!isset($_SESSION['kasir_logged_in'])) { header("Location: index.php"); exit; }

// Hitung data ringkasan
$stmt = $db_lokal->query("SELECT COUNT(*) FROM transaksi WHERE is_synced = 0");
$pendingSync = $stmt->fetchColumn();

$stmt = $db_lokal->query("SELECT COUNT(*) FROM produk");
$totalProduk = $stmt->fetchColumn();
$namaCabang = "Toko bandung";

// Ambil Omset Hari Ini (Lokal)
$today = date('Y-m-d');
$stmt = $db_lokal->prepare("SELECT SUM(total_transaksi) FROM transaksi WHERE DATE(waktu_transaksi) = ?");
$stmt->execute([$today]);
$omsetHariIni = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - POS System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }

        /* Navbar Clean */
        .navbar {
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
        }

        /* Card Stats Kecil */
        .card-stat-small {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            transition: all 0.2s;
            height: 100%;
            border-left: 4px solid transparent;
        }
        .border-left-success { border-left-color: #1cc88a; }
        .border-left-warning { border-left-color: #f6c23e; }
        .border-left-info { border-left-color: #36b9cc; }

        /* Menu Card Besar */
        .menu-card {
            background: white;
            border-radius: 1rem;
            padding: 2.5rem 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #5a5c69;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(58, 59, 69, 0.1);
            color: #4e73df;
        }
        
        /* Ikon Menu */
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        /* Warna Menu */
        .menu-kasir .icon-circle { background-color: #e8f5e9; color: #1cc88a; } /* Hijau */
        .menu-stok .icon-circle { background-color: #e0f2f1; color: #00897b; }   /* Teal (Baru) */
        .menu-sync .icon-circle { background-color: #e3f2fd; color: #4e73df; }    /* Biru */
        .menu-history .icon-circle { background-color: #fff3e0; color: #f6c23e; } /* Kuning */

        .menu-card:hover .icon-circle {
            transform: scale(1.1);
        }

        /* Badge Notifikasi */
        .badge-notify {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.5em 1em;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a class="navbar-brand text-primary" href="#">
                <i class="fa-solid fa-store me-2"></i> POS CLIENT
            </a>
            
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <div class="fw-bold text-dark small text-uppercase"><?php echo $_SESSION['kasir_nama'] ?? 'Kasir'; ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">ID: <?php echo $_SESSION['kasir_id'] ?? '-'; ?></div>
                </div>
                <a href="logout.php" class="btn btn-light text-danger btn-sm rounded-circle shadow-sm" style="width:35px; height:35px; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-power-off"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; padding-bottom: 50px;">
        
        <div class="container" style="margin-top: 100px; padding-bottom: 50px;">
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-banner d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-white text-primary mb-2 shadow-sm px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-store me-2"></i> <?php echo $namaCabang; ?>
                    </span>
                    
                    <h4 class="fw-bold mb-1 mt-1">Halo, <?php echo explode(' ', $_SESSION['kasir_nama'] ?? 'Kasir')[0]; ?>! 👋</h4>
                    <p class="mb-0 opacity-75 small">Selamat bertugas. Jangan lupa sinkronisasi data sebelum dan sesudah shift.</p>
                </div>
                <div class="d-none d-md-block text-end">
                    <span class="h2 fw-bold d-block mb-0"><?php echo date('H:i'); ?></span>
                    <span class="small opacity-75"><?php echo date('l, d F Y'); ?></span>
                </div>
            </div>
        </div>
    </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card-stat-small border-left-info d-flex align-items-center">
                    <div class="me-3">
                        <i class="fa-solid fa-box fa-2x text-info opacity-25"></i>
                    </div>
                    <div>
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Produk</div>
                        <div class="h5 mb-0 fw-bold text-dark"><?php echo $totalProduk; ?> SKU</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat-small border-left-success d-flex align-items-center">
                    <div class="me-3">
                        <i class="fa-solid fa-coins fa-2x text-success opacity-25"></i>
                    </div>
                    <div>
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Omset Shift Ini</div>
                        <div class="h5 mb-0 fw-bold text-dark">Rp <?php echo number_format($omsetHariIni, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php if($pendingSync > 0): ?>
                    <div class="card-stat-small border-left-warning d-flex align-items-center bg-warning bg-opacity-10">
                        <div class="me-3">
                            <i class="fa-solid fa-cloud-arrow-up fa-2x text-danger opacity-50"></i>
                        </div>
                        <div>
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Status Data</div>
                            <div class="h5 mb-0 fw-bold text-danger"><?php echo $pendingSync; ?> Pending</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-stat-small border-left-success d-flex align-items-center">
                        <div class="me-3">
                            <i class="fa-solid fa-check-circle fa-2x text-success opacity-25"></i>
                        </div>
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Status Data</div>
                            <div class="h5 mb-0 fw-bold text-dark">Tersinkronisasi</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h6 class="text-uppercase text-secondary fw-bold small mb-3 ps-1">Menu Aplikasi</h6>
        <div class="row g-4">
            
            <div class="col-md-6 col-xl-3">
                <a href="kasir.php" class="menu-card menu-kasir">
                    <div class="icon-circle shadow-sm">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Transaksi Baru</h5>
                    <p class="text-muted small mb-0 text-center">Buka mesin kasir</p>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="stok.php" class="menu-card menu-stok">
                    <div class="icon-circle shadow-sm">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Cek Stok</h5>
                    <p class="text-muted small mb-0 text-center">Info harga & sisa barang</p>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="sync_ui.php" class="menu-card menu-sync">
                    <?php if($pendingSync > 0): ?>
                        <span class="badge bg-danger rounded-pill badge-notify">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo $pendingSync; ?>
                        </span>
                    <?php endif; ?>
                    
                    <div class="icon-circle shadow-sm">
                        <i class="fa-solid fa-rotate"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Sinkronisasi</h5>
                    <p class="text-muted small mb-0 text-center">Upload & Download</p>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="riwayat.php" class="menu-card menu-history">
                    <div class="icon-circle shadow-sm">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Riwayat</h5>
                    <p class="text-muted small mb-0 text-center">Laporan harian</p>
                </a>
            </div>

        </div>

        <div class="text-center mt-5">
            <small class="text-muted opacity-50">&copy; 2024 Retail POS Client System v1.0</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>