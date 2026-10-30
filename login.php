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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil dan sanitasi input
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Query untuk mengambil data pengguna berdasarkan username
    $sql = "SELECT id, username, password, nama, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();

            // 3. Verifikasi password
            if (password_verify($password, $user_data['password'])) {
                // 4. Login berhasil
                $_SESSION['user'] = [
                    'id' => $user_data['id'],
                    'username' => $user_data['username'],
                    'nama' => $user_data['nama'],
                    'role' => $user_data['role']
                ];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Username atau Password salah.";
            }
        } else {
            $error = "Username atau Password salah.";
        }
        $stmt->close();
    } else {
        $error = "Terjadi kesalahan internal: " . $conn->error;
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
<title>Login - FireTrack</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #D93A2F;   /* Merah Api */
    --accent: #FFC107;    /* Kuning Aksen */
    --dark-blue: #0b1220; /* Biru Gelap */
    --light-bg: #f8f9fa;
    --text-dark: #212529;
    --nav-height: 64px;
}

html, body { height: 100%; }
body {
    font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    background: linear-gradient(135deg, var(--dark-blue), #071025);
    color: #fff;
    display: flex;
    flex-direction: column;
}

/* Navbar */
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

/* Container login */
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 80px 15px;
    flex-grow: 1;
}

/* Card login */
.login-card {
    background: #fff;
    color: var(--text-dark);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    max-width: 420px;
    width: 100%;
    padding: 35px;
    animation: fadeInDown 0.8s ease-out;
}

.login-card h2 {
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

/* Tombol login */
.btn-custom {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.2s;
}
.btn-custom:hover { background-color: #a82e26; transform: translateY(-1px); color: #fff; }
.btn-custom:active { background-color: #8c231d !important; }

/* Alert error */
.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
    border-radius: 8px;
}

/* Footer */
footer {
    background: var(--dark-blue);
    color: #bdbdbd;
    padding: 15px 0;
    margin-top: auto;
    font-size: 0.85rem;
    text-align: center;
    width: 100%;
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
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

<!-- Login Form -->
<div class="login-container">
    <div class="login-card">
        <div class="text-center mb-4">
            <img src="logo.png" alt="FireTrack Logo" style="max-width: 80px;">
        </div>
        <h2 class="text-center">MASUK KE SISTEM</h2>
        <p class="text-center text-muted small mb-4">Gunakan akun Petugas atau Admin Anda.</p>

        <?php if ($error): ?>
            <div class="alert alert-error text-center small" role="alert">
                <i class="fa fa-exclamation-triangle me-1"></i> <?= esc($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label fw-bold small">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user" style="color:var(--primary)"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-bold small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock" style="color:var(--primary)"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-custom btn-lg"><i class="fa fa-right-to-bracket me-2"></i> MASUK</button>
            </div>
        </form>

        <p class="text-center mt-3 small">
            Belum punya akun? 
            <a href="register.php" class="text-decoration-none" style="color:var(--primary); font-weight:600;">Daftar di sini</a>
        </p>
    </div>
</div>

<footer>
    &copy; <?= date('Y'); ?> FireTrack — Inventaris Damkar
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
