<?php
// =======================
// KONFIGURASI & SESSION
// =======================
if (file_exists('koneksi.php')) {
    require_once 'koneksi.php';
} else {
    die("Sistem tidak dapat terhubung ke database. Harap hubungi administrator.");
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Cek Otentikasi dan Otorisasi (HANYA ADMIN)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php"); // Arahkan ke dashboard jika bukan admin
    exit;
}

$user = $_SESSION['user'];
$userName = $user['nama'] ?? $user['username'];
$userRole = $user['role'] ?? ''; // <-- Tambahkan baris ini agar tidak undefined
$isAdmin = ($user['role'] === 'admin');
$userId = $user['id'] ?? 0;

// Fungsi pembantu
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$isCurrentUser = function ($id) use ($userId) {
    return $id === $userId;
};

// =======================
// LOGIKA PROSES (TAMBAH, EDIT, HAPUS, RESET PASS)
// =======================
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_id = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'add') {
            // --- TAMBAH PENGGUNA BARU (Role default PETUGAS) ---
            $username = esc($_POST['username'] ?? '');
            $nama = esc($_POST['nama'] ?? '');
            $role = 'petugas';
            $password_plain = $_POST['password'] ?? '';

            if (empty($username) || empty($password_plain) || empty($nama)) {
                throw new Exception("Semua field wajib diisi.");
            }

            // Cek duplikasi username
            $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $check_user->store_result();
            if ($check_user->num_rows > 0) {
                throw new Exception("Username sudah digunakan. Harap pilih username lain.");
            }
            $check_user->close();

            $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password_hash, $nama, $role);
            $stmt->execute();
            $success_message = "Petugas <b>$nama</b> berhasil ditambahkan.";
            $stmt->close();

        } elseif ($action === 'edit' && $target_id > 0) {
            // --- EDIT DATA PETUGAS ---
            $nama = esc($_POST['nama'] ?? '');
            $role = 'petugas';

            $stmt = $conn->prepare("UPDATE users SET nama = ?, role = ? WHERE id = ? AND role = 'petugas'");
            $stmt->bind_param("ssi", $nama, $role, $target_id);
            $stmt->execute();
            $success_message = "Data Petugas berhasil diperbarui.";
            $stmt->close();

        } elseif ($action === 'delete' && $target_id > 0) {
            // --- HAPUS PENGGUNA (Hanya Petugas) ---
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'petugas'");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success_message = "Pengguna Petugas berhasil dihapus.";
            } else {
                throw new Exception("Gagal menghapus. Akun tidak ditemukan atau bukan Petugas.");
            }
            $stmt->close();

        } elseif ($action === 'reset_password' && $target_id > 0) {
            // --- RESET PASSWORD ---
            $new_password = $_POST['new_password'] ?? '';
            if (empty($new_password)) {
                throw new Exception("Password baru tidak boleh kosong.");
            }

            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $target_id);
            $stmt->execute();
            $success_message = "Password pengguna berhasil direset.";
            $stmt->close();

        } elseif ($action === 'edit_self' && $target_id === $userId) {
            // --- EDIT AKUN ADMIN SENDIRI ---
            $nama = esc($_POST['nama'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $updates = ["nama = ?"];
            $types = "si";
            $params = [$nama, $target_id];

            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updates[] = "password = ?";
                $types = "ssi";
                array_splice($params, 1, 0, [$password_hash]);
            }

            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $success_message = "Akun Anda berhasil diperbarui.";
            $stmt->close();

            // Refresh session setelah edit mandiri
            $res_user_refresh = $conn->query("SELECT id, username, nama, role FROM users WHERE id = $userId");
            if ($res_user_refresh && $updated_user = $res_user_refresh->fetch_assoc()) {
                $_SESSION['user'] = $updated_user;
            }
        }

    } catch (Exception $e) {
        $error_message = "Gagal melakukan aksi: " . $e->getMessage();
    }
}

// =======================
// AMBIL DATA PENGGUNA (HANYA PETUGAS)
// =======================
$data_users = [];
$res_users = $conn->query("SELECT id, username, nama, role, created_at FROM users WHERE role = 'petugas' ORDER BY nama ASC");
if ($res_users) {
    while ($row = $res_users->fetch_assoc()) {
        $data_users[] = $row;
    }
}


// =======================
// INCLUDES (HEADER, SIDEBAR, NAVBAR)
// =======================
require_once 'header.php';
require_once 'sidebar.php';
require_once 'navbar.php';
?>

