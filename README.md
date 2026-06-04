<div align="center">

# ABSEN REK!

**Sistem Informasi Absensi Berbasis Web dengan integrasi RFID dan Webcam**

</div>

Selamat datang di ABSEN REK! Repository ini berisi source code lengkap untuk sistem absensi digital yang dikembangkan sebagai proyek tugas akhir.

---

## Tentang Proyek

ABSEN REK! adalah Sistem Informasi Absensi Berbasis Web dengan integrasi RFID dan Webcam yang dikembangkan sebagai proyek tugas akhir.
Sistem ini dibuat untuk membantu proses pencatatan kehadiran siswa secara digital serta memudahkan guru dalam memantau data absensi.
Selain itu, sistem ini juga dilengkapi dengan proses verifikasi wajah menggunakan kamera untuk membantu mengurangi praktik titip absen 
yang masih sering terjadi di lingkungan sekolah.

---

## Tujuan Pengembangan

- Membantu proses absensi siswa secara digital
- Mempermudah pengelolaan data kehadiran siswa
- Mengurangi praktik titip absen melalui verifikasi wajah
- Menyediakan laporan absensi yang terstruktur dan mudah diakses

---

## Fitur Utama

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

## Fitur Pendukung

| Fitur | Deskripsi |
|-------|-----------|
| Login & Logout | Administrator dan Guru dapat login/logout |
| Session Timeout | Logout otomatis setelah 30 menit tidak aktif |
| Pencegahan Double Scan | Cegah scanning kartu yang sama di hari yang sama |
| Pencatatan Status | Hadir, Terlambat, Pulang, Izin, Sakit, Alpa |
| Kalender Hari Libur | Kelola jadwal libur sekolah |
| Notifikasi Visual | Alert dan feedback menggunakan SweetAlert2 |
| Notifikasi Audio | Suara otomatis untuk konfirmasi scan |
| Penyimpanan Foto | Bukti absensi tersimpan untuk verifikasi |
| Manajemen Data Siswa | CRUD lengkap dengan foto dan identitas RFID |

---

## Teknologi

### Frontend
![HTML5](https://img.shields.io/badge/HTML5-E34C26?style=flat-square&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![Face API](https://img.shields.io/badge/Face%20API-4A90E2?style=flat-square)
![SweetAlert2](https://img.shields.io/badge/SweetAlert2-FC8019?style=flat-square)
![html2pdf](https://img.shields.io/badge/html2pdf-FF6B6B?style=flat-square)

### Backend
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![PDO](https://img.shields.io/badge/PDO-336791?style=flat-square)

### Database
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)

### Hardware
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
