<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : date('Y-m-d');
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : date('Y-m-d');
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '1'; // Default ke ID 1 (11 RPL 2)

// Get all classes for dropdown
$stmtKelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelasList = $stmtKelas->fetchAll();

// Build Query
$query = "SELECT a.*, s.nama_lengkap, s.jenis_kelamin, k.nama_kelas 
          FROM absensi a 
          JOIN siswa s ON a.nisn = s.nisn 
          LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
          WHERE DATE(a.waktu_scan) >= ? AND DATE(a.waktu_scan) <= ?";
$params = [$dari_tanggal, $sampai_tanggal];

if ($id_kelas !== '') {
    $query .= " AND s.id_kelas = ?";
    $params[] = $id_kelas;
}

$query .= " ORDER BY a.waktu_scan ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

// Calculate statistics
$total_hadir = 0;
$total_pulang = 0;
$total_alpa = 0;
$total_izin = 0;
$total_sakit = 0;
$total_terlambat = 0;

foreach ($absensi as $row) {
    if ($row['status'] === 'Hadir') {
        $total_hadir++;
        $jam_scan = date('H:i', strtotime($row['waktu_scan']));
        if ($jam_scan > '06:45') $total_terlambat++;
    }
    elseif ($row['status'] === 'Pulang') $total_pulang++;
    elseif ($row['status'] === 'Alpa') $total_alpa++;
    elseif (strpos($row['status'], 'Izin') !== false) $total_izin++;
    elseif (strpos($row['status'], 'Sakit') !== false) $total_sakit++;
}

// Determine class name for filter display
$nama_kelas_filter = 'Semua Kelas';
if ($id_kelas !== '') {
    foreach ($kelasList as $k) {
        if ($k['id_kelas'] == $id_kelas) {
            $nama_kelas_filter = $k['nama_kelas'];
            break;
        }
    }
}
?>

<div class="laporan-header-container">
    <h2 class="laporan-title">Laporan Absensi</h2>
</div>

<!-- Filter Card (Hidden when printing) -->
<div class="filter-card">
    <form action="index.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem; width: 100%; align-items: flex-end;">
        <input type="hidden" name="page" value="laporan">
        
        <div class="filter-group">
            <label>Dari Tanggal</label>
            <input type="date" name="dari_tanggal" value="<?= htmlspecialchars($dari_tanggal) ?>">
        </div>
        
        <div class="filter-group">
            <label>Sampai Tanggal</label>
            <input type="date" name="sampai_tanggal" value="<?= htmlspecialchars($sampai_tanggal) ?>">
        </div>
        
        <input type="hidden" name="id_kelas" value="1">
        
        <button type="submit" class="btn btn-primary btn-search">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Cari Data
        </button>

        <button type="button" onclick="downloadPDF()" class="btn btn-pdf">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            Export PDF
        </button>

        <button type="button" onclick="downloadExcel()" class="btn btn-success">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
            Export Excel
        </button>
    </form>
</div>

