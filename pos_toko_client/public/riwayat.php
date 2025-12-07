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
    <title>Riwayat - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white shadow-sm py-3 mb-4">
        <div class="container">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
            <span class="fw-bold text-success"><i class="fa-solid fa-clock-rotate-left me-2"></i> Riwayat Transaksi Lokal</span>
            <div style="width: 80px;"></div> </div>
    </nav>

    <div class="container">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Daftar Transaksi (50 Terakhir)</h5>
                <a href="sync.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-rotate me-1"></i> Upload ke Pusat
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>ID Transaksi</th>
                            <th>No Struk</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status Sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transaksi)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi</td></tr>
                        <?php else: ?>
                            <?php foreach($transaksi as $t): ?>
                            <tr>
                                <td class="small text-muted">
                                    <?php echo date('d/m H:i', strtotime($t['waktu_transaksi'])); ?>
                                </td>
                                <td>
                                    <span class="font-monospace small"><?php echo $t['id_transaksi']; ?></span>
                                </td>
                                <td><?php echo $t['no_struk']; ?></td>
                                <td class="text-end fw-bold">
                                    <?php echo formatRupiah($t['total_transaksi']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if($t['is_synced'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                            <i class="fa-solid fa-check me-1"></i> Terkirim
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">
                                            <i class="fa-solid fa-clock me-1"></i> Pending
                                        </span>
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

</body>
</html>