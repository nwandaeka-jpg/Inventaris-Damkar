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

// Fungsi sanitasi HTML
function esc($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// =======================
// LOGIKA PROSES TAMBAH & EDIT BARANG
// =======================
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $barang_id = (int)($_POST['id'] ?? 0);

    // Ambil data umum (sanitasi input teks untuk tampil di UI, gunakan raw $_POST untuk DB jika perlu)
    $kode_barang = esc($_POST['kode_barang'] ?? '');
    $nama = esc($_POST['nama'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $lokasi = esc($_POST['lokasi'] ?? '');
    $qty = (int)($_POST['qty'] ?? 0);
    $kondisi = esc($_POST['kondisi'] ?? '');
    $keterangan = esc($_POST['keterangan'] ?? '');

    // Untuk penyimpanan ke DB kita boleh gunakan nilai asli (tanpa esc) — di sini tetap memakai variabel di atas.
    // Jika lokasi kosong, set ke NULL agar tidak tersimpan '0' oleh kesalahan bind_param
    $lokasi_for_db = ($lokasi === '') ? null : $lokasi;

    if (!$isAdmin) {
        $error_message = "Akses ditolak. Hanya Administrator yang dapat menambah/mengubah barang.";
    } elseif ($kategori_id <= 0 || empty($nama)) {
        $error_message = "Data wajib (Nama & Kategori) tidak valid.";
    } else {
        $conn->begin_transaction();
        try {
            $foto_path_final = '';

            // 1. Cek duplikasi kode
            $check_kode_sql = "SELECT id FROM barang WHERE kode_barang = ? AND id != ?";
            $check_kode = $conn->prepare($check_kode_sql);
            $check_kode->bind_param("si", $kode_barang, $barang_id);
            $check_kode->execute();
            $check_kode->store_result();
            if ($check_kode->num_rows > 0) {
                throw new Exception("Kode Barang sudah ada. Harap gunakan kode yang unik.");
            }
            $check_kode->close();

            // 2. Handle Upload Foto (opsional)
            $is_new_upload = false;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'barang_' . time() . '.' . $fileExtension;
                $uploadFileDir = './uploads/';

                if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0777, true);
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $foto_path_final = $dest_path;
                    $is_new_upload = true;
                } else {
                    throw new Exception("Gagal mengupload file foto.");
                }
            }

            // 3. Proses SQL: Tambah atau Edit
            if ($action === 'add_barang') {
                $sql = "INSERT INTO barang (kode_barang, nama, kategori_id, lokasi, qty, foto, kondisi, keterangan, updated_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                // PENTING: urutan jenis harus sesuai parameter:
                // 1 kode (s), 2 nama (s), 3 kategori_id (i), 4 lokasi (s|null), 5 qty (i),
                // 6 foto (s), 7 kondisi (s), 8 keterangan (s), 9 updated_by (i)
                // => pola: s s i s i s s s i  -> "ssisisssi"
                $stmt->bind_param(
                    "ssisisssi",
                    $kode_barang,
                    $nama,
                    $kategori_id,
                    $lokasi_for_db,
                    $qty,
                    $foto_path_final,
                    $kondisi,
                    $keterangan,
                    $userId
                );

                if ($stmt->execute()) {
                    $new_barang_id = $stmt->insert_id;
                    $success_message = "Barang '$nama' berhasil ditambahkan ke inventaris!";

                    // Log Transaksi Stok Awal (Jika QTY > 0)
                    if ($qty > 0) {
                        $stmt_transaksi = $conn->prepare("INSERT INTO transaksi (barang_id, tipe, qty, user_id, keterangan) 
                                                          VALUES (?, 'masuk', ?, ?, 'Stok awal ditambahkan saat registrasi barang.')");
                        $stmt_transaksi->bind_param("iii", $new_barang_id, $qty, $userId);
                        $stmt_transaksi->execute();
                        $stmt_transaksi->close();
                    }
                } else {
                    throw new Exception("Gagal menyimpan barang: " . $stmt->error);
                }
                $stmt->close();
            } elseif ($action === 'edit_barang' && $barang_id > 0) {
                // Ambil path foto lama jika tidak ada upload baru
                if (!$is_new_upload) {
                    $res_old = $conn->query("SELECT foto FROM barang WHERE id = " . (int)$barang_id);
                    if ($res_old && $row = $res_old->fetch_assoc()) {
                        $foto_path_final = $row['foto'];
                    }
                }

                // Update Detail Barang
                $sql = "UPDATE barang 
                        SET kode_barang=?, nama=?, kategori_id=?, lokasi=?, foto=?, kondisi=?, keterangan=?, updated_by=? 
                        WHERE id=?";
                $stmt = $conn->prepare($sql);

                // Urutan jenis:
                // 1 kode (s), 2 nama (s), 3 kategori_id (i), 4 lokasi (s|null), 5 foto (s),
                // 6 kondisi (s), 7 keterangan (s), 8 updated_by (i), 9 id (i)
                // => pola: s s i s s s s i i  -> "ssissssii"
                $stmt->bind_param(
                    "ssissssii",
                    $kode_barang,
                    $nama,
                    $kategori_id,
                    $lokasi_for_db,
                    $foto_path_final,
                    $kondisi,
                    $keterangan,
                    $userId,
                    $barang_id
                );

                if (!$stmt->execute()) {
                    throw new Exception("Gagal mengupdate barang: " . $stmt->error);
                }
                $success_message = "Barang '$nama' berhasil diperbarui!";
                $stmt->close();
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// =======================
// AMBIL DATA UNTUK TABEL & MODAL
// =======================
$data_barang = [];
$list_kategori = [];

// Ambil list kategori
$res_kategori = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
if ($res_kategori) {
    while ($row = $res_kategori->fetch_assoc()) {
        $list_kategori[$row['id']] = $row['nama'];
    }
}

// Ambil data barang
$sql_barang = "
    SELECT 
        b.id, b.kode_barang, b.nama AS nama_barang, b.qty, b.lokasi, b.kondisi, b.keterangan, b.foto,
        k.nama AS nama_kategori, k.id AS kategori_id,
        u.nama AS updater_nama
    FROM barang b
    JOIN kategori k ON b.kategori_id = k.id
    LEFT JOIN users u ON b.updated_by = u.id
    ORDER BY b.id DESC
";
$res_barang = $conn->query($sql_barang);
if ($res_barang) {
    while ($row = $res_barang->fetch_assoc()) {
        $data_barang[] = $row;
    }
}

// =======================
// INCLUDES HEADER, SIDEBAR, NAVBAR
// =======================
require_once 'header.php';
require_once 'sidebar.php';
require_once 'navbar.php';
?>

<!-- MAIN CONTENT AREA -->
<div class="page-wrapper d-flex flex-column flex-grow-1">
    <div class="container-fluid content-area p-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-primary"><i class="fa fa-boxes me-2"></i> Daftar Inventaris Barang</h1>
            <?php if ($isAdmin): ?>
                <button class="btn btn-action" data-bs-toggle="modal" data-bs-target="#addBarangModal">
                    <i class="fa fa-plus-circle me-1"></i> Tambah Barang Baru
                </button>
            <?php endif; ?>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i> <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card main-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (empty($data_barang)): ?>
                        <div class="alert alert-info m-3 text-center">
                            Belum ada data barang yang tercatat dalam inventaris.
                        </div>
                    <?php else: ?>
                        <table class="table table-striped table-hover mb-0">
                            <thead class="small text-uppercase bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>Foto</th>
                                    <th>Kode & Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Stok (Qty)</th>
                                    <th>Kondisi</th>
                                    <?php if ($isAdmin): ?><th>Aksi</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($data_barang as $b):
                                    $foto_url = empty($b['foto']) ? 'https://placehold.co/60x60/808080/FFFFFF?text=No+Img' : esc($b['foto']);
                                ?>
                                <tr
                                    data-id="<?= $b['id'] ?>"
                                    data-kode="<?= esc($b['kode_barang']) ?>"
                                    data-nama="<?= esc($b['nama_barang']) ?>"
                                    data-kategori-id="<?= $b['kategori_id'] ?>"
                                    data-lokasi="<?= esc($b['lokasi']) ?>"
                                    data-qty="<?= $b['qty'] ?>"
                                    data-kondisi="<?= esc($b['kondisi']) ?>"
                                    data-keterangan="<?= esc($b['keterangan']) ?>"
                                    data-foto="<?= esc($b['foto']) ?>"
                                >
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <img src="<?= $foto_url ?>" class="barang-img"
                                             alt="<?= esc($b['nama_barang']) ?>"
                                             onclick="showImageModal('<?= $foto_url ?>', '<?= esc($b['nama_barang']) ?>')">
                                    </td>
                                    <td>
                                        <small class="text-muted fw-bold"><?= esc($b['kode_barang']) ?></small><br>
                                        <span class="fw-bold text-dark"><?= esc($b['nama_barang']) ?></span>
                                    </td>
                                    <td><?= esc($b['nama_kategori']) ?></td>
                                    <td><?= empty($b['lokasi']) ? '-' : esc($b['lokasi']) ?></td>
                                    <td>
                                        <h5 class="mb-0 fw-bold <?= ($b['qty'] < 5) ? 'text-danger' : 'text-success' ?>">
                                            <?= number_format($b['qty'], 0) ?>
                                        </h5>
                                        <small class="text-muted">unit</small>
                                    </td>
                                    <td>
                                        <?php
                                        $kondisi = strtolower(trim(esc($b['kondisi'])));
                                        $badge_class = 'bg-success';
                                        if (strpos($kondisi, 'rusak') !== false) $badge_class = 'bg-danger';
                                        elseif (strpos($kondisi, 'perbaikan') !== false) $badge_class = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge rounded-pill <?= $badge_class ?> p-2">
                                            <?= esc($b['kondisi']) ?: 'Baik' ?>
                                        </span>
                                    </td>

                                    <?php if ($isAdmin): ?>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info me-1" title="Edit Barang"
                                            onclick="loadEditModal(this)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <a href="delete_barang.php?id=<?= $b['id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           title="Hapus"
                                           onclick="return confirm('Yakin ingin menghapus barang <?= esc($b['nama_barang']) ?>?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-footer text-muted small">
                Daftar ini mencakup semua peralatan Damkar yang terdaftar.
                Total <?= count($data_barang) ?> jenis barang.
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH BARANG -->
<div class="modal fade" id="addBarangModal" tabindex="-1" aria-labelledby="addBarangModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addBarangModalLabel">
            <i class="fa fa-plus-circle me-2"></i> Tambah Barang Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_barang">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Kode Barang (Unik)<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="kode_barang" required maxlength="60">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Barang<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="nama" required maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori<span class="text-danger">*</span></label>
              <select class="form-select" name="kategori_id" id="kategori_id_input" required>
                <option value="">Pilih Kategori...</option>
                <?php foreach ($list_kategori as $id => $nama): ?>
                  <option value="<?= $id ?>"><?= esc($nama) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Lokasi Penyimpanan</label>
              <input type="text" class="form-control" name="lokasi" maxlength="150">
            </div>
            <div class="col-md-4">
              <label class="form-label">Stok Awal (Qty)<span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="qty" required min="0">
            </div>
            <div class="col-md-8">
              <label class="form-label">Kondisi Barang</label>
              <input type="text" class="form-control" name="kondisi" maxlength="60"
                     placeholder="Contoh: Baik, Rusak, Perlu Perbaikan">
            </div>
            <div class="col-12">
              <label class="form-label">Foto Barang (Maks 2MB)</label>
              <input class="form-control" type="file" name="foto" accept="image/*">
              <small class="form-text text-muted">Akan disimpan di folder `/uploads/`.</small>
            </div>
            <div class="col-12">
              <label class="form-label">Keterangan Tambahan</label>
              <textarea class="form-control" name="keterangan" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-action">
              <i class="fa fa-save me-1"></i> Simpan Barang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDIT BARANG -->
<div class="modal fade" id="editBarangModal" tabindex="-1" aria-labelledby="editBarangModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="editBarangModalLabel">
            <i class="fa fa-edit me-2"></i> Edit Detail Barang
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_barang">
        <input type="hidden" name="id" id="edit-id" value="">
        <div class="modal-body">
          <div class="alert alert-warning small">
              <i class="fa fa-info-circle me-1"></i> <strong>Penting:</strong> Stok (Qty) hanya dapat diubah melalui halaman <strong>Transaksi Stok</strong>.
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Kode Barang (Unik)<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="kode_barang" id="edit-kode_barang" required maxlength="60">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Barang<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="nama" id="edit-nama" required maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori<span class="text-danger">*</span></label>
              <select class="form-select" name="kategori_id" id="edit-kategori_id" required>
                <option value="">Pilih Kategori...</option>
                <?php foreach ($list_kategori as $id => $nama): ?>
                  <option value="<?= $id ?>"><?= esc($nama) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Lokasi Penyimpanan</label>
              <input type="text" class="form-control" name="lokasi" id="edit-lokasi" maxlength="150">
            </div>
            <div class="col-md-4">
              <label class="form-label">Stok Saat Ini (QTY)</label>
              <input type="number" class="form-control" id="edit-qty-display" disabled>
            </div>
            <div class="col-md-8">
              <label class="form-label">Kondisi Barang</label>
              <input type="text" class="form-control" name="kondisi" id="edit-kondisi" maxlength="60">
            </div>
            <div class="col-12">
              <label class="form-label">Ganti Foto Barang (Opsional)</label>
              <input class="form-control" type="file" name="foto" accept="image/*">
              <small class="form-text text-muted">
                  Abaikan jika tidak ingin mengubah foto. Saat ini: <span id="current-foto-name"></span>
              </small>
            </div>
            <div class="col-12">
              <label class="form-label">Keterangan Tambahan</label>
              <textarea class="form-control" name="keterangan" id="edit-keterangan" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-info text-white">
              <i class="fa fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL VIEW FOTO -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Lihat Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" class="img-fluid rounded" alt="Foto Barang">
      </div>
    </div>
  </div>
</div>

<?php
// FOOTER (Memuat footer, JS, dan tag penutup)
require_once 'footer.php';
?>

<script>
const style = document.createElement('style');
style.innerHTML = `
.barang-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ddd;
    cursor: zoom-in;
}
`;
document.head.appendChild(style);

function showImageModal(src, title) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModalLabel').innerText = 'Foto Barang: ' + title;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

function loadEditModal(buttonElement) {
    const row = buttonElement.closest('tr');

    document.getElementById('edit-id').value = row.getAttribute('data-id');
    document.getElementById('edit-kode_barang').value = row.getAttribute('data-kode');
    document.getElementById('edit-nama').value = row.getAttribute('data-nama');

    const lokasiValue = row.getAttribute('data-lokasi');
    document.getElementById('edit-lokasi').value = (lokasiValue === 'null' || lokasiValue === '-') ? '' : lokasiValue;

    document.getElementById('edit-qty-display').value = row.getAttribute('data-qty');
    document.getElementById('edit-kondisi').value = row.getAttribute('data-kondisi');
    document.getElementById('edit-keterangan').value = row.getAttribute('data-keterangan');

    const currentFoto = row.getAttribute('data-foto');
    document.getElementById('current-foto-name').textContent = currentFoto ? currentFoto.split('/').pop() : 'Tidak ada foto terdaftar.';

    const kategoriId = row.getAttribute('data-kategori-id');
    document.getElementById('edit-kategori_id').value = kategoriId;

    new bootstrap.Modal(document.getElementById('editBarangModal')).show();
}

document.addEventListener('DOMContentLoaded', () => {
    const formAdd = document.querySelector('#addBarangModal form');
    const formEdit = document.querySelector('#editBarangModal form');

    if (formAdd) {
        formAdd.addEventListener('submit', (e) => {
            if (document.getElementById('kategori_id_input').value === "") {
                alert('Harap pilih Kategori!');
                e.preventDefault();
            }
        });
    }

    if (formEdit) {
        formEdit.addEventListener('submit', (e) => {
            if (document.getElementById('edit-kategori_id').value === "") {
                alert('Harap pilih Kategori!');
                e.preventDefault();
            }
        });
    }

    <?php if ($error_message && $isAdmin && ($_POST['action'] ?? '') === 'add_barang'): ?>
        new bootstrap.Modal(document.getElementById('addBarangModal')).show();
    <?php endif; ?>

    <?php if ($error_message && $isAdmin && ($_POST['action'] ?? '') === 'edit_barang' && isset($_POST['id'])): ?>
        const modalEdit = new bootstrap.Modal(document.getElementById('editBarangModal'));

        document.getElementById('edit-id').value = <?= (int)$_POST['id'] ?>;
        document.getElementById('edit-kode_barang').value = '<?= esc($_POST['kode_barang'] ?? '') ?>';
        document.getElementById('edit-nama').value = '<?= esc($_POST['nama'] ?? '') ?>';
        document.getElementById('edit-lokasi').value = '<?= esc($_POST['lokasi'] ?? '') ?>';
        document.getElementById('edit-kondisi').value = '<?= esc($_POST['kondisi'] ?? '') ?>';
        document.getElementById('edit-keterangan').value = '<?= esc($_POST['keterangan'] ?? '') ?>';
        document.getElementById('edit-kategori_id').value = <?= (int)$_POST['kategori_id'] ?>;

        modalEdit.show();
    <?php endif; ?>
});
</script>
