<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../controllers/TransaksiController.php'; // Panggil Controller

// Cek Login
// if (!isset($_SESSION['kasir_logged_in'])) { header("Location: index.php"); exit; }

// Inisialisasi Controller
$trxController = new TransaksiController($db_lokal);
$pesan = '';

// ==========================================
// 1. LOGIC KERANJANG BELANJA (CART)
// ==========================================

// Tambah ke Cart
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id_produk'];
    
    // Cek stok/data dasar
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty']++;
    } else {
        $_SESSION['cart'][$id] = [
            'id' => $id,
            'kode' => $_POST['kode_produk'],
            'nama' => $_POST['nama_produk'],
            'harga' => $_POST['harga'],
            'qty' => 1
        ];
    }
    header("Location: kasir.php"); exit; // Redirect agar tidak resubmit saat refresh
}

// Hapus Item
if (isset($_GET['hapus'])) {
    unset($_SESSION['cart'][$_GET['hapus']]);
    header("Location: kasir.php"); exit;
}

// Reset Cart
if (isset($_GET['reset'])) {
    unset($_SESSION['cart']);
    header("Location: kasir.php"); exit;
}

// ==========================================
// 2. LOGIC CHECKOUT / BAYAR (Via Controller)
// ==========================================
if (isset($_POST['proses_bayar'])) {
    if (!empty($_SESSION['cart'])) {
        $bayar = $_POST['bayar'];
        $kasir_id = $_SESSION['kasir_id'];

        // Panggil Fungsi di Controller
        $hasil = $trxController->simpanTransaksi($_SESSION['cart'], $bayar, $kasir_id);

        if ($hasil['status'] == 'success') {
            unset($_SESSION['cart']); // Kosongkan keranjang
            $kembalianRp = formatRupiah($hasil['kembalian']);
            $pesan = "<script>
                        alert('✅ Transaksi Berhasil!\\n\\nKembalian: $kembalianRp');
                        window.location.href='kasir.php';
                      </script>";
        } else {
            $pesan = "<script>alert('❌ Gagal: " . $hasil['message'] . "');</script>";
        }
    }
}

// ==========================================
// 3. AMBIL DATA PRODUK (Via Controller)
// ==========================================
$keyword = isset($_GET['cari']) ? $_GET['cari'] : '';
$produkList = $trxController->cariProduk($keyword);

