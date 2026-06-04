<div align="center">

# ABSEN REK

**Sistem Informasi Absensi Berbasis Web dengan integrasi RFID dan Webcam**

</div>

Selamat datang di ABSEN REK! Repository ini berisi source code lengkap untuk sistem absensi digital yang dikembangkan sebagai proyek tugas akhir.

---

## Tentang Proyek

ABSEN REK adalah solusi digital untuk pencatatan kehadiran siswa yang mengintegrasikan teknologi RFID dan face recognition. Sistem ini dirancang untuk:

- Mempermudah proses absensi siswa secara real-time
- Mengurangi praktik titip absen melalui verifikasi wajah
- Membantu guru dalam pengelolaan dan analisis data kehadiran
- Menyediakan laporan absensi yang terstruktur dan mudah diakses

---

## 🎯 Tujuan Pengembangan

- Membantu proses absensi siswa secara digital
- Mempermudah pengelolaan data kehadiran siswa
- Mengurangi praktik titip absen melalui verifikasi wajah
- Menyediakan laporan absensi yang terstruktur dan mudah diakses

---

## 🚀 Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| Integrasi RFID & Webcam | Scan kartu dan capture wajah siswa |
| Face Recognition | Verifikasi wajah otomatis menggunakan teknologi AI |
| Pencegahan Titip Absen | Pencocokan wajah dengan data master yang tersimpan |
| Dashboard Real-time | Statistik dan aktivitas absensi secara live |
| Auto-Alpa | Sistem otomatis menandai alpa berdasarkan jadwal |
| Validasi Keterlambatan | Deteksi dan pencatatan waktu keterlambatan |
| Scan Masuk/Pulang | Terminal absensi dengan dual entry |
| Manajemen Siswa | CRUD lengkap untuk data siswa |
| Input Manual | Pencatatan status hadir, izin, sakit |
| Laporan Fleksibel | Filter dan export laporan ke PDF/Excel |

---

## ✨ Fitur Pendukung

- Login dan Logout Administrator/Guru
- Session Timeout otomatis setelah 30 menit tidak aktif
- Pencegahan Double Scan pada hari yang sama
- Pencatatan status: Hadir, Terlambat, Pulang, Izin, Sakit, Alpa
- Kalender Hari Libur
- Notifikasi visual menggunakan SweetAlert2
- Notifikasi audio otomatis
- Penyimpanan foto bukti absensi
- Pengelolaan data siswa melalui operasi CRUD lengkap

---

## Teknologi

**Frontend**
- HTML5, CSS3
- JavaScript (Vanilla)
- Bootstrap
- Face API.js
- SweetAlert2
- html2pdf.js

**Backend**
- PHP Native
- PDO (PHP Data Objects)

**Database**
- MySQL / MariaDB

**Hardware**
- RFID Reader
- Webcam

---

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Machattt/TA-absensi-kelas.git
cd ABSEN-REK
```

### 2. Siapkan Database

1. Buat database baru
2. Import file SQL yang tersedia di folder `database`

### 3. Konfigurasi Database

Sesuaikan file koneksi database (`config/database.php`) dengan server lokal Anda.

### 4. Jalankan Aplikasi

Aktifkan Apache dan MySQL, lalu akses aplikasi:

```
http://localhost/absensi%20kelas/
```

> **Akun Default (Guru)**  
> Username: `guru`  
> Password: `password`

---

## 👩‍💻 Pengembang

**Navyza Marcha Vega**  
Kelas XI RPL 2

---

ABSEN REK merupakan proyek tugas akhir yang dikembangkan untuk membantu digitalisasi proses absensi siswa serta mendukung pengelolaan data kehadiran yang lebih terstruktur di lingkungan sekolah.
