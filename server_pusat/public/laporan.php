<?php
// session_start();
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

require_once '../config/database.php';
require_once '../controllers/TransaksiController.php';

$trxController = new TransaksiController($db);

// --- FITUR AJAX (Untuk mengambil detail transaksi tanpa reload) ---
if (isset($_GET['ajax_detail']) && isset($_GET['id_trx'])) {
    $detail = $trxController->getDetailTransaksi($_GET['id_trx']);
    header('Content-Type: application/json');
    echo json_encode($detail);
    exit; // Stop script agar tidak me-load HTML di bawahnya
}
// ------------------------------------------------------------------

// 1. Setup Filter Tanggal (Default: Hari ini)
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$filter_toko = isset($_GET['id_toko']) ? $_GET['id_toko'] : '';

// 2. Ambil Data Laporan (Menggunakan Custom Query agar fleksibel filter range-nya)
// Kita tulis query manual disini untuk filter range tanggal yang spesifik
$sql = "SELECT t.*, k.nama_toko 
        FROM transaksi t
        JOIN toko k ON t.id_toko = k.id_toko
        WHERE DATE(t.waktu_transaksi) BETWEEN :mulai AND :akhir";
$params = [':mulai' => $tgl_mulai, ':akhir' => $tgl_akhir];

if (!empty($filter_toko)) {
    $sql .= " AND t.id_toko = :toko";
    $params[':toko'] = $filter_toko;
}
$sql .= " ORDER BY t.waktu_transaksi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// 3. Hitung Ringkasan
$totalOmset = 0;
$totalTransaksi = count($laporan);
foreach ($laporan as $row) {
    $totalOmset += $row['total_transaksi'];
}

// 4. Ambil Daftar Toko untuk Dropdown Filter
$listToko = $db->query("SELECT * FROM toko WHERE is_active=1")->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - Retail Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #333; }
        .sidebar-link { text-decoration: none; color: #555; display: block; padding: 10px 15px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #eef2f6; color: #0d6efd; font-weight: 500; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .table thead th { font-size: 0.85rem; text-transform: uppercase; color: #888; background: #fff; }
        
        /* CSS Khusus Print (Agar saat diprint sidebar hilang) */
        @media print {
            .no-print { display: none !important; }
            .col-lg-9 { width: 100% !important; }
            .card { border: 1px solid #ddd; box-shadow: none; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-light bg-white fixed-top shadow-sm py-3 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark" href="index.php"><i class="fa-solid fa-network-wired text-primary me-2"></i> RETAIL PUSAT</a>
            <span class="text-secondary small">Halo, <?php echo $_SESSION['admin_nama']; ?></span>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; margin-bottom: 50px;">
        <div class="row">
            <div class="col-lg-3 mb-4 no-print">
                <div class="card p-3 h-100">
                    <h6 class="text-muted text-uppercase mb-3 px-2" style="font-size: 0.75rem;">Menu Utama</h6>
                    <a href="index.php" class="sidebar-link"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                    <a href="products.php" class="sidebar-link"><i class="fa-solid fa-box me-2"></i> Data Produk</a>
                    <a href="harga.php" class="sidebar-link"><i class="fa-solid fa-tags me-2"></i> Manajemen Harga</a>
                    <a href="stok.php" class="sidebar-link"><i class="fa-solid fa-truck-ramp-box me-2"></i> Distribusi Stok</a>
                    <a href="laporan.php" class="sidebar-link active"><i class="fa-solid fa-file-invoice me-2"></i> Laporan Transaksi</a>
                </div>
            </div>

            <div class="col-lg-9">
                
                <div class="card p-4 mb-4 no-print">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" name="tgl_mulai" class="form-control" value="<?php echo $tgl_mulai; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" name="tgl_akhir" class="form-control" value="<?php echo $tgl_akhir; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Filter Toko</label>
                            <select name="id_toko" class="form-select">
                                <option value="">Semua Toko</option>
                                <?php foreach($listToko as $t): ?>
                                    <option value="<?php echo $t['id_toko']; ?>" <?php echo ($filter_toko == $t['id_toko']) ? 'selected' : ''; ?>>
                                        <?php echo $t['nama_toko']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="fa-solid fa-print"></i></button>
                        </div>
                    </form>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card p-3 bg-primary text-white">
                            <span class="small opacity-75">Total Omset (Periode Ini)</span>
                            <h3 class="fw-bold mb-0">Rp <?php echo number_format($totalOmset, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3">
                            <span class="small text-muted">Total Transaksi</span>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalTransaksi); ?> <small class="fs-6 text-muted">Nota</small></h3>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Rincian Transaksi</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal & Waktu</th>
                                    <th>ID Transaksi</th>
                                    <th>Cabang Toko</th>
                                    <th class="text-end">Total Belanja</th>
                                    <th class="text-center no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($laporan)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data transaksi pada periode ini.</td></tr>
                                <?php else: ?>
                                    <?php foreach($laporan as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo date('d M Y', strtotime($row['waktu_transaksi'])); ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('H:i:s', strtotime($row['waktu_transaksi'])); ?></div>
                                        </td>
                                        <td>
                                            <span class="font-monospace text-secondary"><?php echo $row['id_transaksi']; ?></span>
                                            <br><small class="text-muted">No Struk: <?php echo $row['no_struk']; ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $row['nama_toko']; ?></span></td>
                                        <td class="text-end fw-bold">Rp <?php echo number_format($row['total_transaksi'], 0, ',', '.'); ?></td>
                                        <td class="text-center no-print">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-detail" 
                                                    data-id="<?php echo $row['id_transaksi']; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#modalDetail">
                                                Detail
                                            </button>
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

    <div class="modal fade" id="modalDetail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="loading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small">Memuat data...</p>
                    </div>
                    
                    <div id="detailContent" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Produk</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBodyDetail">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script AJAX untuk Load Detail Transaksi
        const detailButtons = document.querySelectorAll('.btn-detail');
        const tableBody = document.getElementById('tableBodyDetail');
        const loadingDiv = document.getElementById('loading');
        const contentDiv = document.getElementById('detailContent');

        detailButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const idTrx = this.getAttribute('data-id');
                
                // Reset Modal
                loadingDiv.style.display = 'block';
                contentDiv.style.display = 'none';
                tableBody.innerHTML = '';

                // Fetch Data dari File INI SENDIRI (Self-Request)
                fetch(`laporan.php?ajax_detail=1&id_trx=${idTrx}`)
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        data.forEach(item => {
                            // Format Rupiah sederhana
                            let harga = new Intl.NumberFormat('id-ID').format(item.harga_satuan);
                            let subtotal = new Intl.NumberFormat('id-ID').format(item.subtotal);

                            html += `
                                <tr>
                                    <td class="small">${item.kode_produk}</td>
                                    <td>${item.nama_produk}</td>
                                    <td class="text-center">${item.qty}</td>
                                    <td class="text-end">Rp ${harga}</td>
                                    <td class="text-end fw-bold">Rp ${subtotal}</td>
                                </tr>
                            `;
                        });
                        tableBody.innerHTML = html;
                        loadingDiv.style.display = 'none';
                        contentDiv.style.display = 'block';
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>';
                        loadingDiv.style.display = 'none';
                        contentDiv.style.display = 'block';
                    });
            });
        });
    </script>
</body>
</html>