<div class="page-wrapper d-flex flex-column flex-grow-1">
    <div class="container-fluid content-area p-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-primary"><i class="fa fa-users me-2"></i> Manajemen Petugas</h1>
            <button class="btn btn-action" data-bs-toggle="modal" data-bs-target="#addEditModal" data-mode="add">
                <i class="fa fa-user-plus me-1"></i> Tambah Petugas Baru
            </button>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i> <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabel Pengguna -->
        <div class="card main-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (empty($data_users)): ?>
                        <div class="alert alert-info m-3 text-center">Belum ada Petugas yang terdaftar.</div>
                    <?php else: ?>
                        <table class="table table-striped table-hover mb-0">
                            <thead class="small text-uppercase bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama & Username</th>
                                    <th>Bergabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($data_users as $u): ?>
                                <tr data-id="<?= $u['id'] ?>" data-nama="<?= esc($u['nama']) ?>" data-role="<?= esc($u['role']) ?>">
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <span class="fw-bold"><?= esc($u['nama']) ?></span><br>
                                        <small class="text-muted"><?= esc($u['username']) ?></small>
                                    </td>
                                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button class="btn btn-sm btn-outline-info me-1 btn-edit" title="Edit Pengguna"
                                            data-bs-toggle="modal" data-bs-target="#addEditModal" data-mode="edit"
                                            data-id="<?= $u['id'] ?>" data-nama="<?= esc($u['nama']) ?>" data-username="<?= esc($u['username']) ?>">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <!-- Reset Password -->
                                        <button class="btn btn-sm btn-outline-warning me-1" title="Reset Password"
                                            data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-id="<?= $u['id'] ?>" data-nama="<?= esc($u['nama']) ?>">
                                            <i class="fa fa-key"></i>
                                        </button>

                                        <!-- Delete -->
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                <?= $isCurrentUser($u['id']) ? 'disabled' : '' ?>
                                                onclick="return confirm('Yakin menghapus pengguna <?= esc($u['nama']) ?>? Transaksi yang dibuat pengguna ini tidak akan terhapus.')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH/EDIT -->
<div class="modal fade" id="addEditModal" tabindex="-1" aria-labelledby="addEditModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addEditModalLabel">Tambah Petugas Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="modal-action" value="add">
        <input type="hidden" name="id" id="modal-id" value="">
        <input type="hidden" name="role" value="petugas">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6 mb-3">
              <label class="form-label">Username<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="username" id="modal-username" required maxlength="50">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Nama Lengkap<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="nama" id="modal-nama" required maxlength="150">
            </div>
            <div class="col-12 mb-3">
              <label class="form-label">Password<span class="text-danger" id="password-star">*</span></label>
              <input type="password" class="form-control" name="password" id="modal-password" required>
              <small class="text-muted" id="password-help">Password hanya diisi saat <b>Tambah</b> atau <b>Reset</b>.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-action"><i class="fa fa-save me-1"></i> Simpan Petugas</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL RESET PASSWORD -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="fa fa-key me-2"></i> Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="id" id="reset-id" value="">
        <div class="modal-body">
          <p>Anda akan mereset password untuk pengguna: <strong id="reset-nama"></strong>.</p>
          <div class="mb-3">
            <label class="form-label">Password Baru<span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="new_password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning text-dark"><i class="fa fa-sync me-1"></i> Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var addEditModal = document.getElementById('addEditModal');
    var resetPasswordModal = document.getElementById('resetPasswordModal');
    var modalPassword = document.getElementById('modal-password');
    var modalUsername = document.getElementById('modal-username');
    var passwordHelp = document.getElementById('password-help');
    var passwordStar = document.getElementById('password-star');

    addEditModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var mode = button.getAttribute('data-mode');

        var modalTitle = addEditModal.querySelector('.modal-title');
        var modalIdInput = addEditModal.querySelector('#modal-id');
        var modalNamaInput = addEditModal.querySelector('#modal-nama');
        var modalActionInput = addEditModal.querySelector('#modal-action');

        if (mode === 'add') {
            modalTitle.textContent = 'Tambah Petugas Baru';
            modalActionInput.value = 'add';
            modalIdInput.value = '';
            modalNamaInput.value = '';
            modalUsername.disabled = false;
            modalUsername.value = '';
            modalPassword.required = true;
            modalPassword.value = '';
            passwordHelp.textContent = 'Password akan di-hash saat disimpan.';
            passwordStar.style.display = 'inline';
        } else if (mode === 'edit') {
            modalTitle.textContent = 'Edit Data Petugas';
            modalActionInput.value = 'edit';
            modalIdInput.value = button.getAttribute('data-id');
            modalNamaInput.value = button.getAttribute('data-nama');
            modalPassword.required = false;
            modalPassword.value = '';
            modalUsername.disabled = true;
            passwordHelp.textContent = 'Kosongkan password jika tidak ingin mengubahnya.';
            passwordStar.style.display = 'none';
            modalUsername.value = button.getAttribute('data-username');
        }
    });

    resetPasswordModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nama = button.getAttribute('data-nama');
        document.getElementById('reset-id').value = id;
        document.getElementById('reset-nama').textContent = nama;
        resetPasswordModal.querySelector('input[name="new_password"]').value = '';
    });
});
</script>
