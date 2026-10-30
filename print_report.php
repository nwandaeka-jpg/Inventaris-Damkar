<?php
// ===========================================
// KONFIGURASI, SESSION & LOGIKA DATA
// File ini dirancang untuk Cetak, BUKAN tampilan web normal.
// ===========================================
if (file_exists('koneksi.php')) {
    require_once 'koneksi.php';
} else {
    die("Sistem tidak dapat terhubung ke database.");
}

// Fungsi keamanan sederhana
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Ambil periode dari URL (Default: bulan ini)
$periode = esc($_GET['periode'] ?? date('Y-m'));
$tahun = substr($periode, 0, 4);
$bulan = substr($periode, 5, 2);
$bulan_nama = date('F', mktime(0, 0, 0, $bulan, 10));
$bulan_id = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
    'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
    'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
    'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
][$bulan_nama] ?? $bulan_nama;

$periode_text = $bulan_id . ' ' . $tahun;

// --- LOGIKA LAPORAN PERGERAKAN STOK BULANAN ---
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Inventaris - <?= $periode_text ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 15mm; }
            body { font-size: 10pt; background: white !important; color: black !important; }
            .table { border-collapse: collapse !important; }
            .table th, .table td { border: 1px solid #000 !important; padding: 5px; }
            .no-print { display: none !important; }
            .kop-surat { border-bottom: 3px solid #D93A2F; padding-bottom: 10px; margin-bottom: 20px; text-align: center; }
        }

        body { font-family: 'Times New Roman', serif; margin: 20px; }
        .kop-surat { border-bottom: 3px solid #D93A2F; margin-bottom: 25px; padding-bottom: 15px; }
        .logo { width: 80px; height: auto; margin-right: 15px; }
    </style>
</head>
<body onload="window.print()">

    <!-- KOP SURAT -->
    <div class="kop-surat d-flex align-items-center">
        <img src="damkar.png" alt="Logo Damkar" class="logo">
        <div>
            <div class="fw-bold" style="font-size: 1.1rem;">DINAS PEMADAM KEBAKARAN DAN PENYELAMATAN</div>
            <div class="fw-bold" style="font-size: 1.5rem;">KOTA PEMATANGSIANTAR</div>
            <div class="small">
                Jl. Tarutung No. 10, Kel. Kristen, Kec. Siantar Barat, Kota Pematangsiantar, Sumatera Utara<br>
                Telp: (0622) 431809 | Call Center: 0811-6265-113 (Aplikasi: FireTrack Inventaris)
            </div>
        </div>
    </div>

    <h4 class="text-center fw-bold mb-4">LAPORAN REKAP PERGERAKAN INVENTARIS</h4>
    <p class="text-center mb-5">Periode: <b><?= $periode_text ?></b></p>

    <!-- LAPORAN: PERGERAKAN STOK BULANAN -->
    <div class="mb-5">
        <?php if (empty($laporanPergerakan)): ?>
            <div class="alert alert-info p-2 small border-info">Tidak ada pergerakan stok yang tercatat pada periode ini.</div>
        <?php else: ?>
            <table class="table table-bordered table-sm">
                <thead class="bg-light small">
                    <tr>
                        <th>Kode</th>
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
                        <td class="small"><?= esc($b['kode_barang']) ?></td>
                        <td><?= esc($b['nama']) ?></td>
                        <td><?= esc($b['nama_kategori']) ?></td>
                        <td class="text-success text-center fw-bold"><?= number_format($b['total_masuk'], 0) ?></td>
                        <td class="text-danger text-center fw-bold"><?= number_format($b['total_keluar'], 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold" style="background-color: #f0f0f0;">
                        <td colspan="3" class="text-end">TOTAL PERIODE INI</td>
                        <td class="text-success text-center"><?= number_format($grand_masuk, 0) ?></td>
                        <td class="text-danger text-center"><?= number_format($grand_keluar, 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

    <!-- TANDA TANGAN -->
    <div style="margin-top: 50px;" class="row">
        <div class="col-8"></div>
        <div class="col-4 text-center">
            Pematangsiantar, <?= date('d ') . $bulan_id . date(' Y') ?><br>
            Dibuat oleh,<br><br><br><br>
            _________________________<br>
            Ingot Sahata Lumbantobing, SH
        </div>
    </div>

</body>
</html>
