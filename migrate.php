<?php
require 'config/database.php';

try {
    // 1. Create kelas table
    $pdo->exec("CREATE TABLE IF NOT EXISTS kelas (
        id_kelas INT AUTO_INCREMENT PRIMARY KEY,
        nama_kelas VARCHAR(50) NOT NULL UNIQUE
    )");

    // 2. Insert default '11 RPL 2' class
    $pdo->exec("INSERT IGNORE INTO kelas (id_kelas, nama_kelas) VALUES (1, '11 RPL 2')");

    // 3. Add id_kelas to siswa if it doesn't exist
    $result = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'id_kelas'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE siswa ADD COLUMN id_kelas INT DEFAULT 1");
        // Add foreign key constraint
        $pdo->exec("ALTER TABLE siswa ADD FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas)");
    }

    // 4. Add keterangan to absensi if it doesn't exist
    $keteranganResult = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'keterangan'");
    if ($keteranganResult->rowCount() == 0) {
        $pdo->exec("ALTER TABLE absensi ADD COLUMN keterangan VARCHAR(255) NULL AFTER status");
    }

    // 5. Ensure siswa.nisn and absensi.nisn use VARCHAR(50)
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

    // 6. Add foto_path to siswa if it doesn't exist (foto master saat registrasi)
    $fotoPathResult = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'foto_path'");
    if ($fotoPathResult->rowCount() == 0) {
        $pdo->exec("ALTER TABLE siswa ADD COLUMN foto_path VARCHAR(255) NULL AFTER jenis_kelamin");
    }

    echo "Migration Success!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
