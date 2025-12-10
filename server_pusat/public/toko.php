<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
$page = 'toko';

require_once '../config/database.php';
require_once '../controllers/TokoController.php';

$controller = new TokoController($db);
$pesan = '';

// HANDLE POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'save') {
        $hasil = $controller->save($_POST);
    }
    if (isset($_POST['action']) && $_POST['action'] == 'status') {
        $hasil = $controller->setStatus($_POST['id_toko'], $_POST['status_baru']);
    }
    if (isset($_POST['action']) && $_POST['action'] == 'reset_key') {
        $hasil = $controller->regenerateKey($_POST['id_toko']);
    }

    if (isset($hasil)) {
        $alertType = ($hasil['status'] == 'success') ? 'success' : 'danger';
        $pesan = "<div class='alert alert-$alertType alert-dismissible fade show border-0 shadow-sm'>
                    <i class='fa-solid fa-circle-info me-2'></i>{$hasil['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}

$dataToko = $controller->getAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Cabang - Server Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #5a5c69; }
        .card-stat { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); background-color: #fff; }
        .page-header h4 { color: #5a5c69; font-weight: 700; }
        .table thead th { background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0; font-size: 0.8rem; text-transform: uppercase; padding: 1rem; }
        .table td { vertical-align: middle; padding: 1rem; }
        .api-key-box { font-family: 'Courier New', monospace; background: #f1f3f5; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: #d63384; border: 1px solid #dee2e6; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; cursor: pointer; }
        .api-key-box:hover { background: #e9ecef; }
        .avatar-initial { width: 40px; height: 40px; background-color: #4e73df; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
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
                    <h4 class="mb-1">Data Cabang</h4>
                    <p class="small mb-0 text-muted">Kelola identitas toko, kepala cabang, dan akses API.</p>
                </div>
                <button class="btn btn-primary btn-sm shadow-sm px-3 rounded-pill fw-bold" onclick="openModalAdd()">
                    <i class="fa-solid fa-plus me-2"></i> Tambah Cabang
                </button>
            </div>

            <div class="card card-stat">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Cabang</th>
                                    <th>Kepala Toko</th>
                                    <th>Kode Unit</th>
                                    <th>Koneksi API</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($dataToko as $t): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initial me-3 bg-gradient-primary shadow-sm">
                                                <i class="fa-solid fa-store fs-6"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo $t['nama_toko']; ?></div>
                                                <small class="text-muted text-truncate d-block" style="max-width: 150px;"><?php echo $t['alamat']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-secondary"><?php echo $t['kepala_toko']; ?></span>
                                            <small class="text-muted"><i class="fa-brands fa-whatsapp me-1 text-success"></i> <?php echo $t['kontak_hp']; ?></small>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?php echo $t['kode_toko']; ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="api-key-box" onclick="copyKey('<?php echo $t['api_key']; ?>')" title="Klik untuk copy">
                                                <?php echo $t['api_key']; ?>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Reset Key akan memutus koneksi toko ini. Lanjut?');" style="margin:0;">
                                                <input type="hidden" name="action" value="reset_key">
                                                <input type="hidden" name="id_toko" value="<?php echo $t['id_toko']; ?>">
                                                <button class="btn btn-sm btn-light text-danger border shadow-sm rounded-circle" style="width:30px; height:30px; padding:0;" title="Reset Key">
                                                    <i class="fa-solid fa-rotate"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id_toko" value="<?php echo $t['id_toko']; ?>">
                                            <?php if($t['is_active'] == 1): ?>
                                                <input type="hidden" name="status_baru" value="0">
                                                <button class="badge bg-success bg-opacity-10 text-success border-0 px-3 py-2 rounded-pill fw-bold">Aktif</button>
                                            <?php else: ?>
                                                <input type="hidden" name="status_baru" value="1">
                                                <button class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-3 py-2 rounded-pill fw-bold">Nonaktif</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm" style="width:32px; height:32px; padding:0;" 
                                                onclick='openModalEdit(<?php echo json_encode($t); ?>)'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalToko" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">Tambah Cabang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id_toko" id="inputId">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Kode Unit</label>
                            <input type="text" name="kode_toko" id="inputKode" class="form-control text-uppercase font-monospace" placeholder="JKT01" maxlength="6" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-secondary">Nama Cabang</label>
                            <input type="text" name="nama_toko" id="inputNama" class="form-control" placeholder="Contoh: Toko Jakarta Pusat" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Kepala Toko (PIC)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-user text-muted"></i></span>
                                <input type="text" name="kepala_toko" id="inputKepala" class="form-control" placeholder="Nama PIC" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Kontak / WA</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-phone text-muted"></i></span>
                                <input type="text" name="kontak_hp" id="inputKontak" class="form-control" placeholder="08..." required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Alamat Lengkap</label>
                            <textarea name="alamat" id="inputAlamat" class="form-control" rows="2" placeholder="Jl. Raya..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-light fw-bold text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalToko = new bootstrap.Modal(document.getElementById('modalToko'));

    function openModalAdd() {
        document.getElementById('modalTitle').innerText = 'Tambah Cabang Baru';
        document.getElementById('inputId').value = '';
        document.getElementById('inputKode').value = '';
        document.getElementById('inputKode').removeAttribute('readonly'); 
        document.getElementById('inputNama').value = '';
        document.getElementById('inputKepala').value = '';
        document.getElementById('inputKontak').value = '';
        document.getElementById('inputAlamat').value = '';
        modalToko.show();
    }

    function openModalEdit(data) {
        document.getElementById('modalTitle').innerText = 'Edit Cabang';
        document.getElementById('inputId').value = data.id_toko;
        document.getElementById('inputKode').value = data.kode_toko;
        document.getElementById('inputKode').setAttribute('readonly', true); 
        document.getElementById('inputNama').value = data.nama_toko;
        document.getElementById('inputKepala').value = data.kepala_toko; // Load data baru
        document.getElementById('inputKontak').value = data.kontak_hp;   // Load data baru
        document.getElementById('inputAlamat').value = data.alamat;
        modalToko.show();
    }

    function copyKey(key) {
        navigator.clipboard.writeText(key).then(() => {
            alert("API Key tersalin: " + key);
        });
    }
</script>

</body>
</html>