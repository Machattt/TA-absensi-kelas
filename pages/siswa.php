<?php
// Kalau belum login, lempar ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$msg = '';

// Pastikan kolom foto_path ada di tabel siswa biar nggak error
try {
    $pdo->query("SELECT foto_path FROM siswa LIMIT 1");
} catch (PDOException $e) {
    // Kalau error (berarti kolomnya belum ada), tambahin kolomnya otomatis
    $pdo->exec("ALTER TABLE siswa ADD COLUMN foto_path VARCHAR(255) DEFAULT NULL");
}
$search = trim($_GET['q'] ?? '');
$editNisn = trim($_GET['edit'] ?? '');
$openAddModal = isset($_GET['open']) && $_GET['open'] === 'add';
$editData = null;
$uploadsDir = realpath(__DIR__ . '/../uploads');

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['nisn'])) {
    $delNisn = $_GET['nisn'];
    // Hapus foto aslinya di folder kalau memang ada
    try {
        $stmtFoto = $pdo->prepare("SELECT foto_path FROM siswa WHERE nisn = ?");
        $stmtFoto->execute([$delNisn]);
        $fotoData = $stmtFoto->fetch();
        if ($fotoData && !empty($fotoData['foto_path']) && $fotoData['foto_path'] !== '-') {
            $pathFile = realpath(__DIR__ . '/../') . '/' . ltrim($fotoData['foto_path'], '/\\');
            if (file_exists($pathFile)) {
                @unlink($pathFile);
            }
        }
    } catch (PDOException $e) { }

    $stmt = $pdo->prepare("DELETE FROM siswa WHERE nisn = ?");
    if ($stmt->execute([$delNisn])) {
        $msg = "<div class='alert alert-success'>Data siswa berhasil dihapus.</div>";
    } else {
        $msg = "<div class='alert alert-error'>Data siswa gagal dihapus.</div>";
    }
}

