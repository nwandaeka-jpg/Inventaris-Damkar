<?php
/**
 * logout.php
 * 
 * Fungsi: Mengakhiri sesi pengguna yang sedang aktif
 * dan mengarahkan kembali ke halaman login.
 */

// =======================
// 1. Memulai atau melanjutkan sesi
// =======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================
// 2. Menghapus semua variabel sesi
// =======================
$_SESSION = [];

// =======================
// 3. Menghapus cookie sesi jika ada
// =======================
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,          // Expired
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// =======================
// 4. Menghancurkan sesi di server
// =======================
session_destroy();

// =======================
// 5. Redirect ke halaman login
// =======================
header("Location: index.php");
exit;
?>
