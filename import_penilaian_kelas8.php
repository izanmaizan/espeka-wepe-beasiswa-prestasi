<?php
// File: import_penilaian_kelas8.php
// Letakkan di folder root atau admin/tools/

$page_title = 'Import Data Penilaian Kelas VIII';
require_once './includes/header.php';
requireRole('admin');

// Data penilaian kelas VIII dari Excel
$data_kelas8 = [
    ['AHMAD MUZAQQIR', 'VIII.1', 78, 'B', 1, 'B', 'B'],
    ['ANDRE WILMAN', 'VIII.1', 77, 'B', 1, 'B', 'B'],
    ['ANGGA PRATAMA', 'VIII.1', 85, 'C', 1, 'B', 'B'],
    ['ANISSA AGUSWITA', 'VIII.1', 86, 'B', 0, 'C', 'B'],
    ['ARYA WIGUNA', 'VIII.1', 82, 'B', 0, 'C', 'B'],
    ['ASNAH', 'VIII.1', 80, 'B', 0, 'B', 'C'],
    ['ASYIFA RAMADHANI', 'VIII.1', 83, 'C', 1, 'B', 'B'],
    ['BINTANG RIZKI ANUGRAH', 'VIII.1', 76, 'C', 2, 'B', 'C'],
    ['ETIN PUTRI', 'VIII.1', 77, 'B', 2, 'B', 'C'],
    ['FARIS NAUVAL IRANDRA', 'VIII.1', 75, 'B', 3, 'SB', 'B'],
    ['GILANG MAULANA MARTA', 'VIII.1', 89, 'B', 0, 'SB', 'B'],
    ['HAMID ALBAR', 'VIII.1', 91, 'SB', 0, 'SB', 'SB'],
    ['IQRAM RAMADAN', 'VIII.1', 93, 'SB', 1, 'SB', 'SB'],
    ['KEYRIN JUNIANSSYAH USWELA', 'VIII.1', 94, 'SB', 0, 'B', 'SB'],
    ['M. HERU FEBRIANO', 'VIII.1', 85, 'B', 1, 'B', 'B'],
    ['M. USMAN AL GANY', 'VIII.1', 86, 'B', 2, 'B', 'B'],
    ['MUHAMMAD ARIF FAUZAN', 'VIII.1', 78, 'B', 1, 'B', 'B'],
    ['MUHAMMAD FADHIL', 'VIII.1', 85, 'B', 1, 'B', 'B'],
    ['MUHAMMAD RIDHO ALKHARNI', 'VIII.1', 83, 'B', 1, 'C', 'B'],
    ['MUHAMMAD SYUKRI', 'VIII.1', 89, 'C', 2, 'C', 'B'],
    ['MUTIARA HANIFA', 'VIII.1', 79, 'C', 2, 'C', 'C'],
    ['NABELA DINI PRATAMA', 'VIII.1', 76, 'B', 1, 'B', 'B'],
    ['NAZIFA PUTRI', 'VIII.1', 85, 'B', 1, 'B', 'B'],
    ['RAHMI EDITTYA', 'VIII.1', 82, 'B', 1, 'SB', 'C'],
    ['RIZKA AYU ANDRIANI', 'VIII.1', 75, 'C', 3, 'B', 'C'],
    ['SATRIA', 'VIII.1', 73, 'B', 1, 'B', 'C'],
    ['YANG HERMANSYAH ADE PUTRA HAMDANI', 'VIII.1', 74, 'B', 1, 'B', 'B'],
    ['ZELVI PUTRI CAHYANI', 'VIII.1', 76, 'C', 1, 'B', 'B'],
    ['ACHMAD FAHREZY', 'VIII.2', 91, 'SB', 2, 'SB', 'B'],
    ['AIDIL ARRAZI', 'VIII.2', 74, 'B', 1, 'C', 'SB'],
    ['ANNA ALTAFUNISA', 'VIII.2', 80, 'B', 1, 'C', 'SB'],
    ['ARSY AHMAD ZULVA', 'VIII.2', 81, 'C', 1, 'B', 'B'],
    ['AZIRA YULANDA', 'VIII.2', 83, 'C', 0, 'B', 'B'],
    ['DAFFA ARIAN AL FARIZZI', 'VIII.2', 74, 'C', 0, 'B', 'B'],
    ['DAFFA MIQDAD', 'VIII.2', 75, 'B', 1, 'C', 'SB'],
    ['DIKA AULIA CANDA WINATA', 'VIII.2', 85, 'B', 1, 'C', 'B'],
    ['FADEL ARTA TANJUNG', 'VIII.2', 84, 'C', 1, 'B', 'C'],
    ['FAKHRI RIZQI PRATAMA', 'VIII.2', 86, 'C', 1, 'B', 'B'],
    ['GHALIB AL FARUQ', 'VIII.2', 88, 'B', 2, 'SB', 'B'],
    ['HAINA NAILAH', 'VIII.2', 89, 'B', 1, 'SB', 'SB'],
    ['JENNI APRILIA', 'VIII.2', 87, 'SB', 0, 'C', 'SB'],
    ['JIHAN VIDIA UTAMA', 'VIII.2', 91, 'SB', 0, 'B', 'C'],
    ['KEYLA ASYFA', 'VIII.2', 77, 'B', 1, 'C', 'B'],
    ['KEYSA PUTRI', 'VIII.2', 74, 'B', 2, 'B', 'C'],
    ['LUTHFI HAFIDH KHAIRI', 'VIII.2', 85, 'C', 2, 'B', 'SB'],
    ['MUHAMAD AL VINO', 'VIII.2', 86, 'C', 2, 'SB', 'B'],
    ['MUHAMMAD ALWAHIDI ZIKRI', 'VIII.2', 83, 'B', 1, 'B', 'C'],
    ['MUHAMMAD FAREL', 'VIII.2', 74, 'B', 3, 'SB', 'B'],
    ['NAZBI RAMADHAN', 'VIII.2', 80, 'B', 0, 'B', 'B'],
    ['NUR LAILA', 'VIII.2', 82, 'B', 0, 'B', 'B'],
    ['OCTHA RINA QUARTRIET', 'VIII.2', 83, 'B', 1, 'C', 'B'],
    ['RADITYA RIZKI PUTRA', 'VIII.2', 81, 'C', 1, 'B', 'C'],
    ['RAHADATUL AISYI', 'VIII.2', 77, 'C', 0, 'B', 'C'],
    ['RASTI IVANDA', 'VIII.2', 78, 'B', 1, 'B', 'SB'],
    ['RASYA JANNATA PUTRA', 'VIII.2', 79, 'B', 1, 'C', 'B'],
    ['REFANDY RIZALDY', 'VIII.2', 85, 'B', 1, 'C', 'SB'],
    ['ZAHRA AISYAH', 'VIII.2', 81, 'C', 2, 'C', 'B']
];

