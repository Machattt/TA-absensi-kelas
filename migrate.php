<?php
require 'config/database.php';

try {
    // 4. Tambahin kolom keterangan ke tabel absensi kalau belum ada
    $keteranganResult = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'keterangan'");
    if ($keteranganResult->rowCount() == 0) {
        $pdo->exec("ALTER TABLE absensi ADD COLUMN keterangan VARCHAR(255) NULL AFTER status");
    }

    // 5. Pastikan nisn di tabel siswa dan absensi tipenya VARCHAR(50) biar panjangnya pas
    $fkStmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'absensi'
          AND COLUMN_NAME = 'nisn'
          AND REFERENCED_TABLE_NAME = 'siswa'
        LIMIT 1
    ");
    $fkName = $fkStmt->fetchColumn();

    if ($fkName) {
        $pdo->exec("ALTER TABLE absensi DROP FOREIGN KEY `{$fkName}`");
    }

    $pdo->exec("ALTER TABLE siswa MODIFY COLUMN nisn VARCHAR(50) NOT NULL");
    $pdo->exec("ALTER TABLE absensi MODIFY COLUMN nisn VARCHAR(50) NOT NULL");

    $pdo->exec("ALTER TABLE absensi ADD CONSTRAINT fk_absensi_nisn FOREIGN KEY (nisn) REFERENCES siswa(nisn) ON DELETE CASCADE");

    // 6. Tambahin foto_path di tabel siswa kalau belum ada (buat nyimpen foto pas pendaftaran)
    $fotoPathResult = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'foto_path'");
    if ($fotoPathResult->rowCount() == 0) {
        $pdo->exec("ALTER TABLE siswa ADD COLUMN foto_path VARCHAR(255) NULL AFTER jenis_kelamin");
    }


    echo "Migration Success!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
