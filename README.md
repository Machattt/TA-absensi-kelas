# 📸 ABSEN REK

## 📖 Deskripsi Singkat

ABSEN REK adalah Sistem Informasi Absensi Berbasis Web dengan integrasi RFID dan Webcam yang dikembangkan sebagai proyek tugas akhir.

<br>

Sistem ini dibuat untuk membantu proses pencatatan kehadiran siswa secara digital serta memudahkan guru dalam memantau data absensi.

<br>

Selain itu, sistem ini juga dilengkapi dengan proses verifikasi wajah menggunakan kamera untuk membantu mengurangi praktik titip absen yang masih sering terjadi di lingkungan sekolah.

---

## 🎯 Tujuan Pengembangan

- Membantu proses absensi siswa secara digital.
- Mempermudah pengelolaan data kehadiran siswa.
- Mengurangi praktik titip absen melalui verifikasi wajah.
- Menyediakan laporan absensi yang terstruktur dan mudah diakses.

---

## 🚀 Fitur Utama

- Integrasi RFID dan Webcam untuk proses absensi siswa.
- Verifikasi wajah otomatis menggunakan Face Recognition.
- Pencegahan titip absen melalui pencocokan wajah dengan data master.
- Dashboard statistik dan aktivitas absensi secara real-time.
- Sistem Auto-Alpa berdasarkan jadwal operasional sekolah.
- Validasi keterlambatan secara otomatis.
- Sistem scan masuk dan scan pulang dalam satu terminal absensi.
- Manajemen data siswa beserta foto dan identitas RFID.
- Input manual untuk status hadir, izin, dan sakit.
- Laporan kehadiran siswa dengan filter tanggal.
- Export laporan ke format PDF dan Excel.

---

## ✨ Fitur Pendukung

- Login dan Logout Administrator/Guru.
- Session Timeout otomatis setelah 30 menit tidak aktif.
- Pencegahan Double Scan pada hari yang sama.
- Pencatatan status Hadir, Terlambat, Pulang, Izin, Sakit, dan Alpa.
- Kalender Hari Libur.
- Notifikasi visual menggunakan SweetAlert2.
- Notifikasi audio otomatis.
- Penyimpanan foto bukti absensi.
- Tampilan Kiosk Mode layar penuh.
- Pengelolaan data siswa melalui operasi CRUD lengkap.

---

## 🛠️ Tools & Teknologi

### Frontend
- HTML5
- CSS3
- JavaScript (Vanilla JS)
- Bootstrap
- Face API.js
- SweetAlert2
- html2pdf.js

### Backend
- PHP Native
- PDO (PHP Data Objects)

### Database
- MySQL / MariaDB

### Alat & Perangkat
- RFID Reader
- Webcam

---

## ⚙️ Cara Instalasi

<br>

### 1. Clone Repository

```bash
git clone https://github.com/Machattt/TA-absensi-kelas.git
cd ABSEN-REK
```

### 2. Siapkan Database

Buat database baru. <br>
Import file SQL yang tersedia pada folder database.

### 3. Konfigurasi Database

Sesuaikan file koneksi database dengan server lokal Anda.

### 4. Jalankan Aplikasi

Aktifkan Apache dan MySQL, lalu akses aplikasi melalui browser.

<br>

---

## 👨‍💻 Pengembang

Navyza Marcha Vega
XI RPL 2

<br>

ABSEN REK merupakan proyek tugas akhir yang dikembangkan untuk membantu digitalisasi proses absensi siswa serta mendukung pengelolaan data kehadiran yang lebih terstruktur di lingkungan sekolah.