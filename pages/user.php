<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$msg = '';

// Handle Delete
if ($action == 'delete' && isset($_GET['id'])) {
    if ($_GET['id'] == $_SESSION['user_id']) {
        $_SESSION['flash_msg'] = "<div class='alert alert-error'>Gagal: Anda tidak dapat menghapus akun Anda sendiri.</div>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
        if ($stmt->execute([$_GET['id']])) {
            $_SESSION['flash_msg'] = "<div class='alert alert-success'>Guru berhasil dihapus!</div>";
        }
    }
    header("Location: index.php?page=user");
    exit();
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($action == 'add') {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed_password]);
            $_SESSION['flash_msg'] = "<div class='alert alert-success'>Guru baru berhasil ditambahkan!</div>";
            header("Location: index.php?page=user");
            exit();
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-error'>Gagal: Username mungkin sudah digunakan.</div>";
        }
    } elseif ($action == 'edit') {
        $id_user = $_POST['id_user'];
        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=? WHERE id_user=?");
                $stmt->execute([$username, $hashed_password, $id_user]);
            } else {
                // If password is empty, update only username
                $stmt = $pdo->prepare("UPDATE users SET username=? WHERE id_user=?");
                $stmt->execute([$username, $id_user]);
            }
            $_SESSION['flash_msg'] = "<div class='alert alert-success'>Data guru berhasil diupdate!</div>";
            header("Location: index.php?page=user");
            exit();
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-error'>Gagal update data guru.</div>";
        }
    }
}

if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
?>

<?php if ($action == 'list'): ?>
    <?= $msg ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manajemen User (Guru)</h2>
            <a href="index.php?page=user&action=add" class="btn btn-primary btn-sm">Tambah Guru</a>
        </div>
        <div class="card-body" style="overflow-x: auto;">
            <?php
            $stmt = $pdo->query("SELECT id_user, username FROM users ORDER BY username ASC");
            $users = $stmt->fetchAll();
            ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_user']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><span class="badge badge-success">Guru</span></td>
                        <td>
                            <a href="index.php?page=user&action=edit&id=<?= $row['id_user'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </a>
                            <?php if($row['id_user'] != $_SESSION['user_id']): ?>
                            <a href="index.php?page=user&action=delete&id=<?= $row['id_user'] ?>" class="btn btn-sm btn-danger confirm-action" data-title="Hapus Guru?" data-text="Yakin hapus guru ini?" title="Hapus">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <?php
    $data = ['id_user'=>'', 'username'=>''];
    if ($action == 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT id_user, username FROM users WHERE id_user = ?");
        $stmt->execute([$_GET['id']]);
        $data = $stmt->fetch() ?: $data;
    }
    ?>
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <div class="card-header">
            <h2 class="card-title"><?= $action == 'add' ? 'Tambah' : 'Edit' ?> Guru</h2>
            <a href="index.php?page=user" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
        <div class="card-body">
            <?= $msg ?>
            <form action="index.php?page=user&action=<?= $action ?>" method="POST">
                <?php if($action == 'edit'): ?>
                <input type="hidden" name="id_user" value="<?= htmlspecialchars($data['id_user']) ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($data['username']) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password <?= $action == 'edit' ? '(Kosongkan jika tidak ingin diubah)' : '' ?></label>
                    <input type="password" name="password" <?= $action == 'add' ? 'required' : '' ?>>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= $action == 'add' ? 'Simpan Guru' : 'Update Guru' ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>
