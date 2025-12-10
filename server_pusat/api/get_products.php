<?php
// server_pusat/api/get_products.php

// 1. Matikan Error Reporting agar JSON bersih
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/app.php';
require_once '../config/database.php';

// 2. Auth (Wajib Aktif)
require_once 'auth.php'; 

// Ambil ID Toko
$id_toko_request = $toko_data['id_toko']; 
$last_sync = isset($_GET['last_sync']) ? $_GET['last_sync'] : '2000-01-01 00:00:00';

try {
    // 3. QUERY DATA (ANTI DUPLIKAT)
    $query = "SELECT 
                p.id_produk, 
                p.kode_produk, 
                p.nama_produk, 
                p.satuan, 
                p.updated_at,
                
                -- AMBIL STOK (Jika null/kosong dianggap 0)
                COALESCE(SUM(s.jumlah), 0) as stok_toko, 

                -- AMBIL HARGA (Prioritas: Harga Khusus Toko -> Harga Global)
                -- Jika ada harga khusus, pakai itu. Jika tidak, pakai harga global.
                COALESCE(
                    MAX(CASE WHEN h.id_toko = :idtoko THEN h.harga_jual END),
                    MAX(CASE WHEN h.id_toko IS NULL THEN h.harga_jual END)
                ) as harga_jual

              FROM produk p
              
              -- Join Stok (Hanya stok milik toko ini)
              LEFT JOIN stok_toko s ON p.id_produk = s.id_produk AND s.id_toko = :idtoko

              -- Join Harga (Ambil Global ATAU Khusus Toko ini)
              LEFT JOIN harga h ON p.id_produk = h.id_produk 
                   AND h.aktif = 1 
                   AND (h.id_toko IS NULL OR h.id_toko = :idtoko)
                   
              WHERE p.status = 'aktif'
              AND p.updated_at > :last_sync
              
              -- GRUPING AGAR PRODUK TIDAK MUNCUL GANDA
              GROUP BY p.id_produk";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':idtoko'    => $id_toko_request,
        ':last_sync' => $last_sync
    ]);
    
    $data_produk = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Kirim Response JSON
    jsonResponse('success', 'Data produk berhasil diambil', [
        'total_data'  => count($data_produk),
        'server_time' => date('Y-m-d H:i:s'),
        'produk'      => $data_produk
    ]);

} catch (Exception $e) {
    jsonResponse('error', $e->getMessage(), null, 500);
}
?>