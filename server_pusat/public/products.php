<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
$page = 'products'; 

require_once '../config/database.php';
require_once '../controllers/ProductController.php';

$controller = new ProductController($db);
$pesan = '';

// HANDLE REQUEST POST (Simpan, Hapus, Import)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Tambah / Edit
    if (isset($_POST['action']) && $_POST['action'] == 'save') {
        $data = [
            'id_produk' => $_POST['id_produk'] ?? null,
            'kode_produk' => $_POST['kode_produk'],
            'nama_produk' => $_POST['nama_produk'],
            'kategori' => $_POST['kategori'],
            'satuan' => $_POST['satuan'],
            'stok_global' => $_POST['stok_global'],
            'harga_jual' => $_POST['harga_jual'],
            'status' => $_POST['status']
        ];
        $file = $_FILES['gambar'] ?? null;
        $hasil = $controller->save($data, $file);
    }

    // 2. Hapus
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $hasil = $controller->delete($_POST['id_produk']);
    }

    // 3. Import CSV
    if (isset($_POST['action']) && $_POST['action'] == 'import') {
        $hasil = $controller->importCSV($_FILES['file_csv']);
    }

    if (isset($hasil)) {
        $alertType = ($hasil['status'] == 'success') ? 'success' : 'danger';
        $pesan = "<div class='alert alert-$alertType alert-dismissible fade show border-0 shadow-sm'>
                    <i class='fa-solid fa-circle-info me-2'></i>{$hasil['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}

// HANDLE FILTER GET
$kategoriTerpilih = $_GET['kategori'] ?? '';
$searchQuery = $_GET['cari'] ?? '';

// Ambil data produk sesuai filter
// (Pastikan method getAllProduk di ProductController support parameter filter, 
// atau kita filter manual di sini jika controller belum support)

// Opsi 1: Filter Manual (Lebih aman jika tidak ingin ubah controller)
$semuaProduk = $controller->getAllProduk();
$produkList = [];

if ($kategoriTerpilih || $searchQuery) {
    foreach ($semuaProduk as $p) {
        $matchKategori = ($kategoriTerpilih == '' || $p['kategori'] == $kategoriTerpilih);
        $matchSearch = ($searchQuery == '' || stripos($p['nama_produk'], $searchQuery) !== false || stripos($p['kode_produk'], $searchQuery) !== false);
        
        if ($matchKategori && $matchSearch) {
            $produkList[] = $p;
        }
    }
} else {
    $produkList = $semuaProduk;
}

// Ambil Daftar Kategori Unik untuk Dropdown
$daftarKategori = array_unique(array_column($semuaProduk, 'kategori'));
sort($daftarKategori);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Server Pusat</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #5a5c69; }
        .card-stat { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); background-color: #fff; }
        .img-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 0.5rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .table thead th { background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0; color: #858796; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; padding: 1rem; }
        .table td { vertical-align: middle; color: #5a5c69; padding: 1rem; }
        .page-header h4 { color: #5a5c69; font-weight: 700; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; transition: 0.2s; }
        .btn-action:hover { transform: scale(1.1); }
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
                        <h4 class="mb-1">Data Produk</h4>
                        <p class="small mb-0 text-muted">Kelola katalog barang, harga, dan stok global.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm shadow-sm px-3 fw-bold rounded-pill" onclick="openModalAdd()">
                            <i class="fa-solid fa-plus me-2"></i>Tambah Produk
                        </button>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <form method="GET" class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <select name="kategori" class="form-select border-0 bg-light fw-bold text-secondary" onchange="this.form.submit()">
                                    <option value="">📂 Semua Kategori</option>
                                    <?php foreach($daftarKategori as $kat): ?>
                                        <option value="<?php echo $kat; ?>" <?php echo ($kategoriTerpilih == $kat) ? 'selected' : ''; ?>>
                                            <?php echo $kat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                    <input type="text" name="cari" class="form-control border-0 bg-light" placeholder="Cari nama / kode..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                            </div>
                            <div class="col-md-5 text-end">
                                <?php if($kategoriTerpilih || $searchQuery): ?>
                                    <a href="products.php" class="btn btn-light text-danger btn-sm me-2 fw-bold"><i class="fa-solid fa-xmark me-1"></i> Reset</a>
                                <?php endif; ?>
                                <a href="export_produk.php" class="btn btn-success btn-sm shadow-sm text-white fw-bold me-2">
                                    <i class="fa-solid fa-file-csv me-1"></i> Export
                                </a>
                                <button type="button" class="btn btn-info btn-sm text-white shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalImport">
                                    <i class="fa-solid fa-file-import me-1"></i> Import
                                </button>
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
                                        <th class="ps-4">Produk</th>
                                        <th>Kategori</th>
                                        <th>Kode SKU</th>
                                        <th>Stok Gudang</th>
                                        <th class="text-end">Harga Jual</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($produkList)): ?>
                                        <tr><td colspan="7" class="text-center py-5 text-muted">Produk tidak ditemukan.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($produkList as $p): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <?php $img = $p['gambar'] ? "assets/uploads/products/{$p['gambar']}" : "https://via.placeholder.com/48?text=IMG"; ?>
                                                    <img src="<?php echo $img; ?>" class="img-thumb me-3">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo $p['nama_produk']; ?></div>
                                                        <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?php echo $p['satuan']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border fw-normal px-2 py-1"><?php echo $p['kategori'] ?? '-'; ?></span>
                                            </td>
                                            <td class="font-monospace small text-primary"><?php echo $p['kode_produk']; ?></td>
                                            <td>
                                                <span class="fw-bold text-dark"><?php echo number_format($p['stok_global']); ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                Rp <?php echo number_format($p['harga_jual'] ?? 0, 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($p['status'] == 'aktif'): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-action btn-light text-primary border" 
                                                        onclick='openModalEdit(<?php echo json_encode($p); ?>)' title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus permanen produk ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_produk" value="<?php echo $p['id_produk']; ?>">
                                                    <button class="btn btn-action btn-light text-danger border ms-1" title="Hapus">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-muted small">
                    Menampilkan <?php echo count($produkList); ?> produk.
                </div>

            </div> </div>
    </div>

    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary" id="modalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id_produk" id="inputId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Kode SKU</label>
                                <input type="text" name="kode_produk" id="inputKode" class="form-control" required placeholder="Contoh: 899123...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Nama Produk</label>
                                <input type="text" name="nama_produk" id="inputNama" class="form-control" required placeholder="Contoh: Kopi Susu">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Kategori</label>
                                <select name="kategori" id="inputKategori" class="form-select">
                                    <?php foreach($daftarKategori as $kat): ?>
                                        <option value="<?php echo $kat; ?>"><?php echo $kat; ?></option>
                                    <?php endforeach; ?>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Satuan</label>
                                <input type="text" name="satuan" id="inputSatuan" class="form-control" placeholder="Pcs / Kg / Box">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Harga Jual (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" name="harga_jual" id="inputHarga" class="form-control border-start-0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Status</label>
                                <select name="status" id="inputStatus" class="form-select">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="card bg-light border-0 p-3">
                                    <label class="form-label small fw-bold text-primary mb-1">Stok Gudang Pusat</label>
                                    <div class="input-group">
                                        <input type="number" name="stok_global" id="inputStok" class="form-control fw-bold text-center" placeholder="0" required>
                                        <span class="input-group-text">Unit</span>
                                    </div>
                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                        <i class="fa-solid fa-circle-info me-1"></i>
                                        Stok ini akan berkurang otomatis saat didistribusikan ke cabang.
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-secondary">Gambar Produk</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 bg-light">
                        <button type="button" class="btn btn-light text-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImport" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold text-dark">Import CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="import">
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-file-csv fa-3x text-success mb-2"></i>
                            <p class="text-muted small">Upload file CSV untuk input data massal.</p>
                        </div>
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                    </div>
                    <div class="modal-footer border-top-0 bg-light">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalForm = new bootstrap.Modal(document.getElementById('modalForm'));

        function openModalAdd() {
            document.getElementById('modalTitle').innerText = 'Tambah Produk Baru';
            document.getElementById('inputId').value = '';
            document.getElementById('inputKode').value = '';
            document.getElementById('inputNama').value = '';
            document.getElementById('inputSatuan').value = '';
            document.getElementById('inputStok').value = '0';
            document.getElementById('inputHarga').value = '';
            modalForm.show();
        }

        function openModalEdit(data) {
            document.getElementById('modalTitle').innerText = 'Edit Produk';
            document.getElementById('inputId').value = data.id_produk;
            document.getElementById('inputKode').value = data.kode_produk;
            document.getElementById('inputNama').value = data.nama_produk;
            document.getElementById('inputKategori').value = data.kategori;
            document.getElementById('inputSatuan').value = data.satuan;
            document.getElementById('inputStok').value = data.stok_global;
            document.getElementById('inputHarga').value = data.harga_jual;
            document.getElementById('inputStatus').value = data.status;
            modalForm.show();
        }
    </script>

</body>
</html>