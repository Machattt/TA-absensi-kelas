<?php
// Kalau belum login, usir ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}



// Ambil total seluruh siswa buat statistik
$stmt = $pdo->query("SELECT COUNT(*) FROM siswa");
$total_siswa = $stmt->fetchColumn();

$today = date('Y-m-d'); // Tanggal hari ini

// Hitung berapa siswa yang udah hadir (unik berdasarkan NISN) hari ini
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT nisn) FROM absensi WHERE DATE(waktu_scan) = ?");
$stmt->execute([$today]);
$hadir_hari_ini = $stmt->fetchColumn();

// Hitung sisanya (yang belum hadir atau alpa) dan persentase kehadiran
$belum_hadir = max(0, $total_siswa - $hadir_hari_ini);
$persentase = $total_siswa > 0 ? round(($hadir_hari_ini / $total_siswa) * 100, 1) : 0;

// Ambil 10 aktivitas absen terbaru hari ini buat ditampilin di feed (Real-time feed)
$stmt = $pdo->prepare("SELECT s.nama_lengkap, a.waktu_scan, a.status 
                       FROM absensi a
                       JOIN siswa s ON a.nisn = s.nisn
                       WHERE DATE(a.waktu_scan) = ?
                       ORDER BY a.waktu_scan DESC
                       LIMIT 10");
$stmt->execute([$today]);
$feed = $stmt->fetchAll();

// Notifications and stats
// (Sekarang di-fetch secara global dari index.php)
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-left">
        <h1>Halo, Guru! 👋</h1>
        <p>Anda masuk sebagai Guru. Selamat bekerja!</p>
    </div>
</div>

<!-- Stat Grid (Pastel) -->
<div class="stat-grid">
    <div class="stat-box stat-box-bg-blue">
        <div class="stat-box-title">Total Siswa</div>
        <div class="stat-box-value"><?= $total_siswa ?></div>
        <svg class="stat-box-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
    </div>
    
    <div class="stat-box stat-box-bg-green">
        <div class="stat-box-title">Hadir</div>
        <div class="stat-box-value"><?= $hadir_hari_ini ?></div>
        <svg class="stat-box-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>

    <div class="stat-box stat-box-bg-red">
        <div class="stat-box-title">Belum Absen</div>
        <div class="stat-box-value"><?= $belum_hadir ?></div>
        <svg class="stat-box-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>

    <div class="stat-box stat-box-bg-orange">
        <div class="stat-box-title">Persentase</div>
        <div class="stat-box-value"><?= $persentase ?>%</div>
        <svg class="stat-box-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
    </div>
</div>

<!-- Real-time Feed Section -->
<div class="feed-section">
    <div class="feed-header">
        <h2 class="feed-title">AKTIVITAS ABSENSI TERBARU</h2>
    </div>
    <div style="overflow-x: auto;">
        <table class="table-feed">
            <thead>
                <tr>
                    <th>SISWA</th>
                    <th>STATUS</th>
                    <th>WAKTU</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($feed) > 0): ?>
                    <?php foreach($feed as $row): 
                        $time = date('H:i', strtotime($row['waktu_scan']));
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                        <td>
                            <span style="color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td style="color: #64748b; font-weight: 500;"><?= $time ?> WIB</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">
                            <div class="feed-empty">
                                Belum ada aktivitas absensi hari ini. Harap menunggu scan pertama.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
