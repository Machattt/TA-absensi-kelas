<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$msg = '';

// Handle Delete
if ($action == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$_GET['id']]);
        $msg = "<div class='alert alert-success'>Kelas berhasil dihapus!</div>";
    } catch(PDOException $e) {
        $msg = "<div class='alert alert-error'>Gagal: Kelas ini masih digunakan oleh siswa.</div>";
    }
    $action = 'list';
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = trim($_POST['nama_kelas']);
    
    if ($action == 'add') {
        try {
            $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas) VALUES (?)");
            $stmt->execute([$nama_kelas]);
            $msg = "<div class='alert alert-success'>Kelas berhasil ditambahkan!</div>";
            $action = 'list';
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-error'>Gagal: Nama kelas mungkin sudah ada.</div>";
        }
    } elseif ($action == 'edit') {
        $id_kelas = $_POST['id_kelas'];
        try {
            $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas=? WHERE id_kelas=?");
            $stmt->execute([$nama_kelas, $id_kelas]);
            $msg = "<div class='alert alert-success'>Kelas berhasil diupdate!</div>";
            $action = 'list';
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-error'>Gagal update kelas.</div>";
        }
    }
}
?>

<?php if ($action == 'list'): ?>
    <?= $msg ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Data Kelas</h2>
            <a href="index.php?page=kelas&action=add" class="btn btn-primary btn-sm">Tambah Kelas</a>
        </div>
        <div class="card-body" style="overflow-x: auto;">
            <?php
            // Query untuk mendapatkan kelas beserta jumlah siswanya
            $stmt = $pdo->query("SELECT k.*, COUNT(s.nisn) as total_siswa 
                                 FROM kelas k 
                                 LEFT JOIN siswa s ON k.id_kelas = s.id_kelas 
                                 GROUP BY k.id_kelas 
                                 ORDER BY k.nama_kelas ASC");
            $kelas = $stmt->fetchAll();
            ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kelas</th>
                        <th>Total Siswa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($kelas as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_kelas']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td><span class="badge badge-success"><?= $row['total_siswa'] ?> Siswa</span></td>
                        <td>
                            <a href="index.php?page=kelas&action=edit&id=<?= $row['id_kelas'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </a>
                            <?php if($row['total_siswa'] == 0): ?>
                            <a href="index.php?page=kelas&action=delete&id=<?= $row['id_kelas'] ?>" class="btn btn-sm btn-danger confirm-action" data-title="Hapus Kelas?" data-text="Yakin hapus kelas ini?" title="Hapus">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($kelas) == 0): ?>
                    <tr><td colspan="4" style="text-align: center;">Belum ada data kelas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <?php
    $data = ['id_kelas'=>'', 'nama_kelas'=>''];
    if ($action == 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$_GET['id']]);
        $data = $stmt->fetch() ?: $data;
    }
    ?>
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <div class="card-header">
            <h2 class="card-title"><?= $action == 'add' ? 'Tambah' : 'Edit' ?> Kelas</h2>
            <a href="index.php?page=kelas" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
        <div class="card-body">
            <?= $msg ?>
            <form action="index.php?page=kelas&action=<?= $action ?>" method="POST">
                <?php if($action == 'edit'): ?>
                <input type="hidden" name="id_kelas" value="<?= htmlspecialchars($data['id_kelas']) ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Nama Kelas</label>
                    <input type="text" name="nama_kelas" value="<?= htmlspecialchars($data['nama_kelas']) ?>" placeholder="Contoh: 11 RPL 2" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= $action == 'add' ? 'Simpan Kelas' : 'Update Kelas' ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>
