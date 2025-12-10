<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
$page = 'stok';

require_once '../config/database.php';
require_once '../controllers/StokController.php';

$controller = new StokController($db);
$pesan = '';

// Handle Post Distribusi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'distribute') {
    $hasil = $controller->distribute($_POST['id_toko'], $_POST['id_produk'], $_POST['jumlah']);
    $alertType = ($hasil['status'] == 'success') ? 'success' : 'danger';
    $pesan = "<div class='alert alert-$alertType alert-dismissible fade show border-0 shadow-sm'>
                <i class='fa-solid fa-circle-info me-2'></i>{$hasil['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
}

// Handle AJAX Request (Untuk Detail Sebaran)
if (isset($_GET['ajax_detail']) && isset($_GET['id_produk'])) {
    $detail = $controller->getDetailSebaran($_GET['id_produk']);
    header('Content-Type: application/json');
    echo json_encode($detail);
    exit;
}

// Data Tampilan Utama
$stokPusat = $controller->getStokPusat();
$riwayat = $controller->getRiwayat();
$tokoList = $db->query("SELECT * FROM toko WHERE is_active=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi Stok - Server Pusat</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #5a5c69; }
        .card-stat { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); background-color: #fff; }
        .page-header h4 { color: #5a5c69; font-weight: 700; }
        .nav-tabs { border-bottom: 2px solid #e3e6f0; }
        .nav-tabs .nav-link { border: none; color: #858796; font-weight: 600; padding: 1rem 1.5rem; transition: 0.2s; }
        .nav-tabs .nav-link:hover { color: #4e73df; background: #f8f9fc; }
        .nav-tabs .nav-link.active { color: #4e73df; border-bottom: 3px solid #4e73df; background: transparent; }
        .table thead th { background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0; font-size: 0.8rem; text-transform: uppercase; padding: 1rem; color: #858796; }
        .table td { vertical-align: middle; padding: 1rem; color: #5a5c69; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'components/sidebar.php'; ?>

        <div class="col-lg-10 offset-lg-2 p-4">
            
            <?php echo $pesan; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 page-header">
                <div>
                    <h4 class="mb-1">Distribusi Stok</h4>
                    <p class="small mb-0 text-muted">Kelola perpindahan barang dari Gudang Pusat ke Cabang.</p>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-md-4">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-truck-fast me-2"></i>Kirim Barang</h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="distribute">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Tujuan Cabang</label>
                                    <select name="id_toko" class="form-select bg-light border-0 fw-bold text-dark" required>
                                        <option value="">-- Pilih Cabang --</option>
                                        <?php foreach($tokoList as $t): ?>
                                            <option value="<?php echo $t['id_toko']; ?>">🏪 <?php echo $t['nama_toko']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Pilih Produk</label>
                                    <select name="id_produk" id="selectProduk" class="form-select border-0 shadow-sm" required onchange="cekSisaStok()">
                                        <option value="" data-stok="0">-- Pilih Produk --</option>
                                        <?php foreach($stokPusat as $p): ?>
                                            <option value="<?php echo $p['id_produk']; ?>" 
                                                    data-stok="<?php echo $p['stok_global']; ?>"
                                                    data-satuan="<?php echo $p['satuan']; ?>">
                                                <?php echo $p['nama_produk']; ?> (<?php echo $p['kode_produk']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2 p-2 bg-light rounded border border-light">
                                        <small class="text-muted fw-bold">Sisa Gudang:</small>
                                        <span class="fw-bold text-dark" id="displayStok">-</span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-secondary">Jumlah Kirim</label>
                                    <input type="number" name="jumlah" class="form-control form-control-lg border-0 bg-light fw-bold text-primary" placeholder="0" min="1" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow-sm">
                                    <i class="fa-solid fa-paper-plane me-2"></i> PROSES KIRIM
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white p-0 border-bottom">
                            <ul class="nav nav-tabs px-3" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabStok">📦 Stok Gudang Pusat</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRiwayat">📜 Riwayat Pengiriman</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-0">
                            <div class="tab-content">
                                
                                <div class="tab-pane fade show active" id="tabStok">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Nama Produk</th>
                                                    <th class="text-center">Stok Pusat</th>
                                                    <th class="text-center">Total di Cabang</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-center">Detail</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($stokPusat as $s): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?php echo $s['nama_produk']; ?></div>
                                                        <small class="text-muted font-monospace text-primary"><?php echo $s['kode_produk']; ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 rounded-pill fs-6">
                                                            <?php echo number_format($s['stok_global']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="fw-bold text-secondary"><?php echo number_format($s['total_di_cabang']); ?></span>
                                                        <small class="text-muted"><?php echo $s['satuan']; ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if($s['stok_global'] > 10): ?>
                                                            <span class="badge bg-success rounded-pill">Aman</span>
                                                        <?php elseif($s['stok_global'] > 0): ?>
                                                            <span class="badge bg-warning text-dark rounded-pill">Menipis</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger rounded-pill">Habis</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm"
                                                                style="width:32px; height:32px; padding:0;"
                                                                onclick="showDetail(<?php echo $s['id_produk']; ?>, '<?php echo addslashes($s['nama_produk']); ?>')"
                                                                title="Lihat Sebaran Stok">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabRiwayat">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Tanggal</th>
                                                    <th>Ke Cabang</th>
                                                    <th>Produk</th>
                                                    <th class="text-end">Jumlah</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($riwayat)): ?>
                                                    <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat pengiriman.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach($riwayat as $r): ?>
                                                    <tr>
                                                        <td class="ps-4 text-muted small">
                                                            <?php echo date('d M Y, H:i', strtotime($r['tanggal'])); ?>
                                                        </td>
                                                        <td><span class="fw-bold text-dark"><?php echo $r['nama_toko']; ?></span></td>
                                                        <td><?php echo $r['nama_produk']; ?></td>
                                                        <td class="text-end fw-bold text-success">+<?php echo number_format($r['jumlah']); ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill"><i class="fa-solid fa-check me-1"></i> Terkirim</span>
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

            </div> </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold">Detail Sebaran Stok: <span id="modalProdName" class="text-primary"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="small text-uppercase">
                            <tr>
                                <th class="ps-4">Nama Cabang</th>
                                <th class="text-end pe-4">Sisa Stok</th>
                            </tr>
                        </thead>
                        <tbody id="modalContent">
                            </tbody>
                    </table>
                </div>
                <div id="modalLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="modalEmpty" class="text-center py-4 text-muted" style="display:none;">
                    Belum ada stok yang didistribusikan ke cabang.
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalDetail = new bootstrap.Modal(document.getElementById('modalDetail'));
    const modalContent = document.getElementById('modalContent');
    const modalLoading = document.getElementById('modalLoading');
    const modalEmpty = document.getElementById('modalEmpty');

    function cekSisaStok() {
        const select = document.getElementById('selectProduk');
        const display = document.getElementById('displayStok');
        
        const selectedOption = select.options[select.selectedIndex];
        const stok = selectedOption.getAttribute('data-stok');
        const satuan = selectedOption.getAttribute('data-satuan');
        
        if (stok) {
            display.innerText = stok + ' ' + satuan;
            if (parseInt(stok) === 0) {
                display.classList.add('text-danger');
                display.classList.remove('text-dark');
                display.innerText += ' (Habis!)';
            } else {
                display.classList.remove('text-danger');
                display.classList.add('text-dark');
            }
        } else {
            display.innerText = '-';
        }
    }

    function showDetail(id, nama) {
        document.getElementById('modalProdName').innerText = nama;
        modalDetail.show();
        
        // Reset State
        modalContent.innerHTML = '';
        modalLoading.style.display = 'block';
        modalEmpty.style.display = 'none';

        // Fetch AJAX
        fetch(`stok.php?ajax_detail=1&id_produk=${id}`)
            .then(res => res.json())
            .then(data => {
                modalLoading.style.display = 'none';
                
                if (data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        html += `<tr>
                                    <td class="ps-4 fw-bold text-secondary">${item.nama_toko}</td>
                                    <td class="text-end pe-4 fw-bold text-dark">${item.jumlah}</td>
                                 </tr>`;
                    });
                    modalContent.innerHTML = html;
                } else {
                    modalEmpty.style.display = 'block';
                }
            })
            .catch(err => {
                modalLoading.style.display = 'none';
                modalContent.innerHTML = '<tr><td colspan="2" class="text-center text-danger py-3">Gagal memuat data</td></tr>';
            });
    }
</script>

</body>
</html>