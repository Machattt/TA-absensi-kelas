<?php
/**
 * Preflight scan: Buat ngecek UID yang discan itu terdaftar atau tidak,
 * terus balikin URL foto aslinya buat dibandingin sama muka pas absen.
 */
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Harus login dulu pastinya
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Cuma nerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit();
}

$uid = trim($_POST['uid'] ?? '');
if ($uid === '') {
    echo json_encode(['status' => 'error', 'message' => 'UID kosong']);
    exit();
}

$placeholderUrl = 'assets/img/placeholder.png'; // Foto default kalau nggak ada

try {
    $hasFotoMaster = false;
    // Cek dulu, tabel siswanya punya kolom foto_path nggak?
    try {
        $cekFotoMaster = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'foto_path'");
        $hasFotoMaster = $cekFotoMaster->rowCount() > 0;
    } catch (PDOException $e) {
        $hasFotoMaster = false;
    }

    if (!$hasFotoMaster) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Kolom foto siswa belum tersedia. Jalankan migrate.php.',
            'code' => 'NO_FOTO_COLUMN'
        ]);
        exit();
    }

    $stmt = $pdo->prepare(
        "SELECT s.nisn, s.nama_lengkap, s.foto_path
         FROM siswa s
         WHERE s.uid_rfid = ?"
    );
    $stmt->execute([$uid]);
    $siswa = $stmt->fetch();

    if (!$siswa) {
        echo json_encode(['status' => 'error', 'message' => 'Kartu tidak terdaftar']);
        exit();
    }

    $fotoMasterPath = isset($siswa['foto_path']) ? trim((string) $siswa['foto_path']) : '';
    $masterPhotoUrl = $placeholderUrl;

    if ($fotoMasterPath !== '' && $fotoMasterPath !== '-') {
        $fileSystemPath = '../' . ltrim($fotoMasterPath, '/\\');
        if (file_exists($fileSystemPath)) {
            $masterPhotoUrl = ltrim($fotoMasterPath, '/\\') . '?t=' . time();
        }
    }

    if ($masterPhotoUrl === $placeholderUrl) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Belum ada Foto Terdaftar untuk siswa ini. Upload di menu Data Siswa.',
            'code' => 'NO_MASTER_PHOTO'
        ]);
        exit();
    }

    echo json_encode([
        'status' => 'ok',
        'foto_master_url' => $masterPhotoUrl,
        'siswa' => [
            'nisn' => $siswa['nisn'],
            'nama_lengkap' => $siswa['nama_lengkap'],
            'nama_kelas' => '11 RPL 2'
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
