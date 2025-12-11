<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';

// if (!isset($_SESSION['kasir_logged_in'])) { header("Location: index.php"); exit; }

// Ambil Data Transaksi Lokal (Urutkan dari yang terbaru)
$stmt = $db_lokal->query("SELECT * FROM transaksi ORDER BY waktu_transaksi DESC LIMIT 50");
$transaksi = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - POS Client</title>
    
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
            font-size: 0.9rem;
        }

        /* Badge Status */
        .badge-status {
            padding: 0.5em 1em;
            border-radius: 50rem;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-success-soft {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-danger-soft {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .font-mono { font-family: 'Courier New', monospace; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a href="dashboard.php" class="btn btn-light text-secondary rounded-pill fw-bold shadow-sm px-3">
                <i class="fa-solid fa-arrow-left me-2"></i>Dashboard
            </a>
            <span class="navbar-brand mb-0 h1 fw-bold text-success mx-auto">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> RIWAYAT TRANSAKSI
            </span>
            <div style="width: 100px;"></div> </div>
    </nav>

    <div class="container" style="margin-top: 100px; padding-bottom: 50px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">Daftar Transaksi Lokal</h5>
                <p class="text-muted small mb-0">Menampilkan 50 transaksi terakhir yang tersimpan di perangkat ini.</p>
            </div>
            <a href="sync_ui.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload ke Pusat
            </a>
        </div>

        <div class="card card-table">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Waktu</th>
                                <th>ID Transaksi</th>
                                <th>No Struk</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status Sync</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($transaksi)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada transaksi</td></tr>
                            <?php else: ?>
                                <?php foreach($transaksi as $t): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo date('d/m/Y', strtotime($t['waktu_transaksi'])); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($t['waktu_transaksi'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="font-mono text-primary small fw-bold"><?php echo $t['id_transaksi']; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-secondary small"><?php echo $t['no_struk']; ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        <?php echo formatRupiah($t['total_transaksi']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($t['is_synced'] == 1): ?>
                                            <span class="badge badge-status badge-success-soft">
                                                <i class="fa-solid fa-check-circle me-1"></i> Terkirim
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-status badge-danger-soft">
                                                <i class="fa-solid fa-clock me-1"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="cetak_struk.php?id=<?php echo $t['id_transaksi']; ?>" target="_blank" class="btn btn-sm btn-light text-secondary rounded-circle shadow-sm" title="Cetak Ulang">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted opacity-50">Data disimpan secara lokal di perangkat ini.</small>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>