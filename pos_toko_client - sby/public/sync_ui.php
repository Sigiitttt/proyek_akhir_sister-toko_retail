<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../controllers/SyncController.php';

if (!isset($_SESSION['kasir_logged_in'])) { header("Location: index.php"); exit; }

$pesan = '';
$syncController = new SyncController($db_lokal);

// ==========================================
// HANDLE LOGIC SYNC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. DOWNLOAD
    if ($_POST['action'] == 'download_produk') {
        $hasil = $syncController->syncProduk();
        
        if ($hasil['status'] == 'success') {
            $pesan = "<div class='alert alert-success border-0 shadow-sm'><i class='fa-solid fa-circle-check me-2'></i>{$hasil['message']}</div>";
        } else {
            $pesan = "<div class='alert alert-danger border-0 shadow-sm'><i class='fa-solid fa-triangle-exclamation me-2'></i>{$hasil['message']}</div>";
        }
    }

    // 2. UPLOAD
    if ($_POST['action'] == 'upload_transaksi') {
        $hasil = $syncController->uploadTransaksi();

        if ($hasil['status'] == 'success') {
            $pesan = "<div class='alert alert-success border-0 shadow-sm'><i class='fa-solid fa-paper-plane me-2'></i>{$hasil['message']}</div>";
        } elseif ($hasil['status'] == 'info') {
            $pesan = "<div class='alert alert-info border-0 shadow-sm'><i class='fa-solid fa-circle-info me-2'></i>{$hasil['message']}</div>";
        } else {
            $pesan = "<div class='alert alert-danger border-0 shadow-sm'><i class='fa-solid fa-triangle-exclamation me-2'></i>{$hasil['message']}</div>";
        }
    }
}

// DATA MONITORING
$stmt = $db_lokal->query("SELECT COUNT(*) FROM transaksi WHERE is_synced = 0");
$pendingCount = $stmt->fetchColumn();

$stmt = $db_lokal->query("SELECT MAX(updated_at_pusat) FROM produk");
$lastUpdate = $stmt->fetchColumn();
$lastUpdateText = $lastUpdate ? date('d M Y, H:i', strtotime($lastUpdate)) : 'Belum pernah sync';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi - POS System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }
        
        .card-sync {
            border: none;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .card-sync:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.1);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
        }

        .border-top-primary { border-top: 5px solid #4e73df; }
        .border-top-warning { border-top: 5px solid #f6c23e; }
        
        .bg-light-primary { background-color: #e3f2fd; color: #4e73df; }
        .bg-light-warning { background-color: #fff3e0; color: #f6c23e; }

        .status-box {
            background-color: #f8f9fc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #e3e6f0;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg bg-white shadow-sm fixed-top py-3">
        <div class="container">
            <a href="dashboard.php" class="btn btn-light text-secondary rounded-pill fw-bold shadow-sm px-3">
                <i class="fa-solid fa-arrow-left me-2"></i>Dashboard
            </a>
            <span class="navbar-brand mb-0 h1 fw-bold text-primary mx-auto">
                <i class="fa-solid fa-rotate me-2"></i> SINKRONISASI DATA
            </span>
            <div style="width: 100px;"></div> </div>
    </nav>

    <div class="container" style="margin-top: 100px; padding-bottom: 50px;">
        
        <?php echo $pesan; ?>

        <div class="row g-4 justify-content-center">
            
            <div class="col-md-6 col-lg-5">
                <div class="card card-sync border-top-primary p-4 text-center">
                    <div class="card-body">
                        <div class="icon-circle bg-light-primary">
                            <i class="fa-solid fa-cloud-arrow-down"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Ambil Data Produk</h4>
                        <p class="text-muted small mb-4">
                            Unduh data produk, harga, dan stok terbaru dari Server Pusat. Pastikan koneksi internet stabil.
                        </p>
                        
                        <div class="status-box mb-4 text-start">
                            <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Terakhir Diperbarui</small>
                            <div class="fw-bold text-dark mt-1">
                                <i class="fa-regular fa-clock me-2 text-primary"></i> <?php echo $lastUpdateText; ?>
                            </div>
                        </div>

                        <form method="POST" onsubmit="return confirm('Update data produk dari pusat?');">
                            <input type="hidden" name="action" value="download_produk">
                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow-sm">
                                <i class="fa-solid fa-download me-2"></i> DOWNLOAD SEKARANG
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-5">
                <div class="card card-sync border-top-warning p-4 text-center">
                    <div class="card-body">
                        <div class="icon-circle bg-light-warning">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Upload Transaksi</h4>
                        <p class="text-muted small mb-4">
                            Kirim laporan penjualan offline ke Server Pusat untuk rekapitulasi data.
                        </p>

                        <div class="status-box mb-4 text-start">
                            <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Status Data Lokal</small>
                            <div class="mt-1 d-flex justify-content-between align-items-center">
                                <?php if($pendingCount > 0): ?>
                                    <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $pendingCount; ?> Pending</span>
                                <?php else: ?>
                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-2"></i>Semua Terkirim</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="upload_transaksi">
                            <button type="submit" class="btn btn-warning text-white w-100 py-2 rounded-pill fw-bold shadow-sm" 
                                <?php echo ($pendingCount == 0) ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-paper-plane me-2"></i> 
                                <?php echo ($pendingCount > 0) ? 'UPLOAD DATA' : 'DATA SUDAH AMAN'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <div class="text-center mt-5">
            <span class="badge bg-white text-secondary border shadow-sm px-4 py-2 rounded-pill fw-normal">
                <i class="fa-solid fa-link me-2 text-success"></i>
                Terhubung ke: <span class="font-monospace text-dark"><?php echo BASE_API_URL; ?></span>
            </span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>