<!-- Table Card (Printable Area) -->
<div class="card print-area" id="pdf-content" style="background: white; padding: 20px;">
    <!-- Print Specific Header -->
    <div class="print-header" style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 15px;">
        <h1 style="color: var(--primary); font-size: 20px; margin-bottom: 5px;">LAPORAN ABSENSI <?= htmlspecialchars(strtoupper($nama_kelas_filter)) ?></h1>
        <p style="margin: 2px 0; font-size: 13px;">Periode: <?= date('d/m/Y', strtotime($dari_tanggal)) ?> s/d <?= date('d/m/Y', strtotime($sampai_tanggal)) ?></p>
        <p style="margin: 2px 0; font-size: 13px;">Dicetak pada: <?= date('d/m/Y, H:i:s') ?></p>
    </div>

    <div class="card-body" style="overflow-x: auto; padding: 0;">
        <table class="table-clean" style="width: 100%; text-align: left; border-collapse: collapse; font-size: 12px;">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>ID Kartu</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Foto Bukti</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($absensi as $row): ?>
                <?php
                    // Hitung Ontime/Terlambat
                    $jam_scan = date('H:i:s', strtotime($row['waktu_scan']));
                    $jam_masuk = $row['status'] === 'Pulang' ? '-' : $jam_scan;
                    $jam_pulang = $row['status'] === 'Pulang' ? $jam_scan : '-';
                    // Batas masuk 06:45:00 sesuai instruksi
                    $is_ontime = $jam_scan <= '06:45:00';
                    $status_class = $is_ontime ? 'status-ontime' : 'status-terlambat';
                    $status_text = $is_ontime ? 'Ontime' : 'Terlambat';
                    $keterangan = isset($row['keterangan']) && $row['keterangan'] !== '' ? $row['keterangan'] : '-';
                    
                    // Tampilkan status asli untuk data manual/izin/sakit/pulang.
                    if ($row['status'] === 'Pulang') {
                        $status_text = 'Pulang';
                        $status_class = 'status-ontime';
                    } elseif (strpos($row['status'], '(Manual)') !== false || strpos($row['status'], 'Izin') === 0 || strpos($row['status'], 'Sakit') === 0) {
                        $status_text = $row['status'];
                        $status_class = 'status-ontime'; // tetap hijau
                    }
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($row['waktu_scan'])) ?></td>
                    <td><?= htmlspecialchars($row['uid_rfid']) ?></td>
                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kelas'] ?? 'Tanpa Kelas') ?></td>
                    <td>
                        <?php if(!empty($row['foto_path']) && file_exists($row['foto_path']) && $row['foto_path'] != '-'): ?>
                            <a href="#" onclick="showPhotoPopup('<?= htmlspecialchars($row['foto_path']) ?>', '<?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>'); return false;">
                                <img src="<?= htmlspecialchars($row['foto_path']) ?>" alt="Foto" class="photo-thumb" style="width:40px; height:40px; border-radius: 4px; object-fit: cover;">
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= $jam_masuk ?></td>
                    <td><?= $jam_pulang ?></td>
                    <td class="<?= $status_class ?>"><?= $status_text ?></td>
                    <td><?= htmlspecialchars($keterangan) ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($absensi) == 0): ?>
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                        Tidak ada data absensi yang ditemukan sesuai filter.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    Swal.fire({
        title: 'Menyiapkan PDF',
        text: 'Mohon tunggu sebentar...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.left = '-9999px';
    iframe.style.top = '-9999px';
    iframe.style.width = '1200px';
    iframe.style.height = '1200px';
    iframe.style.border = 'none';
    iframe.src = "pages/export_pdf.php?dari_tanggal=<?= urlencode($dari_tanggal) ?>&sampai_tanggal=<?= urlencode($sampai_tanggal) ?>";
    document.body.appendChild(iframe);

    // Menunggu pesan dari export_pdf.php
    window.addEventListener('message', function handlePdfDone(e) {
        if (e.data === 'pdf_done') {
            window.removeEventListener('message', handlePdfDone);
            document.body.removeChild(iframe);
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'File PDF berhasil diunduh.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });

    // Fallback jika iframe gagal diload atau proses error
    setTimeout(() => {
        if (document.body.contains(iframe)) {
            document.body.removeChild(iframe);
            Swal.close();
        }
    }, 15000); // Timeout 15 detik
}

function downloadExcel() {
    Swal.fire({
        title: 'Menyiapkan Excel',
        text: 'Mohon tunggu sebentar...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const url = "pages/export_excel.php?dari_tanggal=<?= urlencode($dari_tanggal) ?>&sampai_tanggal=<?= urlencode($sampai_tanggal) ?>";
    
    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error("Gagal mengunduh file");
            return response.blob();
        })
        .then(blob => {
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = downloadUrl;
            a.download = 'Laporan_Absensi_<?= htmlspecialchars($dari_tanggal) ?>_to_<?= htmlspecialchars($sampai_tanggal) ?>.xls';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);
            
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'File Excel berhasil diunduh.',
                timer: 1500,
                showConfirmButton: false
            });
        })
        .catch(err => {
            console.error(err);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat membuat Excel.'
            });
        });
}

function showPhotoPopup(imageUrl, studentName) {
    Swal.fire({
        title: 'Foto Bukti',
        text: studentName,
        imageUrl: imageUrl,
        imageAlt: 'Foto Bukti ' + studentName,
        imageHeight: 300,
        confirmButtonText: 'Tutup',
        customClass: {
            image: 'rounded-lg object-cover'
        }
    });
}
</script>
