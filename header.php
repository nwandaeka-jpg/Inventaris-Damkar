<?php
// Catatan: Variabel $user, $userName, $userRole, $isAdmin
// harus sudah terdefinisi di file utama sebelum include ini
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - FireTrack</title>

<!-- Bootstrap & Font Awesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #D93A2F;    /* Merah Api */
    --accent: #FFC107;     /* Kuning Aksen */
    --success: #28a745;    /* Hijau */
    --dark-blue: #0b1220; 
    --light-bg: #f8f9fa;
    --text-dark: #212529;
    --nav-height: 64px;
    --sidebar-width: 250px;
}

html, body { height: 100%; }
body {
    font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, Arial;
    background: var(--light-bg);
    color: var(--text-dark);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding-top: var(--nav-height);
    padding-left: var(--sidebar-width);
}

/* ---------------------- */
/* NAVBAR */
/* ---------------------- */
.navbar {
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    height: var(--nav-height);
    background: linear-gradient(90deg, #ff1302ff, var(--primary));
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    z-index: 1030;
}
.brand-logo img { height: 48px; width: auto; object-fit: contain; }
.nav-link-item { color: #fff !important; font-weight: 500; transition: color 0.3s; }
.nav-link-item:hover { color: var(--accent) !important; }

/* ---------------------- */
/* FIRETRACK TEXT STYLE */
/* ---------------------- */
.firetrack-text {
    font-weight: 700;
    font-size: 1.3rem;
    letter-spacing: 1px;
    background: linear-gradient(110deg, #FFFFFF, var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

/* ---------------------- */
/* SIDEBAR */
/* ---------------------- */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    background: var(--dark-blue);
    color: #f8f9fa;
    z-index: 1040;
    padding-top: 15px;
    overflow-y: auto;
    box-shadow: 4px 0 10px rgba(0,0,0,0.5);
}
.sidebar .sidebar-header { 
    padding: 15px 20px; 
    margin-bottom: 20px; 
    border-bottom: 1px solid rgba(255,255,255,0.1); 
}
.sidebar-menu .list-group-item {
    background-color: transparent;
    color: #f8f9fa;
    border: none;
    padding: 12px 20px;
    font-weight: 500;
    transition: all 0.2s;
}
.sidebar-menu .list-group-item:hover { 
    background-color: rgba(255,255,255,0.08); 
    color: var(--accent); 
}
.sidebar-menu .list-group-item.active { 
    background-color: rgba(255,255,255,0.1); 
    color: #fff; 
    border-left: 5px solid var(--accent); 
}
.sidebar-menu .list-group-item.active i { color: var(--accent); }

/* ---------------------- */
/* CARD & BADGES */
/* ---------------------- */
.dashboard-card { 
    border-radius: 12px; 
    box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
    transition: transform 0.3s; 
}
.dashboard-card:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
}
.summary-box { 
    border-radius: 12px; 
    padding: 20px; 
    color: #fff; 
    position: relative; 
    overflow: hidden; 
}
.summary-box i.fa { 
    font-size: 5rem; 
    opacity: 0.15; 
    position: absolute; 
    bottom: 5px; 
    right: 15px; 
}
.bg-total { background: var(--dark-blue); }
.bg-masuk { background: var(--success); }
.bg-keluar { background: var(--primary); }
.bg-kategori { background: var(--accent); color: var(--dark-blue); }
.bg-kategori i.fa { color: var(--dark-blue); opacity: 0.25; }

.role-badge {
    background-color: <?= $isAdmin ? 'var(--accent)' : 'var(--primary)'; ?>;
    color: <?= $isAdmin ? 'var(--dark-blue)' : '#fff'; ?>;
    font-weight: 600;
    padding: 0.25em 0.6em;
    border-radius: 50px;
}

/* ---------------------- */
/* FOOTER & LAYOUT BASE */
/* ---------------------- */
footer {
    background: var(--dark-blue);
    color: #bdbdbd;
    text-align: center;
    padding: 15px 0;
    margin-top: auto;
    font-size: 0.85rem;
    width: 100%;
}
.main-content { flex: 1 0 auto; }

/* Responsive adjustments */
@media (max-width: 992px) {
    body { padding-left: 0; padding-top: var(--nav-height); }
    .sidebar { display: none; }
    .navbar { left: 0; }
    .firetrack-text { display: none; }
    .navbar-brand { margin-right: auto; }
}
</style>
</head>
<body>
<?php
// Catatan: Tidak ada tag penutup di sini, akan dilanjutkan oleh _sidebar dan _navbar
?>