// Proses Form Tambah/Edit Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';
    $nisn = trim($_POST['nisn'] ?? '');
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? '';
    $uid = $nisn; // Penyamaan data: label NISN berisi UID kartu

    if ($nisn === '' || $nama === '' || ($jk !== 'L' && $jk !== 'P')) {
        $msg = "<div class='alert alert-error'>Mohon lengkapi data siswa dengan benar.</div>";
    } else {
        try {
            // Ensure folder uploads/master exists
            $masterDir = __DIR__ . '/../uploads/master';
            if (!is_dir($masterDir)) {
                @mkdir($masterDir, 0755, true);
            }

            $fotoMasterPath = null;
            if (isset($_POST['foto_base64']) && !empty($_POST['foto_base64'])) {
                $base64_string = $_POST['foto_base64'];
                $data = explode(',', $base64_string);
                if (count($data) == 2) {
                    $decoded = base64_decode($data[1], true);
                    if ($decoded === false) {
                        throw new RuntimeException('Base64 decode gagal.');
                    }
                    // Validasi bahwa ini adalah image yang valid
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($decoded);
                    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($mime, $allowed)) {
                        throw new RuntimeException('Format foto tidak didukung. Gunakan JPG/PNG/WebP.');
                    }
                    $safeId = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nisn);
                    $fileName = $safeId . '_' . time() . '.jpg';
                    $destPath = realpath(__DIR__ . '/../uploads') . '/' . $fileName;
                    if (file_put_contents($destPath, $decoded) === false) {
                        throw new RuntimeException('Gagal menyimpan foto Base64.');
                    }
                    $fotoMasterPath = 'uploads/' . $fileName;
                }
            } elseif (isset($_FILES['foto_master']) && is_array($_FILES['foto_master']) && $_FILES['foto_master']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['foto_master']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Upload foto gagal.');
                }

                $maxBytes = 2 * 1024 * 1024; // 2MB
                if ((int) $_FILES['foto_master']['size'] > $maxBytes) {
                    throw new RuntimeException('Ukuran foto terlalu besar (maks 2MB).');
                }

                $tmpName = $_FILES['foto_master']['tmp_name'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpName);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                if (!isset($allowed[$mime])) {
                    throw new RuntimeException('Format foto tidak didukung. Gunakan JPG/PNG/WebP.');
                }

                $ext = $allowed[$mime];
                $safeId = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nisn);
                $fileName = $safeId . '_' . time() . '.' . $ext;
                $destPath = $masterDir . '/' . $fileName;
                if (!move_uploaded_file($tmpName, $destPath)) {
                    throw new RuntimeException('Gagal menyimpan file foto.');
                }

                $fotoMasterPath = 'uploads/master/' . $fileName;
            }

            if ($formAction === 'add') {
                // Simpan foto master jika kolom tersedia
                $hasFotoMaster = false;
                try {
                    $cek = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'foto_path'");
                    $hasFotoMaster = $cek->rowCount() > 0;
                } catch (PDOException $e) {
                    $hasFotoMaster = false;
                }

                if ($hasFotoMaster) {
                    $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama_lengkap, uid_rfid, jenis_kelamin, foto_path) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nisn, $nama, $uid, $jk, $fotoMasterPath]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama_lengkap, uid_rfid, jenis_kelamin) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nisn, $nama, $uid, $jk]);
                }
                $msg = "<div class='alert alert-success'>Data siswa berhasil ditambahkan.</div>";
            } elseif ($formAction === 'edit') {
                $oldNisn = trim($_POST['old_nisn'] ?? '');
                $hasFotoMaster = false;
                try {
                    $cek = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'foto_path'");
                    $hasFotoMaster = $cek->rowCount() > 0;
                } catch (PDOException $e) {
                    $hasFotoMaster = false;
                }

                if ($hasFotoMaster && $fotoMasterPath !== null) {
                    // Hapus foto lama sebelum ganti yang baru
                    try {
                        $stmtOldFoto = $pdo->prepare("SELECT foto_path FROM siswa WHERE nisn = ?");
                        $stmtOldFoto->execute([$oldNisn]);
                        $oldFotoData = $stmtOldFoto->fetch();
                        if ($oldFotoData && !empty($oldFotoData['foto_path']) && $oldFotoData['foto_path'] !== '-') {
                            $oldPathFile = realpath(__DIR__ . '/../') . '/' . ltrim($oldFotoData['foto_path'], '/\\');
                            if (file_exists($oldPathFile) && $oldFotoData['foto_path'] !== $fotoMasterPath) {
                                @unlink($oldPathFile);
                            }
                        }
                    } catch (PDOException $e) { }

                    $stmt = $pdo->prepare("UPDATE siswa SET nisn = ?, nama_lengkap = ?, uid_rfid = ?, jenis_kelamin = ?, foto_path = ? WHERE nisn = ?");
                    $stmt->execute([$nisn, $nama, $uid, $jk, $fotoMasterPath, $oldNisn]);
                } else {
                    $stmt = $pdo->prepare("UPDATE siswa SET nisn = ?, nama_lengkap = ?, uid_rfid = ?, jenis_kelamin = ? WHERE nisn = ?");
                    $stmt->execute([$nisn, $nama, $uid, $jk, $oldNisn]);
                }
                $msg = "<div class='alert alert-success'>Data siswa berhasil diperbarui.</div>";
            }
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-error'>Gagal menyimpan data. NISN atau UID kemungkinan sudah terdaftar.</div>";
        } catch (RuntimeException $e) {
            $msg = "<div class='alert alert-error'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if ($editNisn !== '') {
    $stmtEdit = $pdo->prepare("SELECT nisn, nama_lengkap, jenis_kelamin, foto_path FROM siswa WHERE nisn = ? LIMIT 1");
    $stmtEdit->execute([$editNisn]);
    $editData = $stmtEdit->fetch();
}

