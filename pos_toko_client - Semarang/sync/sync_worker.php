<?php
// pos_toko_client/sync/sync_worker.php

// Set waktu eksekusi unlimited (karena sync bisa lama jika data banyak)
set_time_limit(0); 

echo "<h3>🔄 Memulai Proses Sinkronisasi Otomatis...</h3>";
echo "<hr>";

// 1. Jalankan Download Produk
echo "<strong>[1/2] Download Produk:</strong><br>";
include 'pull_produk.php';
echo "<br><br>";

// 2. Jalankan Upload Transaksi
echo "<strong>[2/2] Upload Transaksi:</strong><br>";
include 'push_transaksi.php';
echo "<br><br>";

echo "<hr>";
echo "✅ Proses Selesai pada: " . date('d-m-Y H:i:s');
?>