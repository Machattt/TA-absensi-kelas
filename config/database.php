<?php
// config/database.php

// Set zona waktu ke Jakarta biar pas sama waktu lokal
date_default_timezone_set('Asia/Jakarta');

$host = '127.0.0.1';
$db   = 'db_absensi';
$user = 'root';
$pass = ''; // Default laragon passwordnya kosong
$charset = 'utf8mb4';

// Setup koneksi ke MySQL pakai PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Biar errornya gampang ketahuan
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ambil data jadi array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options); // Coba konek ke database
} catch (\PDOException $e) {
    // Kalau gagal konek, tampilkan errornya
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Fungsi-fungsi buat keamanan CSRF (biar aman dari serangan pemalsuan request)
if (!function_exists('generateCsrfToken')) {
    // Bikin token CSRF baru kalau belum ada
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Cek token CSRF valid atau nggak
    function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Bikin input hidden buat token CSRF di form
    function csrfTokenField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
    }
}
?>
