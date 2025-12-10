<?php
session_start();
require_once '../config/database.php';
require_once '../config/app.php';

if (!isset($_GET['id'])) die("ID tidak ditemukan");
$stmt = $db_lokal->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
$stmt->execute([$_GET['id']]);
$trx = $stmt->fetch();
if (!$trx) die("Data tidak ditemukan");

$stmtD = $db_lokal->prepare("SELECT d.*, p.nama_produk FROM detail_transaksi d JOIN produk p ON d.id_produk = p.id_produk WHERE d.id_transaksi = ?");
$stmtD->execute([$_GET['id']]);
$items = $stmtD->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Struk #<?php echo $trx['no_struk']; ?></title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 300px; margin: 0 auto; padding: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center">
        <h3>TOKO RETAIL</h3>
        <?php echo date('d/m/Y H:i', strtotime($trx['waktu_transaksi'])); ?>
    </div>
    <div class="line"></div>
    <table>
        <?php foreach($items as $i): ?>
        <tr><td colspan="2"><?php echo $i['nama_produk']; ?></td></tr>
        <tr><td><?php echo $i['qty']; ?> x <?php echo number_format($i['harga_satuan']); ?></td><td class="text-right"><?php echo number_format($i['subtotal']); ?></td></tr>
        <?php endforeach; ?>
    </table>
    <div class="line"></div>
    <table>
        <tr><td>Total</td><td class="text-right"><?php echo number_format($trx['total_transaksi']); ?></td></tr>
        <tr><td>Bayar</td><td class="text-right"><?php echo number_format($trx['bayar']); ?></td></tr>
        <tr><td>Kembali</td><td class="text-right"><?php echo number_format($trx['kembalian']); ?></td></tr>
    </table>
    <div class="text-center no-print" style="margin-top:20px;">
        <button onclick="window.location.href='kasir.php'">Kembali</button>
    </div>
</body>
</html>