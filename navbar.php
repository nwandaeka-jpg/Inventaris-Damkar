<?php
// Pastikan $userName, $userRole, $isAdmin, $userId tersedia dari file utama
// Fungsi pembantu esc() juga diasumsikan tersedia
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg py-3">
    <div class="container-fluid px-4">
        <!-- Logo hanya tampil di mobile, karena di desktop ada di sidebar -->
        <a class="navbar-brand brand-logo d-lg-none" href="dashboard.php">
            <img src="damkar.png" alt="Damkar Logo" class="me-2">
            <span class="firetrack-text">FireTrack</span>
        </a>
        
        <!-- Tombol Mobile Menu Toggle -->
        <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNavMenu">
            <i class="fa fa-bars text-white"></i> Menu
        </button>

        <!-- Dropdown Akun -->
        <div class="d-flex align-items-center ms-auto">
            <!-- Selamat datang message for desktop -->
            <span class="text-white me-3 d-none d-lg-block">
                Selamat datang, <strong class="fw-bold"><?= esc($userName) ?></strong>
            </span>
            
            <div class="dropdown">
                <a class="btn btn-sm text-white dropdown-toggle nav-link-item" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fa fa-user-circle me-1"></i> Akun
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <li class="dropdown-header">
                        Login sebagai:  <?= esc($userName) ?> (<?= esc($userRole) ?>)
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    
                    <!-- Tombol untuk mengelola akun sendiri (Edit Profile) -->
                    <?php if ($isAdmin): ?>
                        <li>
                            <!-- Trigger Modal Edit Profile Admin -->
                            <a class="dropdown-item" href="#" 
                               data-bs-toggle="modal" data-bs-target="#editSelfModal"
                               data-id="<?= $userId ?>" data-nama="<?= esc($userName) ?>">
                                <i class="fa fa-user-circle me-2"></i> Edit Akun Saya
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>

                    <!-- Logout -->
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- MODAL EDIT AKUN SENDIRI (HANYA UNTUK ADMIN) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="editSelfModal" tabindex="-1" aria-labelledby="editSelfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editSelfModalLabel">
                    <i class="fa fa-user-edit me-2"></i> Edit Akun Saya
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $userId ?>">
                <input type="hidden" name="role" value="admin"> <!-- Admin tetap Admin -->
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" value="<?= esc($userName) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ubah Password (Opsional)</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Kosongkan jika tidak ingin mengubah">
                        <small class="text-muted">Username <strong>(<?= esc($user['username']) ?>)</strong> tidak dapat diubah.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-action">
                        <i class="fa fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
