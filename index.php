<?php
require_once 'auth/auth_check.php';
require_once 'config/database.php';

// --- BACKGROUND SCRIPTS: Auto-Cleanup & Auto-Alpa ---
try {
    // Pastikan tabel pengaturan ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        jam_masuk TIME NOT NULL DEFAULT '06:45:00',
        jam_pulang TIME NOT NULL DEFAULT '15:00:00',
        last_alpa_check DATE NULL,
        last_cleanup DATE NULL
    )");
    
    $stmt = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
    $settings = $stmt->fetch();
    if (!$settings) {
        $pdo->exec("INSERT INTO pengaturan (jam_masuk, jam_pulang) VALUES ('06:45:00', '15:00:00')");
        $settings = ['jam_masuk' => '06:45:00', 'jam_pulang' => '15:00:00', 'last_alpa_check' => null, 'last_cleanup' => null];
    }

    $today = date('Y-m-d');
    $timeNow = date('H:i:s');

    // Hapus foto absen > 30 hari (Jalankan 1x sehari)
    if ($settings['last_cleanup'] !== $today) {
        $stmtOld = $pdo->query("SELECT id_absensi, foto_path FROM absensi WHERE foto_path IS NOT NULL AND waktu_scan < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        while ($row = $stmtOld->fetch()) {
            if ($row['foto_path'] !== '-') {
                $pathFile = realpath(__DIR__) . '/' . ltrim($row['foto_path'], '/\\');
                if (file_exists($pathFile)) { @unlink($pathFile); }
            }
        }
        // Set foto_path jadi NULL agar data absensi tetap ada tapi fotonya hilang
        $pdo->exec("UPDATE absensi SET foto_path = NULL WHERE foto_path IS NOT NULL AND waktu_scan < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $pdo->exec("UPDATE pengaturan SET last_cleanup = '$today'");
    }

    // Auto-Alpa: Jam > jam_pulang (Jalankan 1x sehari)
    if ($settings['last_alpa_check'] !== $today && $timeNow > $settings['jam_pulang']) {
        // Cek apakah kolom keterangan ada, jika tidak, alter table
        $cekKolom = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'keterangan'");
        if ($cekKolom->rowCount() === 0) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN keterangan VARCHAR(255) NULL AFTER status");
        }

        // Insert Alpa untuk siswa yang belum ada di absensi hari ini
        $sqlAlpa = "INSERT INTO absensi (nisn, uid_rfid, status, foto_path, waktu_scan, keterangan)
                    SELECT s.nisn, s.uid_rfid, 'Alpa', '-', NOW(), 'Otomatis oleh Sistem'
                    FROM siswa s
                    WHERE s.nisn NOT IN (SELECT nisn FROM absensi WHERE DATE(waktu_scan) = CURDATE())";
        $pdo->exec($sqlAlpa);
        $pdo->exec("UPDATE pengaturan SET last_alpa_check = '$today'");
    }

} catch (PDOException $e) {
    
}
// --- END BACKGROUND SCRIPTS ---

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Ambil data user yang sedang login
$stmtUser = $pdo->prepare("SELECT username FROM users WHERE id_user = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
$usernameDisplay = $currentUser ? ucfirst($currentUser['username']) : 'Guru';

$pageTitles = [
    'dashboard' => 'Sistem Informasi Absensi',
    'scan' => 'Terminal Scan Kehadiran',
    'siswa' => 'Manajemen Data Siswa',
    'kelas' => 'Manajemen Data Kelas',
    'laporan' => 'Rekapitulasi Laporan Harian',
    'user' => 'Pengaturan Akun Guru',
    'input_manual' => 'Entri Absensi Manual',
    'pengaturan' => 'Konfigurasi Jam Operasional'
];
$currentTitle = $pageTitles[$page] ?? ucfirst($page);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Kelas 11 RPL 2- <?= $currentTitle ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
    <div class="app-container">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="assets/img/LogoRpl2.png" class="brand-logo" alt="Logo">
                <div class="brand-text">
                    ABSEN<br>REK!
                </div>
            </div>

            <div class="sidebar-menu">
                <div class="menu-label">MAIN MENU</div>
                <a href="index.php?page=dashboard" class="side-item <?= $page == 'dashboard' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
                <a href="index.php?page=scan" class="side-item <?= $page == 'scan' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    Scan Absen
                </a>
                <a href="index.php?page=laporan" class="side-item <?= $page == 'laporan' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Laporan Harian
                </a>

                <div class="menu-label">DATA & SETTINGS</div>
                <a href="index.php?page=siswa" class="side-item <?= $page == 'siswa' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Data Siswa
                </a>

                <a href="index.php?page=user" class="side-item <?= $page == 'user' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    User Guru
                </a>
                <a href="index.php?page=input_manual" class="side-item <?= $page == 'input_manual' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Input Manual
                </a>
                <a href="index.php?page=pengaturan" class="side-item <?= $page == 'pengaturan' ? 'active' : '' ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Jam Operasional
                </a>
            </div>

            <div class="sidebar-footer">
                <a href="index.php?page=user" class="admin-profile">
                    <div class="admin-avatar">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.2; font-weight: 500;">Login sebagai:</span>
                        <span style="line-height: 1.2;"><?= htmlspecialchars($usernameDisplay) ?></span>
                    </div>
                </a>
                <a href="auth/logout.php" class="side-logout confirm-action" data-title="Logout?" data-text="Yakin ingin keluar dari sistem?">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-wrapper">
            <header class="top-header">
                <div class="breadcrumb">
                    <?= $currentTitle ?>
                </div>
                <div class="top-clock" id="global-clock">
                    --:--:-- WIB - --, -- -- ----
                </div>
            </header>

            <div class="page-content">
                <?php
                $file = "pages/{$page}.php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<div class='alert alert-error'>Halaman tidak ditemukan!</div>";
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        // Global Clock Script
        function updateGlobalClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dateString = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()}`;
            
            document.getElementById('global-clock').innerText = `${timeString} WIB - ${dateString}`;
        }
        setInterval(updateGlobalClock, 1000);
        updateGlobalClock();
    </script>
    
    <!-- Custom Scripts -->
    <?php if($page == 'scan'): ?>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script defer src="assets/js/app.js?v=<?= time() ?>"></script>
    <?php endif; ?>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const confirmButtons = document.querySelectorAll('.confirm-action');
            confirmButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    const title = this.getAttribute('data-title') || 'Konfirmasi';
                    const text = this.getAttribute('data-text') || 'Yakin ingin melanjutkan aksi ini?';
                    
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Lanjutkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed && href) {
                            window.location.href = href;
                        }
                    });
                });
            });

            // Automatically add close buttons to all alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                closeBtn.className = 'alert-close';
                closeBtn.setAttribute('title', 'Tutup');
                closeBtn.onclick = () => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'scale(0.95)';
                    setTimeout(() => alert.remove(), 300);
                };
                alert.appendChild(closeBtn);
            });
        });
    </script>
</body>
</html>
