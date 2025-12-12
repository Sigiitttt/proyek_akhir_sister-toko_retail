<?php
// ==========================================================================
// 1. LOGIKA PHP ASLI (TIDAK DIUBAH / TIDAK DIDOWNGRADE)
// ==========================================================================
ob_start();
session_start();
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../controllers/TransaksiController.php';
require_once '../models/ProdukModel.php'; // Load Model Produk

if (!isset($_SESSION['kasir_logged_in'])) {
    header("Location: index.php");
    exit;
}

$trxController = new TransaksiController($db_lokal);
// Panggil Model Produk yang baru diedit (Tanpa Limit)
$produkModel = new ProdukModel($db_lokal); 

$trxSukses = null;

// --- LOGIC KERANJANG (Sama seperti punya Anda) ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id_produk'];
    // Validasi stok di backend juga biar aman
    $stokSaatIni = $_POST['stok_saat_ini']; 
    
    if ($stokSaatIni > 0) {
        if (isset($_SESSION['cart'][$id])) {
            if ($_SESSION['cart'][$id]['qty'] < $stokSaatIni) {
                $_SESSION['cart'][$id]['qty']++;
            }
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $id,
                'kode' => $_POST['kode_produk'],
                'nama' => $_POST['nama_produk'],
                'harga' => $_POST['harga'],
                'qty' => 1,
                'max_stok' => $stokSaatIni // Simpan max stok di session
            ];
        }
    }
    header("Location: kasir.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $act = $_GET['action'];
    if (isset($_SESSION['cart'][$id])) {
        if ($act == 'plus') {
            // Cek stok max sebelum nambah
            if($_SESSION['cart'][$id]['qty'] < $_SESSION['cart'][$id]['max_stok']) {
                $_SESSION['cart'][$id]['qty']++;
            }
        }
        if ($act == 'minus') {
            $_SESSION['cart'][$id]['qty']--;
            if ($_SESSION['cart'][$id]['qty'] <= 0) unset($_SESSION['cart'][$id]);
        }
        if ($act == 'remove') unset($_SESSION['cart'][$id]);
    }
    header("Location: kasir.php");
    exit;
}

if (isset($_GET['reset'])) {
    unset($_SESSION['cart']);
    header("Location: kasir.php");
    exit;
}

// --- LOGIC BAYAR (Sama seperti punya Anda) ---
if (isset($_POST['proses_bayar'])) {
    if (!empty($_SESSION['cart'])) {
        $bayar = $_POST['bayar'];
        $metode = $_POST['metode_pembayaran'] ?? 'Tunai'; 
        $kasir_id = $_SESSION['kasir_id'];

        $hasil = $trxController->simpanTransaksi($_SESSION['cart'], $bayar, $kasir_id, $metode);

        if ($hasil['status'] == 'success') {
            unset($_SESSION['cart']);
            $trxSukses = $hasil; 
        } else {
            $pesanError = json_encode("Gagal: " . $hasil['message']);
            echo "<script>alert($pesanError);</script>";
        }
    }
}

// Ambil Data Produk (Load All tanpa Limit)
$keyword = $_GET['cari'] ?? '';
$produkList = $produkModel->getAll($keyword);