// Ambil data siswa dari database buat ditampilin
$query = "SELECT nisn, nama_lengkap, jenis_kelamin FROM siswa WHERE 1=1";
$params = [];
if ($search !== '') {
    $query .= " AND (nama_lengkap LIKE ? OR nisn LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}
$query .= " ORDER BY nama_lengkap ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$siswa = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Manajemen Data Siswa 11 RPL 2</h2>
        <button type="button" class="btn btn-primary" id="open-add-modal-btn">Tambah Siswa</button>
    </div>
    <div class="card-body">
        <?= $msg ?>

        <form action="index.php" method="GET" style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="page" value="siswa">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama atau NISN..." style="flex: 1; min-width: 220px;">
            <button type="submit" class="btn btn-secondary">Cari</button>
            <?php if ($search !== ''): ?>
                <a href="index.php?page=siswa" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NISN</th>
                        <th>Nama Lengkap</th>
                        <th>L/P</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($siswa) > 0): ?>
                        <?php foreach ($siswa as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($row['nisn']) ?></td>
                                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td><?= $row['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
                                <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="index.php?page=siswa&edit=<?= urlencode($row['nisn']) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-sm btn-secondary" title="Edit">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                    <a href="index.php?page=siswa&action=delete&nisn=<?= urlencode($row['nisn']) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-sm btn-danger confirm-action" data-title="Hapus Siswa?" data-text="Yakin hapus data siswa ini?" title="Hapus">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted);">Data siswa belum tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-overlay" class="siswa-modal-overlay"></div>

<div id="add-modal" class="siswa-modal">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tambah Siswa 11 RPL 2</h3>
            <button type="button" class="btn btn-secondary btn-sm close-modal-btn">Tutup</button>
        </div>
        <div class="card-body">
            <form action="index.php?page=siswa<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="add">
                
                <div class="modal-grid">
                    <!-- Kiri: Input Data -->
                    <div>
                        <div class="form-group">
                            <label>NISN</label>
                            <input type="text" name="nisn" id="add-nisn-input" placeholder="Tap kartu RFID di sini..." required>
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" required>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" required>
                                <option value="L">L</option>
                                <option value="P">P</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Kanan: Webcam -->
                    <div>
                        <div class="form-group">
                            <label>Ambil Foto Wajah</label>
                            <div id="webcam-container" style="position: relative; width: 100%; aspect-ratio: 4/3; background: #000; border-radius: var(--radius); overflow: hidden; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                <video id="siswa-webcam" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                                <canvas id="siswa-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10;"></canvas>
                                <img id="siswa-preview" style="display: none; width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; z-index: 20; transform: scaleX(-1);" />
                                <div id="webcam-loading" style="position: absolute; color: white; font-weight: bold; z-index: 5;">Memuat Kamera...</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" id="btn-ambil-foto" class="btn btn-secondary btn-sm" style="flex: 1;" disabled>Ambil Foto</button>
                                <button type="button" id="btn-foto-ulang" class="btn btn-secondary btn-sm" style="flex: 1; display: none;">Foto Ulang</button>
                            </div>
                            <input type="hidden" name="foto_base64" id="foto-base64">
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <button type="submit" id="btn-simpan-siswa" class="btn btn-primary" disabled>Simpan Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editData): ?>
<div id="edit-modal" class="siswa-modal">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Siswa 11 RPL 2</h3>
            <a href="index.php?page=siswa<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-secondary btn-sm">Tutup</a>
        </div>
        <div class="card-body">
            <form action="index.php?page=siswa<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="old_nisn" value="<?= htmlspecialchars($editData['nisn']) ?>">
                
                <div class="modal-grid">
                    <!-- Kiri: Input Data -->
                    <div>
                        <div class="form-group">
                            <label>NISN</label>
                            <input type="text" name="nisn" id="edit-nisn-input" value="<?= htmlspecialchars($editData['nisn']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($editData['nama_lengkap']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" required>
                                <option value="L" <?= $editData['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>L</option>
                                <option value="P" <?= $editData['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>P</option>
                            </select>
                        </div>
                        <?php if (!empty($editData['foto_path'])): ?>
                        <div class="form-group">
                            <label>Foto Saat Ini</label>
                            <div style="margin-top: 0.25rem;">
                                <a href="<?= htmlspecialchars($editData['foto_path']) ?>" target="_blank" style="text-decoration: none;">
                                    <img src="<?= htmlspecialchars($editData['foto_path']) ?>" alt="Foto Terdaftar" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border);">
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Kanan: Webcam -->
                    <div>
                        <div class="form-group">
                            <label>Ganti Foto Wajah (Webcam)</label>
                            <div id="edit-webcam-container" style="position: relative; width: 100%; aspect-ratio: 4/3; background: #000; border-radius: var(--radius); overflow: hidden; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                <video id="edit-siswa-webcam" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                                <canvas id="edit-siswa-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10;"></canvas>
                                <img id="edit-siswa-preview" style="display: none; width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; z-index: 20; transform: scaleX(-1);" />
                                <div id="edit-webcam-loading" style="position: absolute; color: white; font-weight: bold; z-index: 5;">Memuat Kamera...</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" id="edit-btn-ambil-foto" class="btn btn-secondary btn-sm" style="flex: 1;" disabled>Ambil Foto Baru</button>
                                <button type="button" id="edit-btn-foto-ulang" class="btn btn-secondary btn-sm" style="flex: 1; display: none;">Foto Ulang</button>
                            </div>
                            <div style="margin-top: 0.4rem; font-size: 0.85rem; color: var(--text-muted);">
                                Kosongkan (jangan ambil foto) jika tidak ingin mengganti foto.
                            </div>
                            <input type="hidden" name="foto_base64" id="edit-foto-base64">
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <button type="submit" id="edit-btn-simpan-siswa" class="btn btn-primary">Update Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .siswa-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        z-index: 1000;
    }
    .siswa-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: min(95vw, 800px);
        z-index: 1001;
    }
    .siswa-modal.show,
    .siswa-modal-overlay.show {
        display: block;
    }
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    @media (max-width: 640px) {
        .modal-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    (function() {
        const overlay = document.getElementById('modal-overlay');
        const addModal = document.getElementById('add-modal');
        const editModal = document.getElementById('edit-modal');
        const openAddBtn = document.getElementById('open-add-modal-btn');
        const closeButtons = document.querySelectorAll('.close-modal-btn');
        const addNisnInput = document.getElementById('add-nisn-input');
        const editNisnInput = document.getElementById('edit-nisn-input');
        let activeScanInput = null;
        let rfidStr = '';
        let rfidTimer = null;

        let stream = null;
        let faceDetectionInterval = null;
        const video = document.getElementById('siswa-webcam');
        const canvas = document.getElementById('siswa-canvas');
        const preview = document.getElementById('siswa-preview');
        const btnAmbil = document.getElementById('btn-ambil-foto');
        const btnUlang = document.getElementById('btn-foto-ulang');
        const base64Input = document.getElementById('foto-base64');
        const btnSimpan = document.getElementById('btn-simpan-siswa');
        const webcamLoading = document.getElementById('webcam-loading');

        const editVideo = document.getElementById('edit-siswa-webcam');
        const editCanvas = document.getElementById('edit-siswa-canvas');
        const editPreview = document.getElementById('edit-siswa-preview');
        const editBtnAmbil = document.getElementById('edit-btn-ambil-foto');
        const editBtnUlang = document.getElementById('edit-btn-foto-ulang');
        const editBase64Input = document.getElementById('edit-foto-base64');
        const editBtnSimpan = document.getElementById('edit-btn-simpan-siswa');
        const editWebcamLoading = document.getElementById('edit-webcam-loading');

        async function initCamera(vidEl, canEl, loadEl, btnAmbilEl) {
            if (!vidEl) return;
            try {
                loadEl.innerText = "Memuat Kamera...";
                loadEl.style.display = 'block';
                
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error("Browser tidak mendukung akses kamera (Gunakan Localhost/HTTPS)");
                }
                
                stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                vidEl.srcObject = stream;
                
                vidEl.onplay = () => {
                    loadEl.style.display = 'none';
                    btnAmbilEl.disabled = false;
                };
            } catch (err) {
                console.error("Camera access error: ", err);
                loadEl.innerText = "Error: " + err.message;
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        }

        function handleCapture(vidEl, canEl, prevEl, base64El, btnAmbilEl, btnUlangEl, btnSimpanEl) {
            const captureCanvas = document.createElement('canvas');
            captureCanvas.width = vidEl.videoWidth;
            captureCanvas.height = vidEl.videoHeight;
            const captureCtx = captureCanvas.getContext('2d');
            captureCtx.drawImage(vidEl, 0, 0, captureCanvas.width, captureCanvas.height);
            
            const dataUrl = captureCanvas.toDataURL('image/jpeg', 0.9);
            base64El.value = dataUrl;
            
            prevEl.src = dataUrl;
            prevEl.style.display = 'block';
            vidEl.style.display = 'none';
            canEl.style.display = 'none';
            
            btnAmbilEl.style.display = 'none';
            btnUlangEl.style.display = 'block';
            if(btnSimpanEl) btnSimpanEl.disabled = false;
            
            stopCamera();
        }

        function handleRetake(vidEl, canEl, prevEl, base64El, btnAmbilEl, btnUlangEl, btnSimpanEl, loadEl) {
            prevEl.style.display = 'none';
            vidEl.style.display = 'block';
            canEl.style.display = 'block';
            base64El.value = '';
            
            btnAmbilEl.style.display = 'block';
            btnUlangEl.style.display = 'none';
            // Disable simpan on 'add', but maybe leave it on 'edit' if we allow editing without changing photo
            if(btnSimpanEl && btnSimpanEl.id === 'btn-simpan-siswa') btnSimpanEl.disabled = true;
            
            initCamera(vidEl, canEl, loadEl, btnAmbilEl);
        }

        if (btnAmbil) {
            btnAmbil.addEventListener('click', function() {
                handleCapture(video, canvas, preview, base64Input, btnAmbil, btnUlang, btnSimpan);
            });
        }
        if (btnUlang) {
            btnUlang.addEventListener('click', function() {
                handleRetake(video, canvas, preview, base64Input, btnAmbil, btnUlang, btnSimpan, webcamLoading);
            });
        }

        if (editBtnAmbil) {
            editBtnAmbil.addEventListener('click', function() {
                handleCapture(editVideo, editCanvas, editPreview, editBase64Input, editBtnAmbil, editBtnUlang, editBtnSimpan);
            });
        }
        if (editBtnUlang) {
            editBtnUlang.addEventListener('click', function() {
                handleRetake(editVideo, editCanvas, editPreview, editBase64Input, editBtnAmbil, editBtnUlang, editBtnSimpan, editWebcamLoading);
            });
        }

        function openModal(modalElement, focusInput) {
            if (!modalElement) return;
            overlay.classList.add('show');
            modalElement.classList.add('show');
            activeScanInput = focusInput || null;
            if (focusInput) {
                setTimeout(() => focusInput.focus(), 80);
            }
            
            if (modalElement === addModal) {
                if (btnSimpan) btnSimpan.disabled = true;
                if (btnAmbil) btnAmbil.style.display = 'block';
                if (btnUlang) btnUlang.style.display = 'none';
                if (preview) preview.style.display = 'none';
                if (video) video.style.display = 'block';
                if (canvas) canvas.style.display = 'block';
                if (base64Input) base64Input.value = '';
                initCamera(video, canvas, webcamLoading, btnAmbil);
            }
            
            if (modalElement === editModal) {
                if (editBtnAmbil) editBtnAmbil.style.display = 'block';
                if (editBtnUlang) editBtnUlang.style.display = 'none';
                if (editPreview) editPreview.style.display = 'none';
                if (editVideo) editVideo.style.display = 'block';
                if (editCanvas) editCanvas.style.display = 'block';
                if (editBase64Input) editBase64Input.value = '';
                initCamera(editVideo, editCanvas, editWebcamLoading, editBtnAmbil);
            }
        }

        function closeAddModal() {
            overlay.classList.remove('show');
            addModal.classList.remove('show');
            activeScanInput = null;
            stopCamera();
        }

        if (openAddBtn) {
            openAddBtn.addEventListener('click', function() {
                openModal(addModal, addNisnInput);
            });
        }

        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', closeAddModal);
        });

        overlay.addEventListener('click', function() {
            closeAddModal();
            if (editModal) {
                window.location.href = "index.php?page=siswa<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>";
            }
        });

        <?php if ($openAddModal): ?>
            openModal(addModal, addNisnInput);
        <?php endif; ?>

        <?php if ($editData): ?>
            openModal(editModal, editNisnInput);
        <?php endif; ?>

        document.addEventListener('keydown', function(e) {
            if (!activeScanInput) return;
            if (e.key.length !== 1 && e.key !== 'Enter') return;

            clearTimeout(rfidTimer);
            rfidTimer = setTimeout(function() {
                rfidStr = '';
            }, 70);

            if (e.key === 'Enter') {
                if (rfidStr.length > 5) {
                    e.preventDefault();
                    activeScanInput.value = rfidStr;
                    activeScanInput.style.borderColor = "var(--success)";
                    activeScanInput.style.boxShadow = "0 0 0 3px rgba(16, 185, 129, 0.2)";
                }
                rfidStr = '';
            } else {
                rfidStr += e.key;
            }
        });
    })();
</script>
