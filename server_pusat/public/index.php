<?php
session_start();
// Cek Login (Pastikan baris ini aktif di production)
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

$page = 'dashboard';
require_once '../config/database.php';

// --- LOGIKA DATA DASHBOARD ---

// 1. Ringkasan Hari Ini
$today = date('Y-m-d');
// PENTING: Gunakan parameter binding dengan titik dua (:today) untuk mencegah error SQL
$sqlSummary = "SELECT 
                (SELECT COALESCE(SUM(total_transaksi), 0) FROM transaksi WHERE DATE(waktu_transaksi) = :today) as omset,
                (SELECT COUNT(*) FROM transaksi WHERE DATE(waktu_transaksi) = :today) as trx_count,
                (SELECT COUNT(*) FROM toko WHERE is_active = 1) as toko_active,
                (SELECT COUNT(*) FROM produk WHERE status = 'aktif') as produk_active";
$stmt = $db->prepare($sqlSummary);
$stmt->execute([':today' => $today]);
$summary = $stmt->fetch();

// 2. Transaksi Terbaru (5 Terakhir)
$sqlTrx = "SELECT t.*, k.nama_toko 
           FROM transaksi t 
           JOIN toko k ON t.id_toko = k.id_toko 
           ORDER BY t.waktu_sync DESC LIMIT 5";
$recentTrx = $db->query($sqlTrx)->fetchAll();

// 3. Stok Rendah (Global Warning)
$sqlLowStock = "SELECT p.nama_produk, t.nama_toko, s.jumlah 
                FROM stok_toko s
                JOIN produk p ON s.id_produk = p.id_produk
                JOIN toko t ON s.id_toko = t.id_toko
                WHERE s.jumlah < 10
                ORDER BY s.jumlah ASC LIMIT 5";
$lowStocks = $db->query($sqlLowStock)->fetchAll();

// 4. Data Grafik Penjualan (7 Hari Terakhir)
$sqlChart = "SELECT DATE(waktu_transaksi) as tgl, SUM(total_transaksi) as total 
             FROM transaksi 
             WHERE waktu_transaksi >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(waktu_transaksi) 
             ORDER BY tgl ASC";
$chartData = $db->query($sqlChart)->fetchAll();

