<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Cek sesi login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uid = trim($_POST['uid']);
    $photo_base64 = $_POST['photo'];

    // Data dari frontend harus lengkap (UID kartu sama foto wajah)
    if (empty($uid) || empty($photo_base64)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit();
    }

    try {
        // Pastikan kolom keterangan udah dibikin di database
        $cekKolom = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'keterangan'");
        if ($cekKolom->rowCount() === 0) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN keterangan VARCHAR(255) NULL AFTER status");
        }

        $placeholderUrl = 'assets/img/placeholder.png';
        $masterPhotoUrl = $placeholderUrl;

        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $today = $now->format('Y-m-d');
        $timeNow = $now->format('H:i:s');
        $dayOfWeek = (int) $now->format('N'); 
        $jamMasukMulai = '06:45:00';
        $jamPulangMulai = '15:00:00';

        // 1. Cek Hari Libur Nasional (Kalau libur ya nggak usah absen)
        $stmtLibur = $pdo->prepare("SELECT keterangan FROM hari_libur WHERE tanggal = ?");
        $stmtLibur->execute([$today]);
        if ($libur = $stmtLibur->fetch()) {
            echo json_encode([
                'status' => 'error',
                'message' => "Hari ini libur: " . htmlspecialchars($libur['keterangan']) . ". Scan ditolak."
            ]);
            exit();
        }

        // 2. Cek Jadwal Dinamis & Libur Mingguan (Misal sabtu minggu)
        $stmtJadwal = $pdo->prepare("SELECT jam_masuk, jam_pulang, is_libur FROM jadwal_operasional WHERE hari = ?");
        $stmtJadwal->execute([$dayOfWeek]);
        if ($jadwal = $stmtJadwal->fetch()) {
            if ($jadwal['is_libur'] == 1) {
                echo json_encode([
                    'status' => 'error',
                    'message' => "Hari ini dijadwalkan sebagai hari libur. Scan ditolak."
                ]);
                exit();
            }
            $jamMasukMulai = $jadwal['jam_masuk'];
            $jamPulangMulai = $jadwal['jam_pulang'];
        }

        // Cek apakah UID terdaftar + ambil foto master kalau kolomnya ada
        $hasFotoMaster = false;
        try {
            $cekFotoMaster = $pdo->query("SHOW COLUMNS FROM siswa LIKE 'foto_path'");
            $hasFotoMaster = $cekFotoMaster->rowCount() > 0;
        } catch (PDOException $e) {
            $hasFotoMaster = false;
        }

        $selectFoto = $hasFotoMaster ? ", s.foto_path AS foto_master_path" : ", NULL AS foto_master_path";
        $stmt = $pdo->prepare("SELECT s.*{$selectFoto} FROM siswa s WHERE s.uid_rfid = ?");
        $stmt->execute([$uid]);
        $siswa = $stmt->fetch();

        if ($siswa) {
            $nisn = $siswa['nisn'];

            // Resolve foto master URL dengan fallback placeholder jika file tidak ada
            $fotoMasterPath = isset($siswa['foto_master_path']) ? trim((string) $siswa['foto_master_path']) : '';
            if ($fotoMasterPath !== '' && $fotoMasterPath !== '-') {
                $fileSystemPath = '../' . ltrim($fotoMasterPath, '/\\');
                if (file_exists($fileSystemPath)) {
                    $masterPhotoUrl = ltrim($fotoMasterPath, '/\\');
                } else {
                    $masterPhotoUrl = $placeholderUrl;
                }
            } else {
                $masterPhotoUrl = $placeholderUrl;
            }

            // Cek apakah udah absen dalam 5 detik terakhir buat mencegah dobel scan (diturunkan untuk mempermudah demo video)
            $stmt = $pdo->prepare("SELECT id_absensi FROM absensi 
                                   WHERE nisn = ? AND waktu_scan > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
            $stmt->execute([$nisn]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Sudah absen baru-baru ini. Tunggu beberapa saat.'
                ]);
                exit();
            }

            // Cek riwayat absen hari ini, biar ketahuan ini absen masuk atau pulang
            $stmt = $pdo->prepare("SELECT status, waktu_scan
                                   FROM absensi
                                   WHERE nisn = ? AND DATE(waktu_scan) = ?
                                   ORDER BY waktu_scan ASC");
            $stmt->execute([$nisn, $today]);
            $scanHariIni = $stmt->fetchAll();
            $sudahScanMasuk = count($scanHariIni) > 0;

            if (!$sudahScanMasuk && $timeNow < $jamMasukMulai) {
                echo json_encode([
                    'status' => 'error',
                    'message' => "Absensi masuk dibuka mulai {$jamMasukMulai} WIB.",
                    'code' => 'TOO_EARLY_CHECKIN'
                ]);
                exit();
            }

            if ($sudahScanMasuk) {
                $sudahPulang = false;
                foreach ($scanHariIni as $riwayat) {
                    if ($riwayat['status'] === 'Pulang') {
                        $sudahPulang = true;
                        break;
                    }
                }

                if ($sudahPulang) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Siswa ini sudah tercatat pulang hari ini.',
                        'code' => 'ALREADY_CHECKED_OUT'
                    ]);
                    exit();
                }

                if ($timeNow < $jamPulangMulai) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => "Belum jam pulang. Jadwal pulang hari ini mulai {$jamPulangMulai} WIB.",
                        'code' => 'TOO_EARLY_CHECKOUT'
                    ]);
                    exit();
                }
            }

            // Proses fotonya (dari base64 jadi file gambar beneran)
            $image_parts = explode(";base64,", $photo_base64);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            
            $file_name = $nisn . '_' . time() . '.jpg';
            $file_path = '../uploads/' . $file_name;
            $db_path = 'uploads/' . $file_name;

            if (file_put_contents($file_path, $image_base64)) {
                $statusAbsensi = $sudahScanMasuk ? 'Pulang' : 'Hadir';

                // Simpan ke database
                $stmt = $pdo->prepare("INSERT INTO absensi (nisn, uid_rfid, status, foto_path, keterangan) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nisn, $uid, $statusAbsensi, $db_path, '-']);


                echo json_encode([
                    'status' => 'success',
                    'message' => $statusAbsensi === 'Pulang' ? 'Scan pulang berhasil' : 'Scan masuk berhasil',
                    'siswa' => [
                        'nisn' => $siswa['nisn'],
                        'nama_lengkap' => $siswa['nama_lengkap'],
                        'nama_kelas' => '11 RPL 2',
                        'foto_master_url' => $masterPhotoUrl
                    ],
                    'attendance_status' => $statusAbsensi
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan foto']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kartu tidak terdaftar']);
        }
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
