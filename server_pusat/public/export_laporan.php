<?php
require_once '../config/database.php';
require_once '../controllers/TransaksiController.php';

$trxController = new TransaksiController($db);

// Ambil Parameter Filter
$filters = [
    'tgl_mulai' => $_GET['tgl_mulai'] ?? date('Y-m-d'),
    'tgl_akhir' => $_GET['tgl_akhir'] ?? date('Y-m-d'),
    'id_toko'   => $_GET['id_toko'] ?? '',
    'metode'    => $_GET['metode'] ?? ''
];

$data = $trxController->getLaporanFilter($filters);
$type = $_GET['type'] ?? 'csv';
$filename = "Laporan_Transaksi_" . date('Ymd_His');

if ($type == 'excel') {
    // Export Excel (Native HTML Table Method)
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$filename.xls");
    
    echo "<table border='1'>";
    echo "<tr>
            <th>Waktu</th>
            <th>ID Transaksi</th>
            <th>Cabang</th>
            <th>Metode</th>
            <th>Total</th>
            <th>Waktu Sync</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>{$row['waktu_transaksi']}</td>";
        echo "<td>{$row['id_transaksi']}</td>";
        echo "<td>{$row['nama_toko']}</td>";
        echo "<td>{$row['metode_pembayaran']}</td>";
        echo "<td>{$row['total_transaksi']}</td>";
        echo "<td>{$row['waktu_sync']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} else {
    // Export CSV
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=$filename.csv");
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Waktu', 'ID Transaksi', 'Cabang', 'Metode', 'Total', 'Waktu Sync']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['waktu_transaksi'],
            $row['id_transaksi'],
            $row['nama_toko'],
            $row['metode_pembayaran'],
            $row['total_transaksi'],
            $row['waktu_sync']
        ]);
    }
    fclose($output);
}
?>