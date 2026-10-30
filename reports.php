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
$page_title = 'Laporan dan Rekap Inventaris'; // Judul halaman

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// =======================
// LOGIKA LAPORAN STOK KRITIS
// =======================
$stokKritis = [];
$res_kritis = $conn->query("
    SELECT b.nama, b.kode_barang, b.qty, b.lokasi, k.nama AS nama_kategori
    FROM barang b
    JOIN kategori k ON b.kategori_id = k.id
    WHERE b.qty <= 2
    ORDER BY b.qty ASC
");
if ($res_kritis) {
    while ($row = $res_kritis->fetch_assoc()) {
        $stokKritis[] = $row;
    }
}

// =======================
// LOGIKA LAPORAN PERGERAKAN STOK BULANAN (Simulasi)
// =======================
$periode = esc($_GET['periode'] ?? date('Y-m')); // Default bulan ini
$tahun = substr($periode, 0, 4);
$bulan = substr($periode, 5, 2);

// Filter SQL berdasarkan bulan dan tahun
$sql_periode = " AND YEAR(t.tanggal) = '$tahun' AND MONTH(t.tanggal) = '$bulan' ";

$laporanPergerakan = [];
$res_pergerakan = $conn->query("
    SELECT 
        b.id, b.kode_barang, b.nama, k.nama AS nama_kategori,
        SUM(CASE WHEN t.tipe = 'masuk' THEN t.qty ELSE 0 END) AS total_masuk,
        SUM(CASE WHEN t.tipe = 'keluar' THEN t.qty ELSE 0 END) AS total_keluar
    FROM barang b
    JOIN kategori k ON b.kategori_id = k.id
    LEFT JOIN transaksi t ON b.id = t.barang_id
    WHERE 1=1 $sql_periode
    GROUP BY b.id
    HAVING total_masuk > 0 OR total_keluar > 0
    ORDER BY b.nama ASC
");
if ($res_pergerakan) {
    while ($row = $res_pergerakan->fetch_assoc()) {
        $laporanPergerakan[] = $row;
    }
}

// =======================
// INCLUDES (HEADER, SIDEBAR, NAVBAR)
// =======================
require_once 'header.php';
require_once 'sidebar.php';
require_once 'navbar.php';
?>

<!-- ============================================== -->
<!-- WRAPPER UTAMA (Mengatur Layout Sidebar dan Konten) -->
<!-- ============================================== -->
<div class="page-wrapper">
    <div class="content-area">

        <!-- MAIN CONTENT -->
        <div class="container-fluid main-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold text-primary">
                    <i class="fa fa-file-alt me-2"></i> Laporan dan Rekap Inventaris
                </h1>
                <a href="print_report.php?periode=<?= esc($periode) ?>" target="_blank" class="btn btn-outline-primary">
    <i class="fa fa-print me-1"></i> Cetak Laporan </a>

            </div>

            <!-- LAPORAN 1: STOK KRITIS -->
            <div class="card dashboard-card mb-5">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fa fa-exclamation-triangle me-2"></i> PERINGATAN: Stok Kritis (Qty &le; 2)
                </div>
                <div class="card-body p-0">
                    <?php if (empty($stokKritis)): ?>
                        <div class="alert alert-success m-3 text-center">
                            <i class="fa fa-thumbs-up me-1"></i> Semua stok barang di atas batas kritis (2 unit).
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="small text-uppercase bg-light">
                                    <tr>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th class="text-danger">Stok Sekarang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stokKritis as $b): ?>
                                    <tr class="<?= $b['qty'] == 0 ? 'table-danger' : '' ?>">
                                        <td><?= esc($b['kode_barang']) ?></td>
                                        <td class="fw-bold"><?= esc($b['nama']) ?></td>
                                        <td><?= esc($b['nama_kategori']) ?></td>
                                        <td class="fw-bold text-danger"><?= number_format($b['qty'], 0) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LAPORAN 2: PERGERAKAN STOK BULANAN -->
            <div class="card dashboard-card">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <div><i class="fa fa-chart-bar me-2 text-primary"></i> Rekap Masuk/Keluar Bulanan</div>
                    <form method="GET" class="d-flex gap-2">
                        <input type="month" class="form-control form-control-sm" name="periode" value="<?= esc($periode) ?>" onchange="this.form.submit()">
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if (empty($laporanPergerakan)): ?>
                            <div class="alert alert-info m-3 text-center">
                                Tidak ada pergerakan stok yang tercatat pada periode ini.
                            </div>
                        <?php else: ?>
                            <table class="table table-striped table-hover mb-0">
                                <thead class="small text-uppercase bg-light">
                                    <tr>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th class="text-success text-center">Total Masuk</th>
                                        <th class="text-danger text-center">Total Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grand_masuk = 0;
                                    $grand_keluar = 0;
                                    foreach ($laporanPergerakan as $b):
                                        $grand_masuk += $b['total_masuk'];
                                        $grand_keluar += $b['total_keluar'];
                                    ?>
                                    <tr>
                                        <td><?= esc($b['kode_barang']) ?></td>
                                        <td class="fw-bold"><?= esc($b['nama']) ?></td>
                                        <td><?= esc($b['nama_kategori']) ?></td>
                                        <td class="text-success text-center fw-bold"><?= number_format($b['total_masuk'], 0) ?></td>
                                        <td class="text-danger text-center fw-bold"><?= number_format($b['total_keluar'], 0) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light fw-bold">
                                        <td colspan="3">TOTAL BULAN INI (<?= date('M Y', strtotime($periode)) ?>)</td>
                                        <td class="text-success text-center"><?= number_format($grand_masuk, 0) ?></td>
                                        <td class="text-danger text-center"><?= number_format($grand_keluar, 0) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                        Data pergerakan dihitung dari log transaksi pada periode yang dipilih.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// FOOTER (Memuat footer, JS, dan tag penutup)
require_once 'footer.php';
?>