$success_count = 0;
$error_count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    try {
        $pdo->beginTransaction();
        
        // Get kriteria IDs
        $stmt = $pdo->query("SELECT id, kode FROM kriteria ORDER BY kode");
        $kriteria_map = [];
        while ($row = $stmt->fetch()) {
            $kriteria_map[$row['kode']] = $row['id'];
        }
        
        foreach ($data_kelas8 as $index => $row) {
            $nama = $row[0];
            $kelas = $row[1];
            $rata_raport = $row[2];
            $keaktifan = $row[3];
            $absensi = $row[4];
            $kedisiplinan = $row[5];
            $keagamaan = $row[6];
            
            // Find siswa by nama and kelas - with flexible matching
            $stmt = $pdo->prepare("SELECT id, nama FROM siswa WHERE kelas = ? AND status = 'aktif'");
            $stmt->execute([$kelas]);
            $siswa_kelas = $stmt->fetchAll();
            
            $siswa = null;
            // Exact match first
            foreach ($siswa_kelas as $s) {
                if (strtoupper(trim($s['nama'])) === strtoupper(trim($nama))) {
                    $siswa = $s;
                    break;
                }
            }
            
            // If not found, try partial match (for names with slight differences)
            if (!$siswa) {
                foreach ($siswa_kelas as $s) {
                    $db_name = strtoupper(str_replace([' ', '.'], '', $s['nama']));
                    $excel_name = strtoupper(str_replace([' ', '.'], '', $nama));
                    
                    // Check if names are similar (accounting for spacing/dots)
                    if (strpos($db_name, $excel_name) !== false || strpos($excel_name, $db_name) !== false) {
                        $siswa = $s;
                        break;
                    }
                }
            }
            
            if (!$siswa) {
                $errors[] = "Siswa tidak ditemukan: $nama ($kelas)";
                $error_count++;
                continue;
            }
            
            $siswa_id = $siswa['id'];
            
            // Delete existing penilaian untuk siswa ini
            $stmt = $pdo->prepare("DELETE FROM penilaian WHERE siswa_id = ?");
            $stmt->execute([$siswa_id]);
            
            // Insert penilaian baru
            $penilaian_data = [
                'C1' => [$rata_raport, konversiRaport($rata_raport)],           // Rata-rata Raport
                'C2' => [$keaktifan, konversiKategoriKeNumerik($keaktifan)],    // Keaktifan  
                'C3' => [$absensi, konversiAbsensi($absensi)],                  // Absensi
                'C4' => [$kedisiplinan, konversiKategoriKeNumerik($kedisiplinan)], // Kedisiplinan
                'C5' => [$keagamaan, konversiKategoriKeNumerik($keagamaan)]     // Keagamaan
            ];
            
            foreach ($penilaian_data as $kode_kriteria => $nilai_data) {
                if (isset($kriteria_map[$kode_kriteria])) {
                    $kriteria_id = $kriteria_map[$kode_kriteria];
                    $nilai_kategori = $nilai_data[0];
                    $nilai_numerik = $nilai_data[1];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO penilaian (siswa_id, kriteria_id, nilai_kategori, nilai_numerik) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$siswa_id, $kriteria_id, $nilai_kategori, $nilai_numerik]);
                }
            }
            
            $success_count++;
        }
        
        $pdo->commit();
        setAlert('success', "Import berhasil! $success_count siswa diimpor, $error_count error.");
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        setAlert('danger', 'Error saat import: ' . $e->getMessage());
    }
    
    header('Location: ../penilaian/index.php');
    exit();
}

