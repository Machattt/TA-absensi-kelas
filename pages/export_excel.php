<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : date('Y-m-d');
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : date('Y-m-d');

// Get attendance data
$query = "SELECT a.*, s.nama_lengkap, s.jenis_kelamin, k.nama_kelas 
          FROM absensi a 
          JOIN siswa s ON a.nisn = s.nisn 
          LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
          WHERE DATE(a.waktu_scan) >= ? AND DATE(a.waktu_scan) <= ?
          ORDER BY a.waktu_scan ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$dari_tanggal, $sampai_tanggal]);
$absensi = $stmt->fetchAll();

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Laporan_Absensi_' . $dari_tanggal . '_to_' . $sampai_tanggal . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Start output buffering to create Excel file
ob_start();

// Create Excel content dengan BOM untuk UTF-8 di Excel
echo "\xEF\xBB\xBF"; // UTF-8 BOM

// Title
echo "LAPORAN ABSENSI SISWA\n";
echo "Kelas 11 RPL 2\n";
echo "Periode: " . date('d/m/Y', strtotime($dari_tanggal)) . " s/d " . date('d/m/Y', strtotime($sampai_tanggal)) . "\n";
echo "Dicetak pada: " . date('d/m/Y H:i:s') . "\n";
echo "\n";

// Summary statistics
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

// Summary section
echo "RINGKASAN ABSENSI\n";
echo "Total Hadir\t" . $total_hadir . "\n";
echo "Total Terlambat\t" . $total_terlambat . "\n";
echo "Total Pulang\t" . $total_pulang . "\n";
echo "Total Alpa\t" . $total_alpa . "\n";
echo "Total Izin\t" . $total_izin . "\n";
echo "Total Sakit\t" . $total_sakit . "\n";
echo "\n";

// Table header
echo "No\tTanggal\tID Kartu\tNama Siswa\tJenis Kelamin\tKelas\tJam Masuk\tJam Pulang\tStatus\tKeterangan\n";

// Table data
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

    echo $no . "\t";
    echo date('d/m/Y', strtotime($row['waktu_scan'])) . "\t";
    echo htmlspecialchars($row['uid_rfid']) . "\t";
    echo htmlspecialchars($row['nama_lengkap']) . "\t";
    echo htmlspecialchars($row['jenis_kelamin']) . "\t";
    echo htmlspecialchars($row['nama_kelas'] ?? 'Tanpa Kelas') . "\t";
    echo $jam_masuk . "\t";
    echo $jam_pulang . "\t";
    echo $status_text . "\t";
    echo htmlspecialchars($keterangan) . "\n";

    $no++;
}

echo "\n";
echo "Sistem Informasi Absensi Siswa Kelas 11 RPL 2 - " . date('Y') . "\n";

// Output and exit
ob_end_flush();
exit();
?>
