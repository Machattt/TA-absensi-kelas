<?php
session_start();
require_once '../config/database.php';

// Kalau belum login, ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

header('Content-Type: text/html; charset=UTF-8');

// Ambil rentang tanggal dari URL, kalau nggak ada pakai hari ini
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : date('Y-m-d');
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : date('Y-m-d');

// Validasi format tanggal biar nggak ada error
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari_tanggal) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai_tanggal)) {
    die('Format tanggal tidak valid!');
}
if (strtotime($dari_tanggal) > strtotime($sampai_tanggal)) {
    die('Tanggal mulai harus sebelum tanggal selesai!');
}

// Ambil data absensi dari database
$query = "SELECT a.*, s.nama_lengkap, s.jenis_kelamin 
          FROM absensi a 
          JOIN siswa s ON a.nisn = s.nisn 
          WHERE DATE(a.waktu_scan) >= ? AND DATE(a.waktu_scan) <= ?
          ORDER BY a.waktu_scan ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$dari_tanggal, $sampai_tanggal]);
$absensi = $stmt->fetchAll();

// Hitung statistik buat ringkasan di PDF
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
        h1 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 10px;
            color: #1e64c8;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #1e64c8;
            padding-bottom: 15px;
        }
        .header p {
            margin: 5px 0;
            font-size: 13px;
            color: #333;
        }
        .summary {
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .summary-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            color: #1e64c8;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 13px;
            padding: 5px 0;
        }
        .summary-row span:first-child {
            font-weight: 500;
            color: #333;
        }
        .summary-row span:last-child {
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th {
            background-color: #1e64c8;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1e64c8;
        }
        td {
            padding: 10px;
            border: 1px solid #ddd;
            color: #333;
        }
        tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="container" id="pdf-content">
        <h1>LAPORAN ABSENSI SISWA KELAS 11 RPL 2</h1>
        
        <div class="header">
            <p><strong>Kelas 11 RPL 2</strong></p>
            <p>Periode: <?= date('d/m/Y', strtotime($dari_tanggal)) ?> s/d <?= date('d/m/Y', strtotime($sampai_tanggal)) ?></p>
            <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <div class="summary">
            <div class="summary-title">RINGKASAN ABSENSI</div>
            <div class="summary-row">
                <span>Total Hadir:</span>
                <span><?= $total_hadir ?> siswa</span>
            </div>
            <div class="summary-row">
                <span>Total Terlambat:</span>
                <span><?= $total_terlambat ?> siswa</span>
            </div>
            <div class="summary-row">
                <span>Total Pulang:</span>
                <span><?= $total_pulang ?> siswa</span>
            </div>
            <div class="summary-row">
                <span>Total Alpa:</span>
                <span><?= $total_alpa ?> siswa</span>
            </div>
            <div class="summary-row">
                <span>Total Izin:</span>
                <span><?= $total_izin ?> siswa</span>
            </div>
            <div class="summary-row">
                <span>Total Sakit:</span>
                <span><?= $total_sakit ?> siswa</span>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Siswa</th>
                    <th>Jenis Kelamin</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($absensi) > 0) {
                    $no = 1;
                    foreach ($absensi as $row) {
                        $jam_scan = date('H:i', strtotime($row['waktu_scan']));
                        $jam_masuk = $row['status'] === 'Pulang' ? '-' : $jam_scan;
                        $jam_pulang = $row['status'] === 'Pulang' ? $jam_scan : '-';
                        
                        $status_text = $row['status'];
                        if ($jam_scan <= '06:45' && $row['status'] === 'Hadir') {
                            $status_text = 'Hadir (Ontime)';
                        } elseif ($jam_scan > '06:45' && $row['status'] === 'Hadir') {
                            $status_text = 'Hadir (Terlambat)';
                        }
                        
                        $keterangan = isset($row['keterangan']) && $row['keterangan'] !== '' ? $row['keterangan'] : '-';
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= date('d/m/Y', strtotime($row['waktu_scan'])) ?></td>
                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                    <td><?= $jam_masuk ?></td>
                    <td><?= $jam_pulang ?></td>
                    <td><?= $status_text ?></td>
                    <td><?= htmlspecialchars($keterangan) ?></td>
                </tr>
                <?php
                        $no++;
                    }
                } else {
                ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #999;">Tidak ada data absensi untuk periode ini</td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Informasi Absensi Siswa Kelas 11 RPL 2 - <?= date('Y') ?></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        window.onload = function() {
            const element = document.getElementById('pdf-content');
            const opt = {
                margin:       [0.5, 0.5, 0.5, 0.5],
                filename:     'Laporan_Absensi_<?= htmlspecialchars($dari_tanggal) ?>_to_<?= htmlspecialchars($sampai_tanggal) ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save().then(function() {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage('pdf_done', '*');
                }
            });
        };
    </script>
</body>
</html>
