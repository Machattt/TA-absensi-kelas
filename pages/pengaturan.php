<?php
// Biasa, cek sesi dulu
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$msg = '';
$action = $_GET['action'] ?? '';

// Proses Update Jadwal Mingguan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_jadwal'])) {
    try {
        $stmtUpdate = $pdo->prepare("UPDATE jadwal_operasional SET jam_masuk = ?, jam_pulang = ?, is_libur = ? WHERE hari = ?");
        for ($i = 1; $i <= 7; $i++) {
            $jam_masuk = $_POST['masuk'][$i] ?? '06:45:00';
            $jam_pulang = $_POST['pulang'][$i] ?? '15:00:00';
            $is_libur = isset($_POST['libur'][$i]) ? 1 : 0; // Kalau dicentang berarti libur
            $stmtUpdate->execute([$jam_masuk, $jam_pulang, $is_libur, $i]);
        }
        $msg = "<div class='alert alert-success'>Jadwal mingguan berhasil disimpan!</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-error'>Gagal menyimpan jadwal.</div>";
    }
}

// Proses Tambah Hari Libur Nasional / Spesial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_libur'])) {
    $tanggal = $_POST['tanggal_libur'] ?? '';
    $keterangan = $_POST['keterangan_libur'] ?? '';
    if (!empty($tanggal) && !empty($keterangan)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO hari_libur (tanggal, keterangan) VALUES (?, ?)");
            $stmt->execute([$tanggal, $keterangan]);
            $msg = "<div class='alert alert-success'>Hari libur berhasil ditambahkan!</div>";
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-error'>Gagal menambah hari libur (mungkin tanggal sudah ada).</div>";
        }
    }
}

// Handle Delete Hari Libur
if ($action === 'delete_libur' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM hari_libur WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $msg = "<div class='alert alert-success'>Hari libur berhasil dihapus!</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-error'>Gagal menghapus hari libur.</div>";
    }
}

// Fetch Jadwal Operasional
$jadwalList = $pdo->query("SELECT * FROM jadwal_operasional ORDER BY hari ASC")->fetchAll();

// Fetch Hari Libur
$liburList = $pdo->query("SELECT * FROM hari_libur ORDER BY tanggal ASC")->fetchAll();
?>

<?= $msg ?>
<div class="settings-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Bagian 1: Jadwal Mingguan -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Jadwal Operasional Mingguan</h2>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.875rem;">Atur jam masuk dan pulang untuk tiap harinya. Centang kotak "Libur" untuk menetapkan hari libur akhir pekan.</p>
            <form action="index.php?page=pengaturan" method="POST">
                <input type="hidden" name="update_jadwal" value="1">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 0.5rem; text-align: left;">Hari</th>
                                <th style="padding: 0.5rem; text-align: left;">Jam Masuk</th>
                                <th style="padding: 0.5rem; text-align: left;">Jam Pulang</th>
                                <th style="padding: 0.5rem; text-align: center;">Libur?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($jadwalList as $jd): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.5rem; font-weight: 500;"><?= htmlspecialchars($jd['nama_hari']) ?></td>
                                <td style="padding: 0.5rem;"><input type="time" name="masuk[<?= $jd['hari'] ?>]" value="<?= htmlspecialchars($jd['jam_masuk']) ?>" class="form-control" style="padding: 0.25rem;"></td>
                                <td style="padding: 0.5rem;"><input type="time" name="pulang[<?= $jd['hari'] ?>]" value="<?= htmlspecialchars($jd['jam_pulang']) ?>" class="form-control" style="padding: 0.25rem;"></td>
                                <td style="padding: 0.5rem; text-align: center;"><input type="checkbox" name="libur[<?= $jd['hari'] ?>]" value="1" <?= $jd['is_libur'] ? 'checked' : '' ?> style="transform: scale(1.2);"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Jadwal</button>
            </form>
        </div>
    </div>

    <!-- Bagian 2: Hari Libur Nasional -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Kalender Libur Nasional</h2>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.875rem;">Tambahkan tanggal merah agar sistem tidak otomatis menghitung Alpa pada tanggal tersebut.</p>
            
            <form action="index.php?page=pengaturan" method="POST" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <input type="hidden" name="add_libur" value="1">
                <input type="date" name="tanggal_libur" class="form-control" required style="flex: 1; min-width: 120px;">
                <input type="text" name="keterangan_libur" class="form-control" placeholder="Keterangan (Cth: Idul Fitri)" required style="flex: 2; min-width: 150px;">
                <button type="submit" class="btn btn-success">Tambah</button>
            </form>

            <div style="max-height: 400px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 0.5rem; text-align: left;">Tanggal</th>
                            <th style="padding: 0.5rem; text-align: left;">Keterangan</th>
                            <th style="padding: 0.5rem; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($liburList) == 0): ?>
                            <tr><td colspan="3" style="text-align: center; padding: 1rem; color: #94a3b8;">Belum ada data hari libur.</td></tr>
                        <?php endif; ?>
                        <?php foreach($liburList as $libur): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.5rem;"><?= date('d M Y', strtotime($libur['tanggal'])) ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($libur['keterangan']) ?></td>
                            <td style="padding: 0.5rem; text-align: center;">
                                <a href="index.php?page=pengaturan&action=delete_libur&id=<?= $libur['id'] ?>" class="btn btn-secondary btn-sm confirm-action" data-title="Hapus Libur?" data-text="Yakin ingin menghapus hari libur ini?" style="background-color: #ef4444; color: white; padding: 0.25rem 0.5rem; font-size: 0.75rem;">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Add simple responsive style for grid -->
<style>
@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
