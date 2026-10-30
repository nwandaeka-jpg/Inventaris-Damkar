<?php
// Catatan: Variabel $user, $isAdmin, dan $page_title harus sudah didefinisikan di file utama
// Fungsi esc() juga diasumsikan sudah ada (di _header.php atau file utama)

// Fungsi pembantu untuk menentukan class 'active'
function isActive($currentPage, $targetPage) {
    // Menangani kasus di mana halaman transaksi memiliki query parameter (misal: transactions.php?tipe=masuk)
    if ($currentPage === 'transactions.php' && strpos($targetPage, 'transactions.php') !== false) {
        return 'active';
    }
    return (basename($currentPage) === basename($targetPage)) ? 'active' : '';
}

// Ambil nama file saat ini untuk menentukan menu aktif
$currentFile = basename($_SERVER['PHP_SELF']);
?>

<!-- SIDEBAR -->
<div class="sidebar d-none d-lg-block">
    <div class="sidebar-header">
        <a class="navbar-brand brand-logo d-flex align-items-center" href="dashboard.php">
            <img src="damkar.png" alt="Damkar Logo" class="me-2">
            <span class="firetrack-text">FireTrack</span>
        </a>
    </div>

    <div class="sidebar-menu list-group list-group-flush">

        <a href="dashboard.php" class="list-group-item list-group-item-action <?= isActive($currentFile, 'dashboard.php') ?>">
            <i class="fa fa-tachometer-alt fa-fw me-2"></i> Dashboard
        </a>
         <?php if ($isAdmin): ?>
            <a href="categories.php" class="list-group-item list-group-item-action <?= isActive($currentFile, 'categories.php') ?>">
                <i class="fa fa-tags fa-fw me-2"></i> Kelola Kategori
            </a>
            <a href="users.php" class="list-group-item list-group-item-action <?= isActive($currentFile, 'users.php') ?>">
                <i class="fa fa-users-cog fa-fw me-2"></i> Manajemen Petugas
            </a>
        <?php endif; ?>

        <a href="inventory.php" class="list-group-item list-group-item-action <?= isActive($currentFile, 'inventory.php') ?>">
            <i class="fa fa-boxes fa-fw me-2"></i> Inventaris Barang
        </a>

        <a href="transactions.php" class="list-group-item list-group-item-action <?= isActive($currentFile, 'transactions.php') ?>">
            <i class="fa fa-exchange-alt fa-fw me-2"></i> Transaksi Stok
        </a>

        <a href="reports.php" class="list-group-item list-group-item-action <?= isActive($currentFile, 'reports.php') ?>">
            <i class="fa fa-file-alt fa-fw me-2"></i> Laporan
        </a>

       

        
        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fa fa-sign-out-alt fa-fw me-2"></i> Logout
        </a>
    </div>
</div>