// Format data untuk Chart.js
$labels = [];
$values = [];
foreach ($chartData as $d) {
    $labels[] = date('d M', strtotime($d['tgl']));
    $values[] = $d['total'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Server Pusat</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc; /* Latar belakang lebih cerah */
            color: #5a5c69;
        }

        /* Styling Kartu Statistik */
        .card-stat {
            border: none;
            border-radius: 0.75rem; /* Sudut lebih bulat */
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); /* Shadow lembut */
            background-color: #fff;
            transition: all 0.3s ease;
        }
        
        .card-stat:hover {
            transform: translateY(-5px); /* Efek melayang saat hover */
        }

        /* Border kiri berwarna untuk identitas kartu */
        .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
        .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
        .border-left-info    { border-left: 0.25rem solid #36b9cc !important; }
        .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }

        /* Styling Teks di Kartu */
        .text-xs {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        /* Ikon di Kartu */
        .icon-box {
            color: #dddfeb;
            font-size: 2rem;
        }

        /* Header Tabel */
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #858796;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .table td {
            vertical-align: middle;
            color: #5a5c69;
        }

        /* Header Halaman */
        .page-header h4 {
            color: #5a5c69;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">

            <?php include 'components/sidebar.php'; ?>

            <div class="col-lg-10 offset-lg-2 p-4">

                <div class="d-flex justify-content-between align-items-center mb-4 page-header">
                    <div>
                        <h4 class="mb-1">Dashboard Overview</h4>
                        <p class="small mb-0">Selamat datang kembali, <span class="fw-bold text-primary"><?php echo $_SESSION['admin_nama']; ?></span>!</p>
                    </div>
                    <div>
                        <span class="badge bg-white text-secondary border shadow-sm px-3 py-2 rounded-pill">
                            <i class="fa-regular fa-calendar-alt me-2"></i> <?php echo date('d F Y'); ?>
                        </span>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-stat border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Omset Hari Ini</div>
                                        <div class="h5 mb-0 fw-bold text-dark">Rp <?php echo number_format($summary['omset'], 0, ',', '.'); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fa-solid fa-coins fa-2x text-gray-300 icon-box text-primary opacity-25"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-stat border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Transaksi Hari Ini</div>
                                        <div class="h5 mb-0 fw-bold text-dark"><?php echo $summary['trx_count']; ?> <small class="text-muted fs-6">Nota</small></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fa-solid fa-receipt fa-2x text-gray-300 icon-box text-success opacity-25"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-stat border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Cabang Aktif</div>
                                        <div class="h5 mb-0 fw-bold text-dark"><?php echo $summary['toko_active']; ?> <small class="text-muted fs-6">Unit</small></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fa-solid fa-store fa-2x text-gray-300 icon-box text-info opacity-25"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-stat border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Master Produk</div>
                                        <div class="h5 mb-0 fw-bold text-dark"><?php echo $summary['produk_active']; ?> <small class="text-muted fs-6">SKU</small></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fa-solid fa-box-open fa-2x text-gray-300 icon-box text-warning opacity-25"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    
                    <div class="col-lg-8 mb-4">
                        <div class="card card-stat shadow mb-4 h-100">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom">
                                <h6 class="m-0 fw-bold text-primary"><i class="fa-solid fa-chart-area me-2"></i>Tren Omset (7 Hari Terakhir)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area" style="height: 320px; width: 100%;">
                                    <canvas id="myAreaChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card card-stat shadow mb-4 h-100">
                            <div class="card-header py-3 bg-white border-bottom">
                                <h6 class="m-0 fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Warning Stok (< 10)</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($lowStocks)): ?>
                                    <div class="text-center p-5">
                                        <i class="fa-solid fa-check-circle fa-3x text-success mb-3 opacity-50"></i>
                                        <p class="text-muted small">Stok aman di semua cabang.</p>
                                    </div>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($lowStocks as $ls): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                                <div>
                                                    <div class="fw-bold text-dark small"><?php echo $ls['nama_produk']; ?></div>
                                                    <div class="small text-muted"><i class="fa-solid fa-store me-1"></i> <?php echo $ls['nama_toko']; ?></div>
                                                </div>
                                                <span class="badge bg-danger rounded-pill px-3"><?php echo $ls['jumlah']; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white text-center py-3">
                                <a href="stok.php" class="text-decoration-none fw-bold small text-primary">Kelola Distribusi &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-stat shadow mb-4">
                    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-list me-2"></i>Transaksi Masuk Terbaru</h6>
                        <a href="laporan.php" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Waktu Sync</th>
                                    <th>ID Transaksi</th>
                                    <th>Cabang Toko</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentTrx)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">Belum ada transaksi masuk hari ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentTrx as $rx): ?>
                                        <tr>
                                            <td class="ps-4 text-secondary small">
                                                <?php echo date('d M Y', strtotime($rx['waktu_sync'])); ?><br>
                                                <span class="fw-bold"><?php echo date('H:i', strtotime($rx['waktu_sync'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace"><?php echo $rx['id_transaksi']; ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?php echo $rx['nama_toko']; ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                Rp <?php echo number_format($rx['total_transaksi'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                                                    <i class="fa-solid fa-check-circle me-1"></i> Sukses
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> </div>
    </div>

    <script>
        // Set Font Family Default Chart.js
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#858796';

        const ctx = document.getElementById('myAreaChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Pendapatan',
                    data: <?php echo json_encode($values); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    tension: 0.3, // Garis melengkung halus
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFont: { size: 14 },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'Rp ' + tooltipItem.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { maxTicksLimit: 7 }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>