// Helper functions (pastikan sudah ada di functions.php)
// function konversiRaport($nilai) {
//     if ($nilai >= 81) return 5.0;
//     if ($nilai >= 61) return 4.0;
//     if ($nilai >= 41) return 3.0;
//     if ($nilai >= 21) return 2.0;
//     return 1.0;
// }

// function konversiKategoriKeNumerik($kategori) {
//     $mapping = ['SB' => 5.0, 'B' => 4.0, 'C' => 3.0, 'KB' => 2.0, 'SKB' => 1.0];
//     return $mapping[strtoupper($kategori)] ?? 3.0;
// }

// function konversiAbsensi($jumlah) {
//     if ($jumlah == 0) return 1.0;
//     if ($jumlah <= 2) return 2.0;
//     if ($jumlah == 3) return 3.0;
//     if ($jumlah <= 5) return 4.0;
//     return 5.0;
// }
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-upload"></i> Import Data Penilaian Kelas VIII</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle"></i> Informasi Import</h6>
                <ul class="mb-0">
                    <li>Total data yang akan diimpor: <strong><?php echo count($data_kelas8); ?> siswa</strong></li>
                    <li>Data akan menggantikan penilaian yang sudah ada (jika ada)</li>
                    <li>Sistem akan mencari siswa berdasarkan nama dan kelas</li>
                    <li>Konversi otomatis: Nilai raport, kategori (SB/B/C/KB/SKB), dan absensi</li>
                </ul>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-warning">
                <h6>Error yang ditemukan:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Raport</th>
                            <th>Keaktifan</th>
                            <th>Absensi</th>
                            <th>Kedisiplinan</th>
                            <th>Keagamaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($data_kelas8, 0, 10) as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row[0]); ?></td>
                            <td><?php echo htmlspecialchars($row[1]); ?></td>
                            <td><?php echo $row[2]; ?></td>
                            <td><?php echo $row[3]; ?></td>
                            <td><?php echo $row[4]; ?></td>
                            <td><?php echo $row[5]; ?></td>
                            <td><?php echo $row[6]; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($data_kelas8) > 10): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                ... dan <?php echo count($data_kelas8) - 10; ?> data lainnya
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="../penilaian/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" name="import" class="btn btn-primary"
                        onclick="return confirm('Yakin ingin mengimpor <?php echo count($data_kelas8); ?> data penilaian? Data yang sudah ada akan ditimpa!')">
                        <i class="bi bi-upload"></i> Import Data Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once './includes/footer.php'; ?>