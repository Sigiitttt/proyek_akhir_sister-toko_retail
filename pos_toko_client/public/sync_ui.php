<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';

// Cek Login
// if (!isset($_SESSION['kasir_logged_in'])) { header("Location: index.php"); exit; }

$pesan = '';

// ==========================================
// HANDLE LOGIC SYNC (SAAT TOMBOL DITEKAN)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. ACTION: DOWNLOAD PRODUK (Pusat -> Lokal)
    if ($_POST['action'] == 'download_produk') {
        // Kita akan me-load script logika sync yang nanti akan kita buat di folder ../sync/
        // Menggunakan output buffering untuk menangkap pesan dari script tersebut
        ob_start();
        include '../sync/pull_produk.php'; 
        $output = ob_get_clean();
        
        // Cek hasil (Logic sederhana: kalau ada kata "Berhasil", anggap sukses)
        if (strpos($output, 'Berhasil') !== false) {
            $pesan = "<div class='alert alert-success'><i class='fa-solid fa-check-circle me-2'></i>Sinkronisasi Produk Berhasil! Data lokal sudah update.</div>";
        } else {
            $pesan = "<div class='alert alert-warning'><i class='fa-solid fa-triangle-exclamation me-2'></i>Respon Server: $output</div>";
        }
    }

    // 2. ACTION: UPLOAD TRANSAKSI (Lokal -> Pusat)
    if ($_POST['action'] == 'upload_transaksi') {
        ob_start();
        include '../sync/push_transaksi.php';
        $output = ob_get_clean();

        if (strpos($output, 'Berhasil') !== false) {
            $pesan = "<div class='alert alert-success'><i class='fa-solid fa-check-circle me-2'></i>Laporan Transaksi Berhasil Terkirim ke Pusat!</div>";
        } elseif (strpos($output, 'Kosong') !== false) {
            $pesan = "<div class='alert alert-info'><i class='fa-solid fa-info-circle me-2'></i>Tidak ada transaksi baru untuk dikirim.</div>";
        } else {
            $pesan = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i>Gagal Upload: $output</div>";
        }
    }
}

// ==========================================
// DATA MONITORING
// ==========================================

// 1. Hitung Pending Upload
$stmt = $db_lokal->query("SELECT COUNT(*) FROM transaksi WHERE is_synced = 0");
$pendingCount = $stmt->fetchColumn();

// 2. Cek Terakhir Update Produk
$stmt = $db_lokal->query("SELECT MAX(updated_at_pusat) FROM produk");
$lastUpdate = $stmt->fetchColumn();
$lastUpdateText = $lastUpdate ? date('d M Y H:i', strtotime($lastUpdate)) : 'Belum pernah sync';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi Data - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        .card-sync { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .card-sync:hover { transform: translateY(-5px); }
        .icon-box { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white shadow-sm py-3 mb-5">
        <div class="container">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
            <span class="fw-bold text-primary"><i class="fa-solid fa-rotate me-2"></i> KONTROL SINKRONISASI</span>
            <div style="width: 80px;"></div>
        </div>
    </nav>

    <div class="container">
        
        <?php echo $pesan; ?>

        <div class="row g-4 justify-content-center">
            
            <div class="col-md-5">
                <div class="card card-sync h-100 p-4">
                    <div class="text-center">
                        <div class="icon-box bg-info bg-opacity-10 text-info mx-auto">
                            <i class="fa-solid fa-cloud-arrow-down"></i>
                        </div>
                        <h5 class="fw-bold">Ambil Data Produk</h5>
                        <p class="text-muted small">
                            Mengambil data barang dan harga terbaru dari Server Pusat. 
                            <br>Lakukan ini setiap toko buka.
                        </p>
                        
                        <div class="alert alert-light border py-2 my-3">
                            <small class="text-muted d-block">Terakhir Update:</small>
                            <strong><?php echo $lastUpdateText; ?></strong>
                        </div>

                        <form method="POST" onsubmit="return confirm('Update data produk dari pusat?');">
                            <input type="hidden" name="action" value="download_produk">
                            <button type="submit" class="btn btn-info text-white w-100 rounded-pill fw-bold">
                                <i class="fa-solid fa-download me-2"></i> DOWNLOAD SEKARANG
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card card-sync h-100 p-4 border-2 <?php echo ($pendingCount > 0) ? 'border-warning' : 'border-white'; ?>">
                    <div class="text-center">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning mx-auto">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <h5 class="fw-bold">Kirim Laporan Transaksi</h5>
                        <p class="text-muted small">
                            Mengirim data penjualan offline ke Database Pusat.
                            <br>Lakukan secara berkala.
                        </p>

                        <div class="alert <?php echo ($pendingCount > 0) ? 'alert-warning' : 'alert-success'; ?> py-2 my-3">
                            <small class="d-block">Status Data Lokal:</small>
                            <?php if($pendingCount > 0): ?>
                                <strong class="text-danger"><?php echo $pendingCount; ?> Transaksi Pending</strong>
                            <?php else: ?>
                                <strong class="text-success">Semua Data Terkirim</strong>
                            <?php endif; ?>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="upload_transaksi">
                            <button type="submit" class="btn btn-warning text-dark w-100 rounded-pill fw-bold" 
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
            <span class="badge bg-light text-secondary border px-3 py-2">
                <i class="fa-solid fa-link me-2"></i>
                Terhubung ke: <?php echo BASE_API_URL; ?>
            </span>
        </div>
    </div>

</body>
</html>