<?php
// Pastikan koneksi dan session dimulai
if (file_exists('koneksi.php')) {
    require_once 'koneksi.php';
} else {
    die("Koneksi database (koneksi.php) tidak ditemukan.");
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan sanitasi input
    $username = trim($conn->real_escape_string($_POST['username'] ?? ''));
    $nama = trim($conn->real_escape_string($_POST['nama'] ?? ''));
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = 'petugas'; // Default role

    // Validasi
    if (empty($username) || empty($nama) || empty($password) || empty($password_confirm)) {
        $error = "Semua kolom wajib diisi.";
    } elseif ($password !== $password_confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Cek ketersediaan username
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);

        if ($check_stmt) {
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Username sudah digunakan. Silakan pilih username lain.";
            } else {
                // Proses Registrasi
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);

                if ($insert_stmt) {
                    $insert_stmt->bind_param("ssss", $username, $hashed_password, $nama, $role);
                    if ($insert_stmt->execute()) {
                        $success = "Registrasi berhasil! Anda sekarang dapat masuk.";
                        $_POST = []; // Kosongkan form setelah sukses
                    } else {
                        $error = "Gagal menyimpan data: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                } else {
                    $error = "Kesalahan query INSERT: " . $conn->error;
                }
            }
            $check_stmt->close();
        } else {
            $error = "Kesalahan query CHECK: " . $conn->error;
        }
    }
}

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi - FireTrack</title>

<!-- Bootstrap & Font Awesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #D93A2F;
    --accent: #FFC107;
    --dark-blue: #0b1220;
    --text-dark: #212529;
    --nav-height: 64px;
}

html, body { height: 100%; }
body {
    font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, Arial;
    background: linear-gradient(135deg, var(--dark-blue), #071025);
    color: #fff;
    display: flex;
    flex-direction: column;
}

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
.brand-logo img { height: 55px; width: auto; object-fit: contain; }

.register-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 80px 15px;
    flex-grow: 1;
}

.register-card {
    background: #fff;
    color: var(--text-dark);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    max-width: 480px;
    width: 100%;
    padding: 35px;
    animation: fadeInUp 0.8s ease-out;
}

.register-card h2 {
    color: var(--primary);
    font-weight: 700;
    margin-bottom: 25px;
    border-bottom: 2px solid var(--accent);
    padding-bottom: 10px;
}

.form-control {
    border-radius: 8px;
    padding: 12px 15px;
    border: 1px solid #ddd;
}
.form-control:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 0.25rem rgba(255,193,7,0.25);
}

.btn-custom {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.2s;
}
.btn-custom:hover { background-color: #a82e26; transform: translateY(-1px); }

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-radius: 8px;
    padding: 10px;
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-radius: 8px;
    padding: 10px;
}

footer {
    background: var(--dark-blue);
    color: #bdbdbd;
    padding: 15px 0;
    margin-top: auto;
    font-size: 0.85rem;
    text-align: center;
    width: 100%;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand brand-logo" href="index.php">
            <img src="damkar.png" alt="Damkar Logo">
        </a>
        <a href="index.php" class="btn btn-sm text-white" style="border:1px solid rgba(255,255,255,0.2);">
            <i class="fa fa-home me-1"></i> Kembali
        </a>
    </div>
</nav>

<!-- Form Registrasi -->
<div class="register-container">
    <div class="register-card">
        <div class="text-center mb-4">
            <img src="logo.png" alt="FireTrack Logo" style="max-width: 80px;">
        </div>
        <h2 class="text-center">DAFTAR AKUN PETUGAS</h2>
        <p class="text-center text-muted small mb-4">Pastikan data yang Anda masukkan benar.</p>

        <?php if ($error): ?>
            <div class="alert alert-error text-center small"><?= esc($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success text-center small">
                <?= esc($success) ?>
                <div class="mt-2"><a href="login.php" class="alert-link" style="color:#155724; font-weight:600;">Lanjut ke halaman Login</a></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label for="nama" class="form-label fw-bold small">Nama Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-address-card" style="color:var(--primary)"></i></span>
                    <input type="text" class="form-control" id="nama" name="nama" placeholder="Contoh: Rahmat Hidayat" value="<?= esc($_POST['nama'] ?? '') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label fw-bold small">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user" style="color:var(--primary)"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username unik" value="<?= esc($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-bold small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock" style="color:var(--primary)"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password_confirm" class="form-label fw-bold small">Konfirmasi Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-check-circle" style="color:var(--primary)"></i></span>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-custom btn-lg"><i class="fa fa-user-plus me-2"></i> DAFTAR SEBAGAI PETUGAS</button>
            </div>
        </form>

        <p class="text-center mt-3 small">
            Sudah punya akun? <a href="login.php" class="text-decoration-none" style="color:var(--primary); font-weight:600;">Masuk di sini</a>
        </p>
    </div>
</div>

<footer>
    &copy; <?= date('Y'); ?> FireTrack — Inventaris Damkar
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