$totalCart = 0;
if (isset($_SESSION['cart'])) foreach ($_SESSION['cart'] as $c) $totalCart += ($c['harga'] * $c['qty']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - POS System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; height: 100vh; overflow: hidden; }
        .layout-container { display: flex; height: 100vh; }
        
        /* KIRI: AREA PRODUK */
        .product-area { flex: 1; padding: 20px; overflow-y: auto; height: 100vh; }

        .search-container { position: sticky; top: 0; z-index: 10; background: #f0f2f5; padding-bottom: 15px; }
        .search-input { border-radius: 50px; padding-left: 20px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: 50px; }

        /* --- STYLING CARD PRODUK --- */
        .product-card {
            background: white; border: none; border-radius: 12px; transition: all 0.2s;
            cursor: pointer; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            height: 100%; display: flex; flex-direction: column; position: relative;
            text-align: left; width: 100%; padding: 0; /* Reset button style */
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: 1px solid #4e73df; }
        
        /* EFEK STOK HABIS */
        .card-disabled {
            opacity: 0.6; background-color: #f8f9fa; cursor: not-allowed !important; filter: grayscale(100%);
        }
        .card-disabled:hover { transform: none; box-shadow: none; border: none; }
        
        .ribbon-habis {
            position: absolute; top: 10px; right: 10px; background: #e74a3b; color: white;
            font-size: 0.65rem; padding: 2px 8px; border-radius: 4px; font-weight: bold; z-index: 2;
        }

        .card-img-top {
            height: 110px; background-color: #e9ecef; display: flex; align-items: center; justify-content: center;
            color: #adb5bd; font-size: 2rem;
        }
        .card-body { padding: 12px; display: flex; flex-direction: column; flex-grow: 1; width: 100%; }
        .product-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; line-height: 1.3; color: #333; }
        .product-price { color: #4e73df; font-weight: 700; font-size: 1rem; margin-top: auto; }
        .product-stock { font-size: 0.75rem; color: #888; margin-bottom: 8px; }

        /* KANAN: KERANJANG */
        .cart-area { 
            width: 400px; background: white; border-left: 1px solid #e3e6f0; 
            display: flex; flex-direction: column; height: 100vh; box-shadow: -5px 0 15px rgba(0,0,0,0.05);
        }
        .cart-header { padding: 20px; border-bottom: 1px solid #eee; background: #fff; }
        .cart-items { flex: 1; overflow-y: auto; padding: 0; }
        .cart-item { padding: 15px 20px; border-bottom: 1px solid #f8f9fc; display: flex; justify-content: space-between; align-items: center; }
        .cart-footer { padding: 20px; background: #f8f9fc; border-top: 1px solid #e3e6f0; }
        
        .btn-qty { width: 28px; height: 28px; padding: 0; border-radius: 50%; border: 1px solid #ddd; background: white; color: #555; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-qty:hover { background: #eee; color: #333; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    </style>
</head>
<body>

<div class="layout-container">
    
    <div class="product-area">
        <div class="search-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="dashboard.php" class="btn btn-light rounded-pill shadow-sm text-secondary fw-bold px-3">
                    <i class="fa-solid fa-arrow-left me-2"></i> Dashboard
                </a>
                <div class="text-end">
                    <h5 class="fw-bold mb-0 text-dark">Kasir Toko</h5>
                    <small class="text-muted"><?php echo date('d M Y'); ?></small>
                </div>
            </div>
            
            <div class="position-relative">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Cari nama produk / scan barcode..." onkeyup="filterProduk()">
                <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="right: 20px; top: 18px;"></i>
            </div>
        </div>

        <div class="row g-3" id="productGrid">
            <?php foreach($produkList as $p): ?>
                <?php 
                    $stok = $p['stok_lokal'];
                    $isHabis = ($stok <= 0);
                    $cardClass = $isHabis ? 'card-disabled' : '';
                ?>
                <div class="col-6 col-md-4 col-lg-3 product-col" data-name="<?php echo strtolower($p['nama_produk']); ?>" data-kode="<?php echo strtolower($p['kode_produk']); ?>">
                    
                    <?php if (!$isHabis): ?>
                        <form method="POST" class="h-100">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="id_produk" value="<?php echo $p['id_produk']; ?>">
                            <input type="hidden" name="kode_produk" value="<?php echo $p['kode_produk']; ?>">
                            <input type="hidden" name="nama_produk" value="<?php echo $p['nama_produk']; ?>">
                            <input type="hidden" name="harga" value="<?php echo $p['harga_jual']; ?>">
                            <input type="hidden" name="stok_saat_ini" value="<?php echo $stok; ?>">
                            
                            <button type="submit" class="product-card <?php echo $cardClass; ?>">
                                <div class="card-img-top">
                                    <?php if(stripos($p['nama_produk'], 'susu') !== false || stripos($p['nama_produk'], 'teh') !== false): ?>
                                        <i class="fa-solid fa-mug-hot text-warning opacity-50"></i>
                                    <?php elseif(stripos($p['nama_produk'], 'mie') !== false || stripos($p['nama_produk'], 'roti') !== false): ?>
                                        <i class="fa-solid fa-burger text-danger opacity-50"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-box-open text-primary opacity-50"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="product-title text-truncate-2"><?php echo $p['nama_produk']; ?></div>
                                    <div class="product-stock">
                                        <i class="fa-solid fa-cubes me-1"></i> Stok: <span class="text-dark fw-bold"><?php echo $stok; ?></span>
                                    </div>
                                    <div class="product-price">Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></div>
                                </div>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="product-card <?php echo $cardClass; ?>">
                            <div class="ribbon-habis">HABIS</div>
                            <div class="card-img-top">
                                <i class="fa-solid fa-ban text-secondary opacity-25"></i>
                            </div>
                            <div class="card-body">
                                <div class="product-title text-truncate-2"><?php echo $p['nama_produk']; ?></div>
                                <div class="product-stock text-danger fw-bold">Stok Habis</div>
                                <div class="product-price text-muted">Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="noResult" class="text-center py-5 d-none">
            <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">Produk tidak ditemukan.</p>
        </div>
    </div>

    <div class="cart-area">
        <div class="cart-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Keranjang</h5>
            <?php if(!empty($_SESSION['cart'])): ?>
                <a href="?reset=1" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Kosongkan keranjang?')">Reset</a>
            <?php endif; ?>
        </div>

        <div class="cart-items" id="cartItems">
            <?php if(empty($_SESSION['cart'])): ?>
                <div class="text-center py-5 mt-5 text-muted opacity-50">
                    <i class="fa-solid fa-basket-shopping fa-3x mb-3"></i>
                    <p>Scan atau pilih barang.</p>
                </div>
            <?php else: ?>
                <?php foreach($_SESSION['cart'] as $id => $item): ?>
                <div class="cart-item">
                    <div style="flex:1;">
                        <div class="fw-bold text-dark text-truncate" style="max-width: 150px;"><?php echo $item['nama']; ?></div>
                        <div class="text-muted small">Rp <?php echo number_format($item['harga'],0,',','.'); ?></div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="?action=minus&id=<?php echo $id; ?>" class="btn-qty"><i class="fa-solid fa-minus"></i></a>
                        <span class="fw-bold" style="min-width: 20px; text-align: center;"><?php echo $item['qty']; ?></span>
                        <a href="?action=plus&id=<?php echo $id; ?>" class="btn-qty bg-primary text-white border-primary"><i class="fa-solid fa-plus"></i></a>
                    </div>
                    <div class="text-end fw-bold ms-3" style="min-width: 70px;">
                        <?php echo number_format($item['harga'] * $item['qty'], 0, ',', '.'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="cart-footer">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Item</span>
                <span class="fw-bold"><?php echo count($_SESSION['cart'] ?? []); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="h5 fw-bold text-dark">Total Bayar</span>
                <span class="h4 fw-bold text-primary">Rp <?php echo number_format($totalCart, 0, ',', '.'); ?></span>
            </div>
            
            <button type="button" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm" onclick="openPaymentModal()" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-wallet me-2"></i> BAYAR
            </button>
        </div>
    </div>

</div>

<div class="modal fade" id="modalPayment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title fw-bold">Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formBayar">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="proses_bayar" value="1">
                    <div class="text-center mb-4">
                        <small class="text-muted text-uppercase fw-bold">Total Tagihan</small>
                        <h1 class="fw-bold text-dark mt-1">Rp <?php echo number_format($totalCart, 0, ',', '.'); ?></h1>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Metode Pembayaran</label>
                        <select name="metode_pembayaran" id="selectMetode" class="form-select fw-bold" onchange="toggleCashInput()">
                            <option value="Tunai">💵 Tunai / Cash</option>
                            <option value="QRIS">📱 QRIS / E-Wallet</option>
                            <option value="Debit">💳 Debit Card</option>
                        </select>
                    </div>

                    <div id="divTunai">
                        <label class="form-label small fw-bold text-secondary">Uang Diterima</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white fw-bold">Rp</span>
                            <input type="number" id="inputBayar" name="bayar" class="form-control form-control-lg fw-bold" placeholder="0">
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-white border flex-fill" onclick="setCash(<?php echo $totalCart; ?>)">Pas</button>
                            <button type="button" class="btn btn-white border flex-fill" onclick="setCash(50000)">50k</button>
                            <button type="button" class="btn btn-white border flex-fill" onclick="setCash(100000)">100k</button>
                        </div>
                        <div class="d-flex justify-content-between p-3 bg-white rounded border">
                            <span class="text-muted fw-bold">Kembalian:</span>
                            <span class="fw-bold fs-5 text-success" id="textKembalian">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" onclick="submitTransaksi()" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">PROSES TRANSAKSI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($trxSukses): ?>
<div class="modal fade" id="modalSukses" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-4 border-0 shadow-lg">
            <div class="mb-3 text-success">
                <i class="fa-solid fa-circle-check fa-4x"></i>
            </div>
            <h5 class="fw-bold">Transaksi Berhasil!</h5>
            <p class="text-muted small">Kembalian: <strong class="text-dark">Rp <?php echo number_format($trxSukses['kembalian'], 0, ',', '.'); ?></strong></p>
            <div class="d-grid gap-2">
                <a href="cetak_struk.php?id=<?php echo $trxSukses['id_transaksi']; ?>" target="_blank" class="btn btn-primary rounded-pill"><i class="fa-solid fa-print me-2"></i> Cetak Struk</a>
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<script> window.onload = function() { new bootstrap.Modal(document.getElementById('modalSukses')).show(); } </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const totalTagihan = <?php echo $totalCart; ?>;
    const modalPayment = new bootstrap.Modal(document.getElementById('modalPayment'));
    const inputBayar = document.getElementById('inputBayar');
    const textKembalian = document.getElementById('textKembalian');
    const selectMetode = document.getElementById('selectMetode');
    const formBayar = document.getElementById('formBayar');

    function openPaymentModal() {
        inputBayar.value = '';
        textKembalian.innerText = 'Rp 0';
        modalPayment.show();
        setTimeout(() => inputBayar.focus(), 500);
    }

    function toggleCashInput() {
        const isTunai = selectMetode.value === 'Tunai';
        document.getElementById('divTunai').style.display = isTunai ? 'block' : 'none';
        if (!isTunai) inputBayar.value = totalTagihan;
    }

    function setCash(amount) {
        inputBayar.value = amount;
        hitungKembalian();
    }

    function hitungKembalian() {
        let bayar = parseInt(inputBayar.value) || 0;
        let kembali = bayar - totalTagihan;
        textKembalian.innerText = "Rp " + new Intl.NumberFormat('id-ID').format(kembali);
        
        if(kembali < 0) {
            textKembalian.classList.remove('text-success'); textKembalian.classList.add('text-danger');
        } else {
            textKembalian.classList.remove('text-danger'); textKembalian.classList.add('text-success');
        }
    }

    function submitTransaksi() {
        if (selectMetode.value === 'Tunai') {
            let bayar = parseInt(inputBayar.value) || 0;
            if (bayar < totalTagihan) { alert("Uang kurang!"); return; }
        }
        formBayar.submit();
    }

    // SEARCH FILTER JS (Cari tanpa reload)
    function filterProduk() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const cards = document.getElementsByClassName('product-col');
        let found = false;

        for (let i = 0; i < cards.length; i++) {
            let name = cards[i].getAttribute('data-name');
            let kode = cards[i].getAttribute('data-kode');
            if (name.includes(input) || kode.includes(input)) {
                cards[i].style.display = ""; found = true;
            } else {
                cards[i].style.display = "none";
            }
        }
        document.getElementById('noResult').classList.toggle('d-none', found);
    }

    if(inputBayar) { inputBayar.addEventListener('input', hitungKembalian); }
</script>

</body>
</html>