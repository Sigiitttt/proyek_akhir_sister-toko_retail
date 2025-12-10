<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
$page = 'laporan';

require_once '../config/database.php';
require_once '../controllers/TransaksiController.php';

$trxController = new TransaksiController($db);

// --- FITUR AJAX DETAIL ---
if (isset($_GET['ajax_detail']) && isset($_GET['id_trx'])) {
    $data = $trxController->getDetailLengkap($_GET['id_trx']);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// --- FILTER DATA ---
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$id_toko   = $_GET['id_toko'] ?? '';
$metode    = $_GET['metode'] ?? '';

// Ambil Data
$laporan = $trxController->getLaporanFilter([
    'tgl_mulai' => $tgl_mulai, 
    'tgl_akhir' => $tgl_akhir, 
    'id_toko' => $id_toko,
    'metode' => $metode
]);

// Data untuk Dropdown
$listToko = $db->query("SELECT * FROM toko WHERE is_active=1")->fetchAll();
// Hitung Total
$totalOmset = array_sum(array_column($laporan, 'total_transaksi'));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - Server Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .table td { vertical-align: middle; }
        .uuid-text { font-family: 'Courier New', monospace; font-size: 0.8rem; letter-spacing: -0.5px; }
        
        /* CSS Print Friendly */
        @media print {
            .no-print, .sidebar, .btn { display: none !important; }
            .col-lg-10 { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .card { border: none !important; shadow: none !important; }
            .table { border: 1px solid #000; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'components/sidebar.php'; ?>

        <div class="col-lg-10 offset-lg-2 p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <div>
                    <h4 class="fw-bold mb-1 text-dark">Laporan Transaksi</h4>
                    <p class="text-muted small mb-0">Rekapitulasi penjualan dari seluruh cabang.</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-danger btn-sm" onclick="window.print()">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF / Print
                    </button>
                    <a href="export_laporan.php?type=csv&tgl_mulai=<?=$tgl_mulai?>&tgl_akhir=<?=$tgl_akhir?>&id_toko=<?=$id_toko?>" target="_blank" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-file-csv me-1"></i> CSV
                    </a>
                    <a href="export_laporan.php?type=excel&tgl_mulai=<?=$tgl_mulai?>&tgl_akhir=<?=$tgl_akhir?>&id_toko=<?=$id_toko?>" target="_blank" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </a>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-0 no-print">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="small fw-bold text-muted">Dari</label>
                            <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?=$tgl_mulai?>">
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold text-muted">Sampai</label>
                            <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?=$tgl_akhir?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Cabang</label>
                            <select name="id_toko" class="form-select form-select-sm">
                                <option value="">Semua Cabang</option>
                                <?php foreach($listToko as $t): ?>
                                    <option value="<?=$t['id_toko']?>" <?=($id_toko == $t['id_toko'])?'selected':''?>>
                                        <?=$t['nama_toko']?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold text-muted">Pembayaran</label>
                            <select name="metode" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="Tunai" <?=($metode == 'Tunai')?'selected':''?>>Tunai</option>
                                <option value="QRIS" <?=($metode == 'QRIS')?'selected':''?>>QRIS</option>
                                <option value="Debit" <?=($metode == 'Debit')?'selected':''?>>Debit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fa-solid fa-filter me-1"></i> Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between">
                        <span class="fw-bold">Total Transaksi: <?=count($laporan)?></span>
                        <span class="fw-bold text-primary">Total Omset: Rp <?=number_format($totalOmset,0,',','.')?></span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Waktu Transaksi</th>
                                    <th>ID Global (UUID)</th>
                                    <th>Cabang</th>
                                    <th>Metode</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status Sync</th>
                                    <th class="text-center no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($laporan)): ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                                <?php else: ?>
                                    <?php foreach($laporan as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?=date('d/m/Y', strtotime($row['waktu_transaksi']))?><br>
                                            <span class="text-muted"><?=date('H:i', strtotime($row['waktu_transaksi']))?></span>
                                        </td>
                                        <td>
                                            <span class="uuid-text text-primary"><?=$row['id_transaksi']?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?=$row['nama_toko']?></span>
                                        </td>
                                        <td><?=$row['metode_pembayaran'] ?? 'Tunai'?></td>
                                        <td class="text-end fw-bold">Rp <?=number_format($row['total_transaksi'],0,',','.')?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">
                                                <i class="fa-solid fa-check me-1"></i> Diterima
                                            </span>
                                            <br>
                                            <small class="text-muted" style="font-size:0.6rem">
                                                Sync: <?=date('d/m H:i', strtotime($row['waktu_sync']))?>
                                            </small>
                                        </td>
                                        <td class="text-center no-print">
                                            <button class="btn btn-sm btn-light text-primary" onclick="showDetail('<?=$row['id_transaksi']?>')">
                                                <i class="fa-solid fa-eye"></i>
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
</div>

<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold">Detail Transaksi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="loading" class="text-center my-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                
                <div id="detailContent" style="display:none;">
                    <div class="row mb-3 border-bottom pb-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">ID Global (UUID)</small>
                            <span class="fw-bold font-monospace text-primary" id="detID">-</span>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted d-block">Total Belanja</small>
                            <span class="h4 fw-bold text-success" id="detTotal">-</span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Cabang:</small> <span class="fw-bold" id="detToko">-</span><br>
                            <small class="text-muted">Kasir ID:</small> <span id="detKasir">-</span>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">Waktu:</small> <span id="detWaktu">-</span><br>
                            <small class="text-muted">Metode:</small> <span class="badge bg-secondary" id="detMetode">-</span>
                        </div>
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detItems"></tbody>
                    </table>

                    <div class="d-flex justify-content-between mt-3 bg-light p-2 rounded">
                        <span>Bayar: <b id="detBayar">-</b></span>
                        <span>Kembali: <b id="detKembali">-</b></span>
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
    const modalDetail = new bootstrap.Modal(document.getElementById('modalDetail'));
    
    function showDetail(id) {
        document.getElementById('loading').style.display = 'block';
        document.getElementById('detailContent').style.display = 'none';
        modalDetail.show();

        fetch(`laporan.php?ajax_detail=1&id_trx=${id}`)
            .then(res => res.json())
            .then(data => {
                const h = data.header;
                const infoKasir = (h.nama_kasir && h.nama_kasir !== '-') ? h.nama_kasir : ('ID: ' + h.kasir_id);
                document.getElementById('detID').innerText = h.id_transaksi;
                document.getElementById('detTotal').innerText = 'Rp ' + parseInt(h.total_transaksi).toLocaleString('id-ID');
                document.getElementById('detToko').innerText = h.nama_toko;
                document.getElementById('detKasir').innerText = infoKasir; // Kolom kasir_id (perlu ada di tabel)
                document.getElementById('detWaktu').innerText = h.waktu_transaksi;
                document.getElementById('detMetode').innerText = h.metode_pembayaran ?? 'Tunai';
                document.getElementById('detBayar').innerText = 'Rp ' + parseInt(h.bayar).toLocaleString('id-ID');
                document.getElementById('detKembali').innerText = 'Rp ' + parseInt(h.kembalian).toLocaleString('id-ID');

                let rows = '';
                data.items.forEach(item => {
                    rows += `<tr>
                        <td>${item.nama_produk} <br><small class='text-muted'>${item.kode_produk}</small></td>
                        <td class='text-center'>${item.qty}</td>
                        <td class='text-end'>Rp ${parseInt(item.harga_satuan).toLocaleString('id-ID')}</td>
                        <td class='text-end fw-bold'>Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</td>
                    </tr>`;
                });
                document.getElementById('detItems').innerHTML = rows;

                document.getElementById('loading').style.display = 'none';
                document.getElementById('detailContent').style.display = 'block';
            });
    }
</script>

</body>
</html>