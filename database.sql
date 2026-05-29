CREATE DATABASE IF NOT EXISTS db_absensi;
USE db_absensi;

CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Insert default admin (password is 'password' hashed with bcrypt)
INSERT IGNORE INTO users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


CREATE TABLE IF NOT EXISTS siswa (
    nisn VARCHAR(50) PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    uid_rfid VARCHAR(50) NOT NULL UNIQUE,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    foto_path VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS absensi (
    id_absensi INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(50) NOT NULL,
    uid_rfid VARCHAR(50) NOT NULL,
    waktu_scan DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Hadir',
    keterangan VARCHAR(255) NULL,
    foto_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (nisn) REFERENCES siswa(nisn) ON DELETE CASCADE
);


