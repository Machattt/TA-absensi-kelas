<?php
session_start(); // Mulai sesi buat ngecek status login

// Auto logout kalau nggak ada aktivitas selama 30 menit
$inactivity_timeout = 30 * 60; // 30 menit
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_timeout)) {
    // Kalau kelamaan nganggur, hapus sesi terus lempar ke halaman login
    session_unset();
    session_destroy();
    header("Location: /absensi kelas/auth/login.php");
    exit();
}

// Update waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// Kalau belum login, lempar lagi ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: /absensi kelas/auth/login.php");
    exit();
}
?>
