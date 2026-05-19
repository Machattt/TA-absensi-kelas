<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$msg = '';

// Ensure keterangan column exists for manual notes.
try {
    $cekKolom = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'keterangan'");
    if ($cekKolom->rowCount() === 0) {
        $pdo->exec("ALTER TABLE absensi ADD COLUMN keterangan VARCHAR(255) NULL AFTER status");
    }
} catch (PDOException $e) {
    // Keep page usable even if column migration fails.
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nisn = $_POST['nisn'] ?? '';
    $status = $_POST['status'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $allowedStatus = ['Hadir', 'Izin', 'Sakit'];

    if (!in_array($status, $allowedStatus, true)) {
        $msg = "<div class='alert alert-error'>Status manual tidak valid.</div>";
    } elseif ($status !== 'Hadir' && $keterangan === '') {
        $msg = "<div class='alert alert-error'>Keterangan wajib diisi untuk status Izin atau Sakit.</div>";
    } else {
    
        // Check if already attended today
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE nisn = ? AND DATE(waktu_scan) = ?");
        $stmt->execute([$nisn, $today]);
        
        if ($stmt->fetchColumn() > 0) {
            $msg = "<div class='alert alert-error'>Siswa ini sudah memiliki data absensi hari ini.</div>";
        } else {
            try {
                $statusSimpan = $status . ' (Manual)';
                $keteranganSimpan = $keterangan !== '' ? $keterangan : '-';
                $stmt = $pdo->prepare("INSERT INTO absensi (nisn, uid_rfid, status, keterangan, foto_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nisn, 'MANUAL', $statusSimpan, $keteranganSimpan, '-']);
                $msg = "<div class='alert alert-success'>Absensi manual berhasil disimpan!</div>";
            } catch(PDOException $e) {
                $msg = "<div class='alert alert-error'>Terjadi kesalahan saat menyimpan data.</div>";
            }
        }
    }
}

// Get all students for dropdown
$stmt = $pdo->query("SELECT nisn, nama_lengkap FROM siswa ORDER BY nama_lengkap ASC");
$siswa = $stmt->fetchAll();
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Input Absensi Manual</h2>
        <a href="index.php?page=dashboard" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
    <div class="card-body">
        <?= $msg ?>
        <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.875rem;">
            Gunakan fitur ini jika siswa tidak dapat scan RFID. Anda bisa memilih status kehadiran dan menambahkan keterangan pendukung.
        </p>
        <form action="index.php?page=input_manual" method="POST">
            <div class="form-group">
                <label>Pilih Siswa</label>
                <select name="nisn" required>
                    <option value="">-- Cari & Pilih Siswa --</option>
                    <?php foreach($siswa as $s): ?>
                        <option value="<?= htmlspecialchars($s['nisn']) ?>">
                            <?= htmlspecialchars($s['nama_lengkap']) ?> (<?= htmlspecialchars($s['nisn']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="">-- Pilih Status --</option>
                    <option value="Hadir">Hadir</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                </select>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" placeholder="Contoh: Sakit Demam / Izin Lomba LKS"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">Simpan Kehadiran</button>
        </form>
    </div>
</div>
