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

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userName = $user['nama'] ?? $user['username'];
$userRole = ucfirst($user['role'] ?? 'petugas');
$isAdmin = ($user['role'] === 'admin');

function esc($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
}

// =======================
// AMBIL DATA DASHBOARD
// =======================
$totalBarang = 0;
$totalTransaksiMasuk = 0;
$totalTransaksiKeluar = 0;
$totalKategori = 0;
$latestTransactions = [];

try {
    // Mengambil total barang
    $res_barang = $conn->query("SELECT COUNT(id) AS total FROM barang");
    if ($res_barang) $totalBarang = $res_barang->fetch_assoc()['total'];

    // Mengambil total transaksi masuk dan keluar
    $res_transaksi = $conn->query("SELECT tipe, SUM(qty) AS total_qty FROM transaksi GROUP BY tipe");
    if ($res_transaksi) {
        while($row = $res_transaksi->fetch_assoc()) {
            if ($row['tipe'] === 'masuk') $totalTransaksiMasuk = $row['total_qty'];
            elseif ($row['tipe'] === 'keluar') $totalTransaksiKeluar = $row['total_qty'];
        }
    }

    // Mengambil total kategori
    $res_kategori = $conn->query("SELECT COUNT(id) AS total FROM kategori");
    if ($res_kategori) $totalKategori = $res_kategori->fetch_assoc()['total'];

    // Mengambil 5 transaksi terbaru
    $sql_latest = "
        SELECT t.id, t.tipe, t.qty, t.tanggal, t.keterangan,
               b.nama AS nama_barang,
               u.nama AS nama_pengguna
        FROM transaksi t
        JOIN barang b ON t.barang_id = b.id
        JOIN users u ON t.user_id = u.id
        ORDER BY t.tanggal DESC
        LIMIT 5";
    $res_latest = $conn->query($sql_latest);
    if ($res_latest) {
        while($row = $res_latest->fetch_assoc()) {
            $latestTransactions[] = $row;
        }
    }

} catch (\Exception $e) {
    // error_log("Database Error: " . $e->getMessage());
}

// =======================
// INCLUDES HEADER, SIDEBAR, NAVBAR
// =======================
require_once 'header.php';
require_once 'sidebar.php';
require_once 'navbar.php';
?>

<!-- MAIN CONTENT AREA -->
<div class="container-fluid main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary">
            <i class="fa fa-tachometer-alt me-2"></i> Ringkasan Dashboard
        </h1>
        <div class="small text-muted">Akses Terakhir: <?= date('d M Y H:i') ?></div>
    </div>

    <!-- SUMMARY METRIK -->
    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-md-3">
            <div class="summary-box bg-total dashboard-card">
                <div class="small opacity-75">Total Jenis Barang</div>
                <h3 class="mt-2"><?= number_format($totalBarang, 0, ',', '.') ?></h3>
                <i class="fa fa-box-open"></i>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="summary-box bg-masuk dashboard-card">
                <div class="small opacity-75">Total Unit Masuk</div>
                <h3 class="mt-2"><?= number_format($totalTransaksiMasuk, 0, ',', '.') ?></h3>
                <i class="fa fa-arrow-alt-circle-down"></i>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="summary-box bg-keluar dashboard-card">
                <div class="small opacity-75">Total Unit Keluar</div>
                <h3 class="mt-2"><?= number_format($totalTransaksiKeluar, 0, ',', '.') ?></h3>
                <i class="fa fa-arrow-alt-circle-up"></i>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="summary-box bg-kategori dashboard-card">
                <div class="small opacity-75 text-dark">Jumlah Kategori</div>
                <h3 class="mt-2"><?= number_format($totalKategori, 0, ',', '.') ?></h3>
                <i class="fa fa-tags text-dark"></i>
            </div>
        </div>
    </div>

    <!-- TRANSAKSI TERBARU -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white fw-bold border-bottom-0 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa fa-history me-2 text-primary"></i> 5 Transaksi Terbaru
                    </div>
                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($latestTransactions)): ?>
                        <div class="alert alert-info m-4 text-center">
                            Belum ada data transaksi yang tercatat.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="small">Tipe</th>
                                        <th class="small">Barang</th>
                                        <th class="small">Qty</th>
                                        <th class="small">Tanggal</th>
                                        <th class="small">Pelaksana</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestTransactions as $t): ?>
                                        <tr>
                                            <td>
                                                <span class="badge rounded-pill <?= ($t['tipe']==='masuk') ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= esc(ucfirst($t['tipe'])) ?>
                                                </span>
                                            </td>
                                            <td><?= esc($t['nama_barang']) ?></td>
                                            <td><?= number_format($t['qty'], 0) ?></td>
                                            <td><?= date('d M H:i', strtotime($t['tanggal'])) ?></td>
                                            <td><?= esc($t['nama_pengguna']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// FOOTER (Memuat footer, JS, dan tag penutup)
require_once 'footer.php';
?>
