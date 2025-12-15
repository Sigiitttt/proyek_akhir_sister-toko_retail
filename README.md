# 🛒 Distributed Retail POS System (Offline-First)

Sistem Point of Sale (POS) terdistribusi yang dirancang untuk menangani masalah ketidakstabilan jaringan internet pada operasional retail multi-cabang. Menggunakan arsitektur **Offline-First** untuk memastikan transaksi kasir tetap berjalan meskipun koneksi ke server pusat terputus.

## 🚀 Fitur Utama

- **Offline-First Architecture**: Kasir cabang dapat melakukan transaksi tanpa internet menggunakan database lokal.
- **Data Synchronization**: Mekanisme sinkronisasi dua arah (Pull Master Data & Push Transaction Reports).
- **Centralized Stock Management**: Administrator pusat dapat mengelola stok global dan mendistribusikannya ke berbagai cabang.
- **Dynamic Pricing**: Kemampuan mengatur harga yang berbeda untuk tiap daerah/cabang.
- **RESTful API**: Komunikasi data antar node menggunakan format JSON yang ringan.
- **Ngrok Integration**: Implementasi tunneling untuk akses publik pada lingkungan localhost.

## 🏗️ Arsitektur Sistem

Sistem ini menerapkan prinsip **AP (Availability & Partition Tolerance)** dari Teorema CAP, menjamin ketersediaan layanan meskipun terjadi partisi jaringan.

### Komponen:
1. **Server Pusat**: Bertindak sebagai *Master Node* (Pusat Data & Laporan).
2. **Client Cabang**: Bertindak sebagai *Replica Node* (Operasional Kasir).
3. **Ngrok Tunnel**: Sebagai jembatan HTTPS lintas jaringan.

## 🛠️ Teknologi yang Digunakan

- **Language**: PHP 8.x (Native)
- **Database**: MySQL / MariaDB (PDO Extension)
- **Frontend**: Bootstrap 5, Chart.js, Select2
- **Connectivity**: REST API, JSON, Ngrok
- **Environment**: Laragon / XAMPP

## 📦 Instalasi

### 1. Server Pusat
1. Clone repositori ini.
2. Import database `retail_pusat.sql` ke phpMyAdmin.
3. Sesuaikan konfigurasi di `server_pusat/config/database.php`.
4. Jalankan Ngrok: `ngrok http 80 --domain=YOUR_DOMAIN.ngrok-free.dev`.

### 2. Client Cabang
1. Copy folder `pos_toko_client` ke laptop kasir.
2. Import database `retail_lokal.sql` ke phpMyAdmin lokal.
3. Update `config/app.php` dengan URL Ngrok Server Pusat Anda.
4. Login menggunakan akun kasir default.

## 🔄 Mekanisme Sinkronisasi

[Image of distributed data synchronization flow diagram]

1. **Pull (Ambil Data)**: Client melakukan request GET ke server untuk memperbarui katalog produk, harga, dan jatah stok.
2. **Push (Setor Data)**: Client mengirimkan data transaksi yang terkumpul (is_synced = 0) secara massal (batch) ke server pusat melalui request POST.
3. **Eventual Consistency**: Stok di pusat akan diperbarui secara otomatis setelah laporan dari cabang berhasil diproses oleh API.

## 📸 Tampilan Dashboard

| Dashboard Server Pusat | Kasir Cabang (POS) |
|---|---|
| ![Dashboard Server](https://via.placeholder.com/400x250?text=Dashboard+Server+Pusat) 
<img width="1891" height="976" alt="image" src="https://github.com/user-attachments/assets/a5ab0f92-aadc-4c36-a3fa-c144a69ff367" />


| ![Kasir Client](https://via.placeholder.com/400x250?text=Antarmuka+Kasir+POS) |
<img width="1884" height="972" alt="image" src="https://github.com/user-attachments/assets/7aa26fce-b5ad-4b11-a606-ebc52d642ccc" />


---
Dibuat untuk Tugas Besar Mata Kuliah **Sistem Terdistribusi**.
