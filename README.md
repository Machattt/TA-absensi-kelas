# ABSEN REK!
## Sistem Informasi Absensi Berbasis Web dengan Integrasi RFID dan Webcam
[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

### One-line description
Sistem Informasi Absensi Berbasis Web dengan integrasi RFID dan Webcam

### Installation
```bash
git clone https://github.com/Machattt/TA-absensi-kelas.git
cd ABSEN-REK
```
1. Siapkan Database
Buat database baru dan import file SQL yang tersedia pada folder database.
2. Konfigurasi Database
Sesuaikan file koneksi database dengan server lokal Anda.
3. Jalankan Aplikasi
Aktifkan Apache dan MySQL, lalu akses aplikasi melalui browser.

### Usage
```php
<?php
// Contoh penggunaan API Face Recognition
require 'vendor/autoload.php';
use FaceAPI\FaceAPI;

$faceAPI = new FaceAPI('API_KEY');
$faceAPI->detectFace('foto_siswa.jpg');
?>
```
### URL Aplikasi
http://localhost/absensi%20kelas/

### Akun Default (Guru)
Username: guru
Password: password
