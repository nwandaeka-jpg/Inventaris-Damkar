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
$userId = $user['id']; // ID pengguna yang melakukan transaksi

// Fungsi pembantu untuk sanitasi HTML
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// =======================
// LOGIKA PROSES TRANSAKSI BARU (MASUK / KELUAR)
// =======================
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add_masuk', 'add_keluar'])) {
    
    // Ambil & Validasi Data
    $tipe = ($_POST['action'] === 'add_masuk') ? 'masuk' : 'keluar';
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);
    $keterangan = esc($_POST['keterangan'] ?? '');

    if ($barang_id <= 0 || $qty <= 0) {
        $error_message = "Harap isi Nama Barang dan Jumlah (Qty) yang valid.";
    } else {
        // Cek Stok Barang (Hanya untuk Transaksi KELUAR)
        if ($tipe === 'keluar') {
            $res_stok = $conn->query("SELECT qty, nama FROM barang WHERE id = $barang_id");
            if ($res_stok && $row = $res_stok->fetch_assoc()) {
                $stok_saat_ini = (int)$row['qty'];
                $nama_barang = esc($row['nama']);

                if ($qty > $stok_saat_ini) {
                    $error_message = "Gagal. Jumlah keluar ($qty) melebihi stok tersedia ($stok_saat_ini) untuk barang '$nama_barang'.";
                }
            } else {
                $error_message = "Barang tidak ditemukan di inventaris.";
            }
        }
    }

    // Proses Transaksi jika tidak ada error
    if (empty($error_message)) {
        $conn->begin_transaction();
        try {
            // Mendapatkan nama barang jika belum terambil di bagian cek stok (untuk transaksi Masuk)
            if (!isset($nama_barang)) {
                $res_nama = $conn->query("SELECT nama FROM barang WHERE id = $barang_id");
                if ($res_nama && $row_nama = $res_nama->fetch_assoc()) {
                    $nama_barang = esc($row_nama['nama']);
                }
            }

            // INSERT ke tabel transaksi
            $stmt_trans = $conn->prepare("INSERT INTO transaksi (barang_id, tipe, qty, user_id, keterangan) VALUES (?, ?, ?, ?, ?)");
            $stmt_trans->bind_param("isdis", $barang_id, $tipe, $qty, $userId, $keterangan);
            
            if ($stmt_trans->execute()) {
                // UPDATE stok di tabel barang
                $operator = ($tipe === 'masuk') ? '+' : '-';
                $sql_update = "UPDATE barang SET qty = qty $operator $qty, updated_by = $userId WHERE id = $barang_id";
                
                if ($conn->query($sql_update)) {
                    $conn->commit();
                    $success_message = "Transaksi **" . ucfirst($tipe) . "** untuk $qty unit barang '$nama_barang' berhasil dicatat!";
                } else {
                    throw new Exception("Gagal update stok barang.");
                }
            } else {
                throw new Exception("Gagal mencatat transaksi.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Kesalahan sistem: " . $e->getMessage();
        } finally {
            if (isset($stmt_trans)) $stmt_trans->close();
        }
    }
}

// =======================
// AMBIL DATA UTAMA
// =======================
$filter_tipe = esc($_GET['tipe'] ?? 'all'); 
$data_transaksi = [];
$barang_list = [];

// Ambil List Barang (dengan stok)
$res_barang_list = $conn->query("SELECT id, nama, qty FROM barang ORDER BY nama ASC");
if ($res_barang_list) {
    while ($row = $res_barang_list->fetch_assoc()) {
        $barang_list[] = $row;
    }
}

// Ambil Daftar Transaksi
$where_clause = '';
if ($filter_tipe === 'masuk') {
    $where_clause = "WHERE t.tipe = 'masuk'";
} elseif ($filter_tipe === 'keluar') {
    $where_clause = "WHERE t.tipe = 'keluar'";
}

$sql_transaksi = "
    SELECT 
        t.id, t.tipe, t.qty, t.tanggal, t.keterangan,
        b.nama AS nama_barang, b.kode_barang,
        u.nama AS nama_pengguna
    FROM transaksi t
    JOIN barang b ON t.barang_id = b.id
    JOIN users u ON t.user_id = u.id
    $where_clause
    ORDER BY t.tanggal DESC
    LIMIT 100";

$res_transaksi = $conn->query($sql_transaksi);
if ($res_transaksi) {
    while ($row = $res_transaksi->fetch_assoc()) {
        $data_transaksi[] = $row;
    }
}

// Header Dinamis
$page_title = 'Semua Transaksi';
if ($filter_tipe === 'masuk') $page_title = 'Transaksi Barang Masuk';
if ($filter_tipe === 'keluar') $page_title = 'Transaksi Barang Keluar';

// =======================
// INCLUDES (HEADER, SIDEBAR, NAVBAR)
// =======================
require_once 'header.php'; 
require_once 'sidebar.php';
require_once 'navbar.php';

// =======================
// FUNGSI MODAL TRANSAKSI
// =======================
function createTransactionModal($type, $action, $color_class, $barang_list, $userId) {
    $title = "Catat Transaksi Barang $type";
    $btn_text = "Simpan Transaksi $type";
    $modal_id = "transactionModal" . $type;
    $header_bg = ($color_class === 'success') ? 'bg-success' : 'bg-primary';
    $stok_warning = ($type === 'Keluar') ? 
        '<small class="form-text text-danger mt-1"><i class="fa fa-info-circle"></i> Stok barang akan berkurang.</small>' : 
        '<small class="form-text text-success mt-1"><i class="fa fa-info-circle"></i> Stok barang akan bertambah.</small>';

    $html = <<<HTML
<div class="modal fade" id="$modal_id" tabindex="-1" aria-labelledby="{$modal_id}Label" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header $header_bg text-white">
        <h5 class="modal-title" id="{$modal_id}Label"><i class="fa fa-{$color_class}-circle me-2"></i> $title</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <input type="hidden" name="action" value="$action">
          <div class="modal-body">
              <div class="row g-3">
                  <div class="col-md-8">
                      <label for="barang_id" class="form-label">Nama Barang<span class="text-danger">*</span></label>
                      <select class="form-select" id="barang_id_$action" name="barang_id" required onchange="updateStokInfo('$action')">
                          <option value="">Pilih Barang...</option>
HTML;
    foreach ($barang_list as $b) {
        $stok_info = ($type === 'Keluar') ? "(Stok: {$b['qty']})" : "";
        $html .= "<option value=\"{$b['id']}\" data-stok=\"{$b['qty']}\">" . esc($b['nama']) . " $stok_info</option>";
    }
    $html .= <<<HTML
                      </select>
                      <small id="stokInfo_$action" class="form-text"></small>
                  </div>
                  <div class="col-md-4">
                      <label for="qty_$action" class="form-label">Jumlah Unit (Qty)<span class="text-danger">*</span></label>
                      <input type="number" class="form-control" id="qty_$action" name="qty" required min="1">
                      $stok_warning
                  </div>
                  <div class="col-12">
                      <label for="keterangan" class="form-label">Keterangan Transaksi</label>
                      <textarea class="form-control" id="keterangan_$action" name="keterangan" rows="3"></textarea>
                      <small class="form-text text-muted">Contoh: Pengadaan dari pusat, Digunakan dalam operasi SAR.</small>
                  </div>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-$color_class"><i class="fa fa-save me-1"></i> $btn_text</button>
          </div>
      </form>
    </div>
  </div>
</div>
HTML;
    return $html;
}
?>

<!-- MAIN CONTENT AREA -->
<div class="container-fluid main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary"><i class="fa fa-history me-2"></i> <?= $page_title ?></h1>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#transactionModalMasuk">
                <i class="fa fa-plus-circle me-1"></i> Transaksi Masuk
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModalKeluar">
                <i class="fa fa-minus-circle me-1"></i> Transaksi Keluar
            </button>
        </div>
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

    <!-- Filter & Tabel -->
    <div class="card main-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold small text-muted text-uppercase">Log Pergerakan Barang (<?= count($data_transaksi) ?> Terbaru)</h5>
            <div class="btn-group" role="group">
                <a href="transactions.php?tipe=all" class="btn btn-sm <?= $filter_tipe === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
                <a href="transactions.php?tipe=masuk" class="btn btn-sm <?= $filter_tipe === 'masuk' ? 'btn-success' : 'btn-outline-success' ?>">Masuk</a>
                <a href="transactions.php?tipe=keluar" class="btn btn-sm <?= $filter_tipe === 'keluar' ? 'btn-danger' : 'btn-outline-danger' ?>">Keluar</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <?php if (empty($data_transaksi)): ?>
                    <div class="alert alert-info m-3 text-center">Tidak ada transaksi yang tercatat dengan filter ini.</div>
                <?php else: ?>
                    <table class="table table-striped table-hover mb-0">
                        <thead class="small text-uppercase bg-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Kode & Nama Barang</th>
                                <th>Qty</th>
                                <th>Pelaksana</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_transaksi as $t): ?>
                            <tr>
                                <td class="align-middle small text-muted"><?= date('d M Y H:i', strtotime($t['tanggal'])) ?></td>
                                <td class="align-middle">
                                    <span class="badge rounded-pill p-2 <?= ($t['tipe']==='masuk') ? 'bg-success' : 'bg-danger' ?>">
                                        <?= esc(ucfirst($t['tipe'])) ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <small class="text-muted fw-bold"><?= esc($t['kode_barang']) ?></small><br>
                                    <span class="fw-bold text-dark"><?= esc($t['nama_barang']) ?></span>
                                </td>
                                <td class="align-middle fw-bold"><?= number_format($t['qty'], 0) ?></td>
                                <td class="align-middle"><?= esc($t['nama_pengguna']) ?></td>
                                <td class="align-middle small text-wrap" style="max-width: 250px;"><?= esc($t['keterangan'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TRANSAKSI MASUK -->
<?= createTransactionModal('Masuk', 'add_masuk', 'success', $barang_list, $userId); ?>
<!-- MODAL TRANSAKSI KELUAR -->
<?= createTransactionModal('Keluar', 'add_keluar', 'danger', $barang_list, $userId); ?>

<script>
    // FUNGSI JAVASCRIPT UNTUK MENAMPILKAN STOK BARANG YANG DIPILIH
    
    // Fungsi utama untuk mengupdate info stok
    function updateStokInfo(action) {
        const selectElement = document.getElementById(`barang_id_${action}`);
        const stokInfoElement = document.getElementById(`stokInfo_${action}`);
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const currentStok = selectedOption.getAttribute('data-stok');
            if (action === 'add_keluar') {
                stokInfoElement.textContent = `Stok tersedia: ${currentStok} unit.`;
                stokInfoElement.className = 'form-text text-primary mt-1';
            } else {
                stokInfoElement.textContent = ''; // Tidak perlu info stok untuk Masuk
            }
        } else {
            stokInfoElement.textContent = '';
        }
    }

    // FUNGSI VALIDASI JAVASCRIPT UNTUK KELUAR (Cek Stok)
    document.querySelector('#transactionModalKeluar form').addEventListener('submit', function(event) {
        const selectElement = document.getElementById('barang_id_add_keluar');
        const qtyElement = document.getElementById('qty_add_keluar');

        if (selectElement.value) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const currentStok = parseInt(selectedOption.getAttribute('data-stok'));
            const inputQty = parseInt(qtyElement.value);

            if (inputQty > currentStok) {
                // Gunakan console.log atau tampilkan pesan di UI (bukan alert)
                console.log(`Gagal! Jumlah yang dikeluarkan (${inputQty}) melebihi stok tersedia (${currentStok}).`);
                
                // Untuk menghindari form submit, tetap gunakan event.preventDefault()
                event.preventDefault();
                
                // Disarankan: Tampilkan pesan error di modal itu sendiri, bukan alert()
            }
        }
    });

    // Menampilkan modal jika ada error setelah POST
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($error_message && isset($_POST['action'])): ?>
            // Jika ada error, tampilkan modal yang sesuai
            const modalId = 'transactionModal' + '<?= ucfirst($tipe) ?>';
            new bootstrap.Modal(document.getElementById(modalId)).show();
        <?php endif; ?>
    });
</script>

<?php
// FOOTER (Memuat footer, JS, dan tag penutup)
require_once 'footer.php';
?>

