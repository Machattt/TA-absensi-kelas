<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Laporan_Absensi_' . date('Y-m-d_H-i-s') . '.xls"');

$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : date('Y-m-d');
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : date('Y-m-d');

$query = "SELECT a.*, s.nama_lengkap, s.jenis_kelamin 
          FROM absensi a 
          JOIN siswa s ON a.nisn = s.nisn 
          WHERE DATE(a.waktu_scan) >= ? AND DATE(a.waktu_scan) <= ?
          ORDER BY a.waktu_scan ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$dari_tanggal, $sampai_tanggal]);
$absensi = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
    th { background-color: #1976D2; color: #FFFFFF; font-weight: bold; border: 1px solid #DDDDDD; padding: 10px; text-align: center; }
    td { border: 1px solid #DDDDDD; padding: 8px; color: #333333; }
    .text-center { text-align: center; }
    .noborder { border: none !important; }
</style>
</head>
<body>
    <table>


        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>ID Kartu</th>
            <th>Nama Siswa</th>
            <th>Jenis Kelamin</th>
            <th>Jam Masuk</th>
            <th>Jam Pulang</th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>
        <?php
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

            echo "<tr>";
            echo "<td class='text-center'>" . $no . "</td>";
            echo "<td class='text-center'>" . date('d/m/Y', strtotime($row['waktu_scan'])) . "</td>";
            echo "<td style=\"mso-number-format:'\@';\">" . htmlspecialchars($row['uid_rfid']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($row['jenis_kelamin']) . "</td>";
            echo "<td class='text-center'>" . $jam_masuk . "</td>";
            echo "<td class='text-center'>" . $jam_pulang . "</td>";
            echo "<td>" . $status_text . "</td>";
            echo "<td>" . htmlspecialchars($keterangan) . "</td>";
            echo "</tr>";

            $no++;
        }
        ?>
    </table>

</body>
</html>
