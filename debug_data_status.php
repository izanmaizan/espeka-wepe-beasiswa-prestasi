<?php
// File: debug_data_status.php
// Letakkan di folder admin/tools/ atau root

$page_title = 'Debug Status Data';
require_once './includes/header.php';
requireRole('admin');

echo "<div class='container mt-4'>";
echo "<h2><i class='bi bi-bug'></i> Debug Status Data Siswa dan Penilaian</h2>";

// 1. Cek jumlah siswa per tingkat
echo "<div class='card mb-4'>";
echo "<div class='card-header'><h5>1. Data Siswa per Tingkat</h5></div>";
echo "<div class='card-body'>";

$stmt = $pdo->query("
    SELECT 
        tingkat, 
        COUNT(*) as total_siswa,
        COUNT(CASE WHEN status = 'aktif' THEN 1 END) as siswa_aktif,
        COUNT(CASE WHEN status = 'nonaktif' THEN 1 END) as siswa_nonaktif
    FROM siswa 
    GROUP BY tingkat 
    ORDER BY tingkat
");
$siswa_stats = $stmt->fetchAll();

echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Tingkat</th><th>Total Siswa</th><th>Aktif</th><th>Non-aktif</th></tr></thead>";
echo "<tbody>";
foreach ($siswa_stats as $stat) {
    echo "<tr>";
    echo "<td><span class='badge bg-primary'>Tingkat {$stat['tingkat']}</span></td>";
    echo "<td>{$stat['total_siswa']}</td>";
    echo "<td><span class='badge bg-success'>{$stat['siswa_aktif']}</span></td>";
    echo "<td><span class='badge bg-warning'>{$stat['siswa_nonaktif']}</span></td>";
    echo "</tr>";
}
echo "</tbody></table>";
echo "</div></div>";

// 2. Cek detail siswa kelas VII dan VIII
echo "<div class='card mb-4'>";
echo "<div class='card-header'><h5>2. Detail Siswa Kelas VII & VIII</h5></div>";
echo "<div class='card-body'>";

$stmt = $pdo->query("
    SELECT tingkat, kelas, COUNT(*) as jumlah
    FROM siswa 
    WHERE tingkat IN ('7', '8') AND status = 'aktif'
    GROUP BY tingkat, kelas 
    ORDER BY tingkat, kelas
");
$detail_kelas = $stmt->fetchAll();

if (empty($detail_kelas)) {
    echo "<div class='alert alert-warning'>";
    echo "<i class='bi bi-exclamation-triangle'></i> ";
    echo "<strong>MASALAH DITEMUKAN:</strong> Tidak ada siswa aktif untuk tingkat 7 dan 8!";
    echo "</div>";
} else {
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Tingkat</th><th>Kelas</th><th>Jumlah Siswa</th></tr></thead>";
    echo "<tbody>";
    foreach ($detail_kelas as $detail) {
        echo "<tr>";
        echo "<td>Tingkat {$detail['tingkat']}</td>";
        echo "<td><span class='badge bg-info'>{$detail['kelas']}</span></td>";
        echo "<td>{$detail['jumlah']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
echo "</div></div>";

// 3. Cek jumlah kriteria
echo "<div class='card mb-4'>";
echo "<div class='card-header'><h5>3. Data Kriteria Penilaian</h5></div>";
echo "<div class='card-body'>";

$stmt = $pdo->query("SELECT * FROM kriteria ORDER BY kode");
$kriteria_list = $stmt->fetchAll();

echo "<p><strong>Total Kriteria:</strong> " . count($kriteria_list) . "</p>";
echo "<table class='table table-sm'>";
echo "<thead><tr><th>Kode</th><th>Nama</th><th>Bobot</th><th>Jenis</th></tr></thead>";
echo "<tbody>";
$total_bobot = 0;
foreach ($kriteria_list as $kriteria) {
    echo "<tr>";
    echo "<td><span class='badge bg-secondary'>{$kriteria['kode']}</span></td>";
    echo "<td>{$kriteria['nama']}</td>";
    echo "<td>{$kriteria['bobot']}</td>";
    echo "<td><span class='badge bg-" . ($kriteria['jenis'] == 'benefit' ? 'success' : 'warning') . "'>{$kriteria['jenis']}</span></td>";
    echo "</tr>";
    $total_bobot += $kriteria['bobot'];
}
echo "</tbody>";
echo "<tfoot><tr><th colspan='2'>Total Bobot</th><th class='text-" . (abs($total_bobot - 1.0) < 0.0001 ? 'success' : 'danger') . "'>{$total_bobot}</th><th></th></tr></tfoot>";
echo "</table>";
echo "</div></div>";

// 4. Cek penilaian per tingkat
echo "<div class='card mb-4'>";
echo "<div class='card-header'><h5>4. Status Penilaian per Tingkat</h5></div>";
echo "<div class='card-body'>";

$stmt = $pdo->query("
    SELECT 
        s.tingkat,
        COUNT(DISTINCT s.id) as total_siswa_aktif,
        COUNT(DISTINCT p.siswa_id) as siswa_ada_penilaian,
        COUNT(DISTINCT CASE 
            WHEN penilaian_count.jumlah_kriteria >= " . count($kriteria_list) . " 
            THEN p.siswa_id 
        END) as siswa_penilaian_lengkap
    FROM siswa s
    LEFT JOIN penilaian p ON s.id = p.siswa_id
    LEFT JOIN (
        SELECT siswa_id, COUNT(DISTINCT kriteria_id) as jumlah_kriteria
        FROM penilaian 
        GROUP BY siswa_id
    ) penilaian_count ON s.id = penilaian_count.siswa_id
    WHERE s.status = 'aktif'
    GROUP BY s.tingkat
    ORDER BY s.tingkat
");
$penilaian_stats = $stmt->fetchAll();

echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Tingkat</th><th>Total Siswa Aktif</th><th>Ada Penilaian</th><th>Penilaian Lengkap</th><th>Persentase</th></tr></thead>";
echo "<tbody>";
foreach ($penilaian_stats as $stat) {
    $persentase = $stat['total_siswa_aktif'] > 0 ? 
        round(($stat['siswa_penilaian_lengkap'] / $stat['total_siswa_aktif']) * 100, 1) : 0;
    
    echo "<tr>";
    echo "<td><span class='badge bg-primary'>Tingkat {$stat['tingkat']}</span></td>";
    echo "<td>{$stat['total_siswa_aktif']}</td>";
    echo "<td><span class='badge bg-info'>{$stat['siswa_ada_penilaian']}</span></td>";
    echo "<td><span class='badge bg-success'>{$stat['siswa_penilaian_lengkap']}</span></td>";
    echo "<td>";
    echo "<div class='progress' style='width: 100px; height: 20px;'>";
    echo "<div class='progress-bar bg-" . ($persentase == 100 ? 'success' : ($persentase > 0 ? 'warning' : 'danger')) . "' style='width: {$persentase}%'>";
    echo "{$persentase}%";
    echo "</div></div>";
    echo "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
echo "</div></div>";

// 5. Analisis detail untuk tingkat 7 dan 8
foreach (['7', '8'] as $tingkat) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header'><h5>5.{$tingkat}. Detail Siswa Tingkat {$tingkat}</h5></div>";
    echo "<div class='card-body'>";
    
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.nis,
            s.nama,
            s.kelas,
            s.status,
            COUNT(p.id) as jumlah_penilaian,
            CASE 
                WHEN COUNT(p.id) >= ? THEN 'Lengkap'
                WHEN COUNT(p.id) > 0 THEN 'Parsial'
                ELSE 'Kosong'
            END as status_penilaian
        FROM siswa s
        LEFT JOIN penilaian p ON s.id = p.siswa_id
        WHERE s.tingkat = ?
        GROUP BY s.id, s.nis, s.nama, s.kelas, s.status
        ORDER BY s.kelas, s.nama
        LIMIT 20
    ");
    $stmt->execute([count($kriteria_list), $tingkat]);
    $detail_siswa = $stmt->fetchAll();
    
    if (empty($detail_siswa)) {
        echo "<div class='alert alert-danger'>";
        echo "<i class='bi bi-exclamation-circle'></i> ";
        echo "<strong>MASALAH:</strong> Tidak ada siswa untuk tingkat {$tingkat}! ";
        echo "Pastikan data siswa sudah diinput dengan benar.";
        echo "</div>";
    } else {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm table-hover'>";
        echo "<thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Jumlah Penilaian</th><th>Status Penilaian</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($detail_siswa as $siswa) {
            echo "<tr class='" . ($siswa['status'] != 'aktif' ? 'table-warning' : '') . "'>";
            echo "<td>{$siswa['nis']}</td>";
            echo "<td>{$siswa['nama']}</td>";
            echo "<td><span class='badge bg-info'>{$siswa['kelas']}</span></td>";
            echo "<td><span class='badge bg-" . ($siswa['status'] == 'aktif' ? 'success' : 'warning') . "'>{$siswa['status']}</span></td>";
            echo "<td>{$siswa['jumlah_penilaian']}/" . count($kriteria_list) . "</td>";
            
            $badge_class = 'secondary';
            if ($siswa['status_penilaian'] == 'Lengkap') $badge_class = 'success';
            elseif ($siswa['status_penilaian'] == 'Parsial') $badge_class = 'warning';
            elseif ($siswa['status_penilaian'] == 'Kosong') $badge_class = 'danger';
            
            echo "<td><span class='badge bg-{$badge_class}'>{$siswa['status_penilaian']}</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
        
        // Count total untuk tingkat ini
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE tingkat = ?");
        $stmt->execute([$tingkat]);
        $total_tingkat = $stmt->fetchColumn();
        
        if ($total_tingkat > 20) {
            echo "<p class='text-muted'><em>Menampilkan 20 dari {$total_tingkat} siswa tingkat {$tingkat}</em></p>";
        }
    }
    echo "</div></div>";
}

// 6. Rekomendasi
echo "<div class='card mb-4'>";
echo "<div class='card-header'><h5>6. Rekomendasi Perbaikan</h5></div>";
echo "<div class='card-body'>";

$recommendations = [];

// Check if no students in tingkat 7 & 8
$stmt = $pdo->query("SELECT COUNT(*) FROM siswa WHERE tingkat IN ('7', '8') AND status = 'aktif'");
$siswa_78_count = $stmt->fetchColumn();

if ($siswa_78_count == 0) {
    $recommendations[] = [
        'type' => 'danger',
        'title' => 'Data Siswa Kelas VII & VIII Tidak Ada',
        'desc' => 'Input data siswa kelas VII dan VIII terlebih dahulu melalui menu Data Siswa → Tambah Siswa.',
        'action' => '<a href="../siswa/tambah.php" class="btn btn-sm btn-primary">Input Data Siswa</a>'
    ];
}

// Check penilaian for each tingkat
foreach ($penilaian_stats as $stat) {
    if ($stat['siswa_penilaian_lengkap'] == 0 && $stat['total_siswa_aktif'] > 0) {
        $tingkat_name = ['7' => 'VII', '8' => 'VIII', '9' => 'IX'][$stat['tingkat']] ?? $stat['tingkat'];
        $recommendations[] = [
            'type' => 'warning',
            'title' => "Belum Ada Penilaian untuk Kelas {$tingkat_name}",
            'desc' => "Ada {$stat['total_siswa_aktif']} siswa aktif tapi belum ada yang dinilai lengkap.",
            'action' => '<a href="../penilaian/index.php?tingkat=' . $stat['tingkat'] . '" class="btn btn-sm btn-success">Input Penilaian</a>'
        ];
    }
}

// Check kriteria bobot
if (abs($total_bobot - 1.0) >= 0.0001) {
    $recommendations[] = [
        'type' => 'warning',
        'title' => 'Total Bobot Kriteria Belum Tepat',
        'desc' => "Total bobot saat ini: {$total_bobot}, seharusnya 1.0000",
        'action' => '<a href="../kriteria/index.php" class="btn btn-sm btn-warning">Perbaiki Kriteria</a>'
    ];
}

if (empty($recommendations)) {
    echo "<div class='alert alert-success'>";
    echo "<i class='bi bi-check-circle'></i> ";
    echo "<strong>Semua data sudah lengkap!</strong> Sistem siap untuk menjalankan perhitungan ranking.";
    echo "</div>";
} else {
    foreach ($recommendations as $rec) {
        echo "<div class='alert alert-{$rec['type']}'>";
        echo "<div class='d-flex justify-content-between align-items-start'>";
        echo "<div>";
        echo "<h6><i class='bi bi-exclamation-triangle'></i> {$rec['title']}</h6>";
        echo "<p class='mb-0'>{$rec['desc']}</p>";
        echo "</div>";
        echo "<div>{$rec['action']}</div>";
        echo "</div>";
        echo "</div>";
    }
}

echo "</div></div>";

echo "</div>"; // Close container

require_once './includes/footer.php';
?>