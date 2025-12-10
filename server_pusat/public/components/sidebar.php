<div class="col-lg-2 d-none d-lg-block bg-white sidebar shadow-sm" style="min-height: 100vh; position: fixed; z-index: 100;">
    <div class="d-flex align-items-center justify-content-center py-4 border-bottom">
        <div class="text-center">
            <i class="fa-solid fa-network-wired text-primary fa-2x mb-2"></i>
            <h6 class="fw-bold text-dark mb-0">RETAIL PUSAT</h6>
            <small class="text-muted" style="font-size: 0.7rem;">Admin Panel v1.0</small>
        </div>
    </div>

    <div class="p-3">
        <p class="text-uppercase text-muted fw-bold small mb-3 px-2" style="font-size: 0.7rem;">Menu Utama</p>

        <nav class="nav flex-column gap-1">
            <a href="index.php" class="nav-link rounded-3 <?php echo ($page == 'dashboard') ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                <i class="fa-solid fa-chart-line me-2" style="width: 20px;"></i> Dashboard
            </a>
            <a href="products.php" class="nav-link rounded-3 <?php echo ($page == 'products') ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                <i class="fa-solid fa-box me-2" style="width: 20px;"></i> Data Produk
            </a>
            <a href="harga.php" class="nav-link rounded-3 <?php echo ($page == 'harga') ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                <i class="fa-solid fa-tags me-2" style="width: 20px;"></i> Kelola Harga
            </a>
            <a href="stok.php" class="nav-link rounded-3 <?php echo ($page == 'stok') ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                <i class="fa-solid fa-truck-ramp-box me-2" style="width: 20px;"></i> Distribusi Stok
            </a>
            <a href="laporan.php" class="nav-link rounded-3 <?php echo ($page == 'laporan') ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                <i class="fa-solid fa-file-invoice me-2" style="width: 20px;"></i> Laporan Transaksi
            </a>
            <a href="toko.php" class="nav-link rounded-3 <?php echo ($page == 'toko') ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                <i class="fa-solid fa-store me-2" style="width: 20px;"></i> Kelola Cabang
            </a>
        </nav>

        <p class="text-uppercase text-muted fw-bold small mt-4 mb-3 px-2" style="font-size: 0.7rem;">Akun</p>
        <nav class="nav flex-column">
            <a href="logout.php" class="nav-link text-danger rounded-3">
                <i class="fa-solid fa-right-from-bracket me-2" style="width: 20px;"></i> Logout
            </a>
        </nav>
    </div>
</div>