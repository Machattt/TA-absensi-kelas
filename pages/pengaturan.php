<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$msg = '';

// Buat tabel jika belum ada
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        jam_masuk TIME NOT NULL DEFAULT '06:45:00',
        jam_pulang TIME NOT NULL DEFAULT '15:00:00',
        last_alpa_check DATE NULL,
        last_cleanup DATE NULL
    )");
    
    // Insert default data if empty
    $cek = $pdo->query("SELECT id FROM pengaturan LIMIT 1");
    if ($cek->rowCount() === 0) {
        $pdo->exec("INSERT INTO pengaturan (jam_masuk, jam_pulang) VALUES ('06:45:00', '15:00:00')");
    }
} catch (PDOException $e) {
    $msg = "<div class='alert alert-error'>Gagal membuat tabel pengaturan: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jam_masuk'])) {
    $jam_masuk = trim($_POST['jam_masuk']);
    $jam_pulang = trim($_POST['jam_pulang']);
    
    try {
        $stmt = $pdo->prepare("UPDATE pengaturan SET jam_masuk = ?, jam_pulang = ?");
        $stmt->execute([$jam_masuk, $jam_pulang]);
        $msg = "<div class='alert alert-success'>Pengaturan berhasil disimpan!</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-error'>Gagal menyimpan pengaturan.</div>";
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
    $settings = $stmt->fetch();
} catch (PDOException $e) { }

?>

<?= $msg ?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Pengaturan Sistem</h2>
    </div>
    <div class="card-body">
        <form action="index.php?page=pengaturan" method="POST">
            <div class="form-group">
                <label>Batas Mulai Jam Masuk</label>
                <input type="time" name="jam_masuk" class="form-control" value="<?= htmlspecialchars($settings['jam_masuk'] ?? '06:45') ?>" required>
                <small style="color: var(--text-muted);">Siswa hanya bisa absen masuk setelah jam ini.</small>
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label>Batas Mulai Jam Pulang</label>
                <input type="time" name="jam_pulang" class="form-control" value="<?= htmlspecialchars($settings['jam_pulang'] ?? '15:00') ?>" required>
                <small style="color: var(--text-muted);">Siswa hanya bisa absen pulang setelah jam ini. Pengecekan otomatis Alpa juga berjalan setelah jam ini berakhir.</small>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem; width: 100%;">Simpan Pengaturan</button>
        </form>
    </div>
</div>
