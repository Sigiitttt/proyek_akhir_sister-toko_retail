<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
$page = 'harga';

require_once '../config/database.php';
require_once '../controllers/HargaController.php';

$controller = new HargaController($db);
$pesan = '';

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_harga') {
    $hasil = $controller->prosesUpdate($_POST);
    $alertType = ($hasil['status'] == 'success') ? 'success' : 'danger';
    $pesan = "<div class='alert alert-$alertType alert-dismissible fade show border-0 shadow-sm'>
                <i class='fa-solid fa-circle-info me-2'></i>{$hasil['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
}

// Ambil Daftar Toko untuk Dropdown
$tokoList = $db->query("SELECT * FROM toko WHERE is_active=1")->fetchAll();

// Filter Data
$filter_toko = isset($_GET['filter_toko']) ? $_GET['filter_toko'] : ''; // Default tampil semua
$listHarga = $controller->getTabelHarga($filter_toko);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Harga - Server Pusat</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }

        /* Card Styling */
        .card-stat {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            background-color: #fff;
        }

        /* Header Halaman */
        .page-header h4 {
            color: #5a5c69;
            font-weight: 700;
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
        }

        /* Badges */
        .badge-global { 
            background-color: #4e73df; 
            color: white; 
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .badge-toko { 
            background-color: #f6c23e; 
            color: #fff; 
            font-weight: 500;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .btn-edit {
            transition: all 0.2s;
        }
        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
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
                        <h4 class="mb-1">Manajemen Harga</h4>
                        <p class="small mb-0 text-muted">Atur harga jual produk secara global atau spesifik per cabang.</p>
                    </div>
                </div>

                <div class="card card-stat mb-4">
                    <div class="card-body py-3">
                        <form method="GET" class="row align-items-center g-2">
                            <div class="col-auto">
                                <span class="fw-bold small text-secondary text-uppercase"><i class="fa-solid fa-filter me-2"></i>Filter Cabang:</span>
                            </div>
                            <div class="col-md-4">
                                <select name="filter_toko" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                                    <option value="">-- Tampilkan Semua Harga --</option>
                                    <option value="global" <?php echo ($filter_toko === 'global') ? 'selected' : ''; ?>>🌍 Hanya Harga Global (Umum)</option>
                                    <?php foreach($tokoList as $t): ?>
                                        <option value="<?php echo $t['id_toko']; ?>" <?php echo ($filter_toko == $t['id_toko']) ? 'selected' : ''; ?>>
                                            🏪 Khusus <?php echo $t['nama_toko']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card card-stat">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Nama Produk</th>
                                        <th>Kode SKU</th>
                                        <th>Cakupan Harga</th>
                                        <th class="text-end">Harga Jual</th>
                                        <th class="text-center">Berlaku Sejak</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($listHarga)): ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Data harga tidak ditemukan.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($listHarga as $h): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo $h['nama_produk']; ?></td>
                                            <td class="small font-monospace text-primary"><?php echo $h['kode_produk']; ?></td>
                                            <td>
                                                <?php if(empty($h['id_toko'])): ?>
                                                    <span class="badge badge-global rounded-pill px-3 py-2 shadow-sm">
                                                        <i class="fa-solid fa-globe me-1"></i> Global
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-toko rounded-pill px-3 py-2 shadow-sm">
                                                        <i class="fa-solid fa-store me-1"></i> <?php echo $h['nama_toko']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold text-success fs-6">
                                                Rp <?php echo number_format($h['harga_jual'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center small text-muted">
                                                <?php echo date('d M Y', strtotime($h['tgl_berlaku'])); ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-edit shadow-sm"
                                                        data-id="<?php echo $h['id_produk']; ?>"
                                                        data-nama="<?php echo $h['nama_produk']; ?>"
                                                        data-harga="<?php echo $h['harga_jual']; ?>"
                                                        data-toko="<?php echo $h['id_toko'] ?? 'global'; ?>"
                                                        onclick="openModal(this)">
                                                    <i class="fa-solid fa-pen me-1"></i> Ubah Harga
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

            </div> </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary">Set Harga Jual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="update_harga">
                        <input type="hidden" name="id_produk" id="inputId">

                        <div class="mb-4 text-center p-3 bg-light rounded border border-light">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Produk Terpilih</label>
                            <h5 class="fw-bold mb-0 text-dark" id="textNama">-</h5>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Berlaku Untuk</label>
                            <select name="id_toko" id="inputToko" class="form-select border-2">
                                <option value="global">🌍 Semua Cabang (Harga Global)</option>
                                <optgroup label="Harga Spesifik Per Cabang">
                                    <?php foreach($tokoList as $t): ?>
                                        <option value="<?php echo $t['id_toko']; ?>">🏪 <?php echo $t['nama_toko']; ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <div class="form-text small mt-2"><i class="fa-solid fa-circle-info me-1"></i>Harga spesifik cabang akan diprioritaskan sistem.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Harga Baru (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-success fw-bold">Rp</span>
                                <input type="number" name="harga_baru" id="inputHarga" class="form-control form-control-lg fw-bold border-start-0 text-success" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 bg-light">
                        <button type="button" class="btn btn-light fw-bold text-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));

        function openModal(btn) {
            document.getElementById('inputId').value = btn.getAttribute('data-id');
            document.getElementById('textNama').innerText = btn.getAttribute('data-nama');
            document.getElementById('inputHarga').value = btn.getAttribute('data-harga');
            
            // Set dropdown toko sesuai data yang diklik
            const tokoVal = btn.getAttribute('data-toko');
            document.getElementById('inputToko').value = tokoVal;

            modalEdit.show();
        }
    </script>

</body>
</html>