// Hitung Total Cart
$totalCart = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) $totalCart += ($c['harga'] * $c['qty']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - <?php echo ID_TOKO; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Layout Full Screen tidak gerak */
        body { background-color: #f0f2f5; height: 100vh; overflow: hidden; font-family: sans-serif; }
        
        /* Kolom Kiri (Produk) */
        .product-section { height: 100vh; overflow-y: auto; padding-bottom: 80px; }
        .product-card { 
            cursor: pointer; transition: 0.2s; border: 1px solid #e9ecef; background: white;
        }
        .product-card:hover { 
            border-color: #198754; background-color: #f1fcf5; transform: translateY(-3px); 
        }

        /* Kolom Kanan (Cart) */
        .cart-section { 
            background: white; height: 100vh; border-left: 1px solid #ddd; 
            display: flex; flex-direction: column; 
            box-shadow: -5px 0 15px rgba(0,0,0,0.05);
        }
        .scrollable-cart { flex-grow: 1; overflow-y: auto; }
        .total-box { background: #e8f5e9; color: #198754; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    
    <?php echo $pesan; ?>

    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-7 col-lg-8 product-section pt-3 px-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 sticky-top bg-light py-2" style="z-index: 10;">
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
                    </a>
                    <form class="d-flex shadow-sm rounded-pill overflow-hidden" style="width: 350px;">
                        <input class="form-control border-0 ps-3" type="search" name="cari" placeholder="Cari barcode / nama barang..." value="<?php echo $keyword; ?>" autofocus>
                        <button class="btn btn-success border-0 px-3" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="row g-3">
                    <?php if(empty($produkList)): ?>
                        <div class="col-12 text-center mt-5 text-muted opacity-50">
                            <i class="fa-solid fa-box-open fa-4x mb-3"></i>
                            <h5>Produk Tidak Ditemukan</h5>
                            <p class="small">Pastikan nama benar atau lakukan <b>Sinkronisasi</b> jika database kosong.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach($produkList as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <form method="POST">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="id_produk" value="<?php echo $p['id_produk']; ?>">
                            <input type="hidden" name="kode_produk" value="<?php echo $p['kode_produk']; ?>">
                            <input type="hidden" name="nama_produk" value="<?php echo $p['nama_produk']; ?>">
                            <input type="hidden" name="harga" value="<?php echo $p['harga_jual']; ?>">
                            
                            <button type="submit" class="card product-card h-100 w-100 text-start p-3 rounded-3">
                                <h6 class="fw-bold mb-1 text-dark text-truncate"><?php echo $p['nama_produk']; ?></h6>
                                <span class="badge bg-light text-secondary border mb-2"><?php echo $p['kode_produk']; ?></span>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="text-success fw-bold"><?php echo formatRupiah($p['harga_jual']); ?></span>
                                    <i class="fa-solid fa-circle-plus text-success fs-4"></i>
                                </div>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-5 col-lg-4 cart-section">
                
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-success"><i class="fa-solid fa-cart-shopping me-2"></i>Keranjang</h5>
                    <?php if(!empty($_SESSION['cart'])): ?>
                        <a href="?reset=1" class="btn btn-sm btn-outline-danger px-3 rounded-pill" onclick="return confirm('Kosongkan keranjang?')">Reset</a>
                    <?php endif; ?>
                </div>

                <div class="scrollable-cart p-3 bg-light">
                    <?php if(empty($_SESSION['cart'])): ?>
                        <div class="text-center text-muted mt-5 opacity-50">
                            <i class="fa-solid fa-basket-shopping fa-3x mb-3"></i>
                            <p>Belum ada barang dipilih</p>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm">
                            <table class="table table-borderless align-middle mb-0">
                                <?php foreach($_SESSION['cart'] as $id => $item): ?>
                                <tr class="border-bottom">
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark"><?php echo $item['nama']; ?></div>
                                        <div class="text-muted small">
                                            <?php echo number_format($item['harga'],0,',','.'); ?> x 
                                            <span class="fw-bold text-dark bg-warning bg-opacity-25 px-1 rounded"><?php echo $item['qty']; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        <?php echo number_format($item['harga'] * $item['qty'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-end pe-3" width="10">
                                        <a href="?hapus=<?php echo $id; ?>" class="text-danger opacity-50 hover-opacity-100"><i class="fa-solid fa-trash-can"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-4 border-top bg-white shadow-lg" style="z-index: 20;">
                    <div class="total-box d-flex justify-content-between align-items-center mb-3">
                        <span class="h6 mb-0 text-success opacity-75">Total Tagihan</span>
                        <span class="h3 fw-bold mb-0">Rp <?php echo number_format($totalCart, 0, ',', '.'); ?></span>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="proses_bayar" value="1">
                        
                        <div class="mb-3">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0 fw-bold text-muted">Rp</span>
                                <input type="number" id="inputBayar" name="bayar" class="form-control border-start-0 fw-bold" placeholder="Input Bayar" required <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3 px-1">
                            <span class="text-muted small">Kembalian:</span>
                            <span class="fw-bold text-dark fs-5" id="textKembalian">Rp 0</span>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 rounded-3 shadow-sm" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            <i class="fa-regular fa-paper-plane me-2"></i> PROSES BAYAR
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        const total = <?php echo $totalCart; ?>;
        const inputBayar = document.getElementById('inputBayar');
        const textKembalian = document.getElementById('textKembalian');

        if(inputBayar) {
            inputBayar.addEventListener('input', function() {
                let bayar = parseInt(this.value) || 0;
                let kembali = bayar - total;
                
                if(kembali < 0) {
                    textKembalian.innerText = "Kurang Rp " + new Intl.NumberFormat('id-ID').format(Math.abs(kembali));
                    textKembalian.classList.add('text-danger');
                    textKembalian.classList.remove('text-dark');
                } else {
                    textKembalian.innerText = "Rp " + new Intl.NumberFormat('id-ID').format(kembali);
                    textKembalian.classList.remove('text-danger');
                    textKembalian.classList.add('text-dark');
                }
            });
        }
    </script>
</body>
</html>