<?php
if (file_exists('koneksi.php')) include 'koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$user = $_SESSION['user'] ?? null;

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FireTrack - Inventaris Damkar Pematangsiantar</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Skema Warna Final: Merah, Kuning, Biru Gelap, Putih */
:root {
    --primary: #D93A2F; /* Merah Api */
    --accent: #FFC107; /* Kuning Aksen (Amber) */
    --dark-blue: #0b1220; /* Biru Gelap untuk background/footer/hero */
    --light-bg: #f8f9fa; /* Latar belakang Putih/Terang */
    --text-dark: #212529;
    --nav-height: 64px;
}

/* 1. STICKY FOOTER SETUP */
html { height: 100%; }

body {
    font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    padding-top: var(--nav-height);
    background: var(--light-bg);
    color: var(--text-dark);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Konten utama yang akan mengisi ruang yang tersisa */
.main-content {
    flex-grow: 1;
    padding-bottom: 40px;
}

/* NAVBAR */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--nav-height);
    z-index: 1030;
    background: linear-gradient(90deg, #e72416ff, var(--primary));
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.brand-logo img {
    height: 55px;
    width: auto;
    object-fit: contain;
}

.navbar-brand { font-weight: 700; }

.nav-link {
    color: #fff !important;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    transition: color 0.3s, background-color 0.3s;
}

.nav-link:hover {
    color: var(--accent) !important;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
}

.navbar-toggler {
    border-color: rgba(255, 255, 255, 0.5);
    color: #fff;
}

.navbar-toggler:focus { box-shadow: none; }

/* HERO SECTION */
.hero {
    background-color: var(--dark-blue);
    color: #fff;
    position: relative;
    overflow: hidden;
    padding: 4.5rem 20px;
    z-index: 1;
    text-align: center;
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

.hero h1 {
    font-size: 3.2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: var(--accent);
    animation: fadeIn 1.5s ease-in-out;
}

.hero p {
    font-size: 1.1rem;
    max-width: 750px;
    opacity: 0.9;
    margin: 0 auto 2rem auto;
}

.hero img {
    max-width: 150px;
    height: auto;
    margin-bottom: 1.5rem;
    filter: drop-shadow(0 0 15px rgba(255, 193, 7, 0.4));
}

/* BUTTONS */
.btn-custom {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px 30px;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
}

.btn-custom:hover {
    background-color: #a82e26;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(217, 58, 47, 0.4);
    color: #fff;
}

.btn-outline-light {
    border: 2px solid var(--accent) !important;
    color: var(--accent) !important;
    background: transparent;
    border-radius: 8px;
    padding: 10px 30px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-outline-light:hover {
    background: var(--accent);
    color: var(--dark-blue) !important;
    border-color: var(--accent) !important;
}

/* FEATURE CARDS */
.features {
    padding-top: 40px;
    padding-bottom: 20px;
    justify-content: center;
    gap: 25px;
}

.feature-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px 25px;
    flex: 1 1 250px;
    max-width: 320px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(16,24,40,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    border-top: 4px solid var(--primary);
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 18px 40px rgba(0,0,0,.15);
}

.feature-card i {
    font-size: 3rem;
    color: var(--primary);
    margin-bottom: 15px;
}

.feature-card h5 {
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--text-dark);
}

.feature-card p {
    color: #6c757d;
    font-size: 0.95rem;
}

/* FOOTER */
footer {
    background: var(--dark-blue);
    color: #bdbdbd;
    padding: 24px 0;
    margin-top: auto;
    font-size: 0.9rem;
    text-align: center;
    width: 100%;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .hero { padding: 3rem 20px; }
    .hero h1 { font-size: 2.5rem; }
    .hero p { font-size: 1rem; }
    .feature-card { max-width: 45%; }
}

@media (max-width: 576px) {
    .feature-card { max-width: 90%; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg py-3">
    <div class="container">
        <a class="navbar-brand brand-logo" href="index.php">
            <img src="damkar.png" alt="Damkar Logo">
        </a>
        <button class="navbar-toggler btn" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <i class="fa fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navMain">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if (!$user): ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">
    <!-- HERO -->
    <div class="hero position-relative">
        <div class="container">
            <img src="logo.png" alt="FireTrack Logo">
            <h1>Selamat Datang di FireTrack</h1>
            <p class="lead">
                FireTrack adalah aplikasi web untuk mengelola inventaris peralatan Dinas Pemadam Kebakaran Kota Pematangsiantar.
                Catat stok, pantau transaksi masuk/keluar, dan dokumentasikan peralatan secara rapi. Sederhana, efisien, dan siap digunakan.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <?php if (!$user): ?>
                    <a href="login.php" class="btn btn-custom me-2"><i class="fa fa-right-to-bracket"></i> Masuk</a>
                    <a href="register.php" class="btn btn-outline-light"><i class="fa fa-user-plus"></i> Daftar</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-custom"><i class="fa fa-tachometer-alt"></i> Buka Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FEATURES -->
    <div class="features container d-flex flex-wrap justify-content-center">
        <div class="feature-card">
            <i class="fa fa-box"></i>
            <h5>Manajemen Inventaris</h5>
            <p>Mengelola data inventaris peralatan dengan mudah: tambah, edit, hapus, dan pantau stok realtime.</p>
        </div>
        <div class="feature-card">
            <i class="fa fa-exchange-alt"></i>
            <h5>Pelacakan Transaksi</h5>
            <p>Catat semua pergerakan barang masuk dan keluar agar stok selalu akurat dan terdokumentasi.</p>
        </div>
        <div class="feature-card">
            <i class="fa fa-file-alt"></i>
            <h5>Pencetakan Laporan</h5>
            <p>Cetak laporan stok dan transaksi untuk kebutuhan administrasi dan audit secara cepat dan rapi.</p>
        </div>
    </div>
</div> <!-- end main-content -->

<footer>
    &copy; <?= date('Y'); ?> FireTrack — Inventaris Dinas Pemadam Kebakaran Kota Pematangsiantar
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
