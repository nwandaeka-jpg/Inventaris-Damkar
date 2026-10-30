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

// Cek Otentikasi
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userName = $user['nama'] ?? $user['username'];
$userRole = ucfirst($user['role'] ?? 'petugas');
$isAdmin = ($user['role'] === 'admin');
$userId = $user['id'];

// HANYA ADMIN YANG BOLEH AKSES HALAMAN INI
if (!$isAdmin) {
    die("<div style='text-align:center; padding: 50px;'>
        <h1 style='color:#D93A2F;'>Akses Ditolak!</h1>
        <p>Hanya Administrator yang dapat mengelola kategori.</p>
        <a href='dashboard.php'>Kembali ke Dashboard</a>
    </div>");
}

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// =======================
// LOGIKA CRUD KATEGORI
// =======================
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nama_kategori = esc($_POST['nama'] ?? '');
    $kategori_id = (int)($_POST['id'] ?? 0);

    if (empty($nama_kategori) && $action !== 'delete') {
        $error_message = "Nama kategori tidak boleh kosong.";
    } else {
        try {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO kategori (nama) VALUES (?)");
                $stmt->bind_param("s", $nama_kategori);
                if ($stmt->execute()) {
                    $success_message = "Kategori '$nama_kategori' berhasil ditambahkan.";
                } else {
                    $error_message = "Gagal menambah kategori. Mungkin nama sudah ada.";
                }
                $stmt->close();
            
            } elseif ($action === 'edit' && $kategori_id > 0) {
                $stmt = $conn->prepare("UPDATE kategori SET nama = ? WHERE id = ?");
                $stmt->bind_param("si", $nama_kategori, $kategori_id);
                if ($stmt->execute()) {
                    $success_message = "Kategori ID: $kategori_id berhasil diperbarui menjadi '$nama_kategori'.";
                } else {
                    $error_message = "Gagal mengedit kategori.";
                }
                $stmt->close();

            } elseif ($action === 'delete' && $kategori_id > 0) {
                // PENTING: Cek apakah kategori digunakan oleh barang
                $check_barang = $conn->query("SELECT COUNT(id) AS total FROM barang WHERE kategori_id = $kategori_id");
                $total_barang = $check_barang ? $check_barang->fetch_assoc()['total'] : 0;

                if ($total_barang > 0) {
                    $error_message = "Gagal menghapus. Kategori ini digunakan oleh $total_barang jenis barang!";
                } else {
                    $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
                    $stmt->bind_param("i", $kategori_id);
                    if ($stmt->execute()) {
                        $success_message = "Kategori ID: $kategori_id berhasil dihapus.";
                    } else {
                        $error_message = "Gagal menghapus kategori.";
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $error_message = "Kesalahan database: " . $e->getMessage();
        }
    }
}

// =======================
// AMBIL DATA KATEGORI
// =======================
$data_kategori = [];
$res_kategori = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
if ($res_kategori) {
    while ($row = $res_kategori->fetch_assoc()) {
        $data_kategori[] = $row;
    }
}

// =======================
// INCLUDES (HEADER, SIDEBAR, NAVBAR)
// =======================
require_once 'header.php'; 
require_once 'sidebar.php';
require_once 'navbar.php';
?>

<!-- MAIN CONTENT AREA -->
<div class="container-fluid main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary"><i class="fa fa-tags me-2"></i> Kelola Kategori Barang</h1>
        <button class="btn btn-action" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fa fa-plus-circle me-1"></i> Tambah Kategori Baru
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

    <!-- Tabel Kategori -->
    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <?php if (empty($data_kategori)): ?>
                    <div class="alert alert-info m-3 text-center">Belum ada kategori yang terdaftar.</div>
                <?php else: ?>
                    <table class="table table-striped table-hover mb-0">
                        <thead class="small text-uppercase bg-light">
                            <tr>
                                <th>No</th>
                                <th>ID Kategori</th>
                                <th>Nama Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($data_kategori as $k): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><span class="badge bg-secondary"><?= $k['id'] ?></span></td>
                                <td class="fw-bold"><?= esc($k['nama']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info me-1" title="Edit"
                                        onclick="openEditModal(<?= $k['id'] ?>, '<?= esc($k['nama'], false) ?>')">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus kategori ID <?= $k['id'] ?>?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
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
        <div class="card-footer text-muted small">
            Total <?= count($data_kategori) ?> kategori terdaftar.
        </div>
    </div>
</div>

<!-- MODAL TAMBAH KATEGORI -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addModalLabel"><i class="fa fa-plus-circle me-2"></i> Tambah Kategori Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="modal-body">
              <label for="nama_add" class="form-label">Nama Kategori<span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="nama_add" name="nama" required maxlength="100">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-action"><i class="fa fa-save me-1"></i> Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDIT KATEGORI -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit me-2"></i> Edit Kategori</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit_kategori_id">
          <div class="modal-body">
              <label for="nama_edit" class="form-label">Nama Kategori<span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="nama_edit" name="nama" required maxlength="100">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-info text-white"><i class="fa fa-save me-1"></i> Perbarui</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(id, nama) {
    document.getElementById('edit_kategori_id').value = id;
    document.getElementById('nama_edit').value = nama;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan modal edit/add jika ada error setelah POST
    <?php if ($error_message && isset($_POST['action'])): ?>
        const action = '<?= esc($_POST['action']) ?>';
        if (action === 'add') {
            new bootstrap.Modal(document.getElementById('addModal')).show();
        } else if (action === 'edit') {
            openEditModal(<?= (int)($_POST['id'] ?? 0) ?>, '<?= esc($_POST['nama'] ?? '', false) ?>');
        }
    <?php endif; ?>
});
</script>

<?php
// FOOTER (Memuat footer, JS, dan tag penutup)
require_once 'footer.php';
?>
