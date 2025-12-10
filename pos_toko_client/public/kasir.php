<?php
ob_start();
session_start();
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../controllers/TransaksiController.php';

if (!isset($_SESSION['kasir_logged_in'])) {
    header("Location: index.php");
    exit;
}

$trxController = new TransaksiController($db_lokal);
$trxSukses = null;

// --- LOGIC KERANJANG ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id_produk'];
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
    header("Location: kasir.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $act = $_GET['action'];
    if (isset($_SESSION['cart'][$id])) {
        if ($act == 'plus') $_SESSION['cart'][$id]['qty']++;
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

// --- LOGIC BAYAR ---
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
            $jsAlert = "<script>alert($pesanError);</script>";
        }
    }
}

$keyword = $_GET['cari'] ?? '';
$produkList = $trxController->cariProduk($keyword);

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
            height: 100vh;
            overflow: hidden;
        }

        /* Layout & Scroll */
        .product-section { height: 100vh; overflow-y: auto; padding-bottom: 80px; }
        .cart-section { 
            background: white; height: 100vh; border-left: 1px solid #e3e6f0; 
            display: flex; flex-direction: column; box-shadow: -5px 0 20px rgba(0,0,0,0.05); 
        }
        
        /* Product Card */
        .product-card {
            border: none; border-radius: 0.75rem; background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            transition: all 0.2s; cursor: pointer; height: 100%; border-left: 4px solid transparent;
        }
        .product-card:hover {
            transform: translateY(-5px); box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.1);
            border-left: 4px solid #4e73df;
        }

        /* Search Bar */
        .search-input {
            border-radius: 2rem; background-color: #fff; border: 1px solid #e3e6f0;
            padding-left: 1.5rem; height: 45px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
        }
        .search-input:focus { border-color: #4e73df; box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25); }

        /* Qty Button */
        .btn-qty {
            width: 28px; height: 28px; padding: 0; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; transition: 0.2s;
        }
        .btn-qty:hover { transform: scale(1.1); }

        /* Total Box */
        .total-wrapper {
            background: #f8f9fc; border-radius: 1rem; padding: 1.5rem; border: 1px solid #e3e6f0;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-7 col-lg-8 product-section pt-4 px-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 sticky-top pt-2 pb-3" style="background-color: #f8f9fc; z-index: 10;">
                    <div>
                        <a href="dashboard.php" class="btn btn-light shadow-sm rounded-pill text-secondary fw-bold px-3">
                            <i class="fa-solid fa-arrow-left me-2"></i>Dashboard
                        </a>
                    </div>
                    <form class="flex-grow-1 ms-4" style="max-width: 500px;">
                        <div class="input-group">
                            <input class="form-control search-input" type="search" name="cari" placeholder="Scan Barcode / Cari Produk..." value="<?php echo htmlspecialchars($keyword); ?>" autofocus>
                            <button class="btn btn-primary rounded-pill ms-2 px-4 shadow-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </form>
                </div>

                <div class="row g-3">
                    <?php if(empty($produkList)): ?>
                        <div class="col-12 text-center py-5 text-muted opacity-50">
                            <i class="fa-solid fa-box-open fa-4x mb-3"></i>
                            <h5>Produk Tidak Ditemukan</h5>
                        </div>
                    <?php endif; ?>

                    <?php foreach($produkList as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <form method="POST" class="h-100">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="id_produk" value="<?php echo $p['id_produk']; ?>">
                            <input type="hidden" name="kode_produk" value="<?php echo $p['kode_produk']; ?>">
                            <input type="hidden" name="nama_produk" value="<?php echo $p['nama_produk']; ?>">
                            <input type="hidden" name="harga" value="<?php echo $p['harga_jual']; ?>">
                            
                            <button type="submit" class="product-card w-100 text-start p-3 d-flex flex-column h-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-light text-secondary border"><?php echo $p['kode_produk']; ?></span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1 text-truncate"><?php echo $p['nama_produk']; ?></h6>
                                <small class="text-muted mb-3 d-block"><?php echo $p['satuan']; ?></small>
                                <div class="mt-auto d-flex justify-content-between align-items-end border-top pt-2">
                                    <span class="fw-bold text-primary fs-5">Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></span>
                                    <div class="btn btn-sm btn-primary rounded-circle" style="width:30px; height:30px; padding:0; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-plus text-white"></i></div>
                                </div>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-5 col-lg-4 cart-section">
                
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Keranjang</h5>
                    <?php if(!empty($_SESSION['cart'])): ?>
                        <a href="?reset=1" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold bg-danger bg-opacity-10 border-0 text-danger" onclick="return confirm('Reset keranjang?')">Reset</a>
                    <?php endif; ?>
                </div>

                <div class="flex-grow-1 overflow-y-auto p-3" style="background-color: #fff;">
                    <?php if(empty($_SESSION['cart'])): ?>
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                            <i class="fa-solid fa-basket-shopping fa-3x mb-3"></i><p class="fw-bold">Keranjang Kosong</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach($_SESSION['cart'] as $id => $item): ?>
                            <div class="card border-0 shadow-sm rounded-3 bg-light">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark"><?php echo $item['nama']; ?></div>
                                        <div class="text-muted small">Rp <?php echo number_format($item['harga']); ?></div>
                                    </div>
                                    <div class="d-flex align-items-center bg-white rounded-pill border px-1 py-1 mx-3 shadow-sm">
                                        <a href="?action=minus&id=<?php echo $id; ?>" class="btn btn-qty btn-light text-secondary"><i class="fa-solid fa-minus"></i></a>
                                        <span class="mx-2 fw-bold text-dark" style="min-width: 20px; text-align: center;"><?php echo $item['qty']; ?></span>
                                        <a href="?action=plus&id=<?php echo $id; ?>" class="btn btn-qty btn-primary text-white"><i class="fa-solid fa-plus"></i></a>
                                    </div>
                                    <div class="text-end fw-bold text-dark" style="min-width: 80px;">
                                        Rp <?php echo number_format($item['harga'] * $item['qty']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-4 bg-white border-top shadow-lg" style="z-index: 20;">
                    <div class="total-wrapper mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-bold text-uppercase">Total Tagihan</span>
                            <span class="h3 fw-bold mb-0 text-primary">Rp <?php echo number_format($totalCart, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-pill shadow-sm" onclick="openPaymentModal()" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-wallet me-2"></i> BAYAR SEKARANG
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPayment" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
                <div class="modal-header bg-primary text-white border-bottom-0" style="border-radius: 1rem 1rem 0 0;">
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
                        <div class="card border-0 shadow-sm p-3 mb-3">
                            <label class="form-label small fw-bold text-secondary">Metode Pembayaran</label>
                            <select name="metode_pembayaran" id="selectMetode" class="form-select border-0 bg-light fw-bold text-dark mb-2" onchange="toggleCashInput()">
                                <option value="Tunai">💵 Tunai / Cash</option>
                                <option value="QRIS">📱 QRIS / E-Wallet</option>
                                <option value="Debit">💳 Debit Card</option>
                            </select>
                        </div>
                        <div id="divTunai">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Uang Diterima</label>
                                <input type="number" id="inputBayar" name="bayar" class="form-control form-control-lg fw-bold border-0 shadow-sm" placeholder="0">
                            </div>
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-white border shadow-sm flex-fill" onclick="setCash(<?php echo $totalCart; ?>)">Pas</button>
                                <button type="button" class="btn btn-white border shadow-sm flex-fill" onclick="setCash(50000)">50k</button>
                                <button type="button" class="btn btn-white border shadow-sm flex-fill" onclick="setCash(100000)">100k</button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm border">
                                <span class="text-muted fw-bold">Kembalian:</span>
                                <span class="fw-bold fs-4 text-success" id="textKembalian">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 bg-light p-3" style="border-radius: 0 0 1rem 1rem;">
                        <button type="button" onclick="submitTransaksi()" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                            PROSES TRANSAKSI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if($trxSukses): ?>
    <div class="modal fade" id="modalSukses" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center p-4 border-0 shadow-lg" style="border-radius: 1rem;">
                <div class="mb-3 text-success">
                    <div style="width: 80px; height: 80px; background: #d1e7dd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                        <i class="fa-solid fa-check fa-3x"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark">Transaksi Berhasil!</h5>
                <p class="text-muted small mb-4">Kembalian: <br><strong class="fs-5 text-dark">Rp <?php echo number_format($trxSukses['kembalian'], 0, ',', '.'); ?></strong></p>
                <div class="d-grid gap-2">
                    <a href="cetak_struk.php?id=<?php echo $trxSukses['id_transaksi']; ?>" target="_blank" class="btn btn-primary fw-bold rounded-pill shadow-sm">
                        <i class="fa-solid fa-print me-2"></i> Cetak Struk
                    </a>
                    <button type="button" class="btn btn-light text-secondary rounded-pill" data-bs-dismiss="modal">Tutup / Baru</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            var myModal = new bootstrap.Modal(document.getElementById('modalSukses'));
            myModal.show();
        }
    </script>
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
            const metode = selectMetode.value;
            const divTunai = document.getElementById('divTunai');
            if (metode === 'Tunai') {
                divTunai.style.display = 'block';
                inputBayar.value = '';
                inputBayar.readOnly = false;
            } else {
                divTunai.style.display = 'none';
                inputBayar.value = totalTagihan; 
                inputBayar.readOnly = true;
            }
        }

        function setCash(amount) {
            inputBayar.value = amount;
            hitungKembalian();
        }

        function hitungKembalian() {
            let bayar = parseInt(inputBayar.value) || 0;
            let kembali = bayar - totalTagihan;
            if (kembali < 0) {
                textKembalian.innerText = "Kurang " + new Intl.NumberFormat('id-ID').format(Math.abs(kembali));
                textKembalian.classList.add('text-danger');
                textKembalian.classList.remove('text-success');
            } else {
                textKembalian.innerText = "Rp " + new Intl.NumberFormat('id-ID').format(kembali);
                textKembalian.classList.remove('text-danger');
                textKembalian.classList.add('text-success');
            }
        }

        function submitTransaksi() {
            const metode = selectMetode.value;
            const bayar = parseInt(inputBayar.value) || 0;
            
            if (metode === 'Tunai') {
                if (bayar <= 0) { alert("Harap masukkan uang!"); inputBayar.focus(); return; }
                if (bayar < totalTagihan) { alert("Uang pembayaran kurang!"); inputBayar.focus(); return; }
            }
            formBayar.submit();
        }

        if(inputBayar) { inputBayar.addEventListener('input', hitungKembalian); }
    </script>
</body>
</html>