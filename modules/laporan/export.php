<?php
require_once '../../includes/functions.php';

requireLogin();

// Get parameters
$view_mode = $_GET['view'] ?? 'global';
$tingkat_filter = $_GET['tingkat'] ?? '';

// Get hasil perhitungan based on view
if ($view_mode === 'global') {
    $hasil_perhitungan = getHasilPerhitunganGlobal($pdo);
    $filename_suffix = 'Top_10_Global';
} else {
    $hasil_perhitungan = getHasilPerhitunganPerTingkat($pdo, $tingkat_filter);
    $filename_suffix = 'Per_Tingkat';
    if ($tingkat_filter) {
        $filename_suffix .= '_Kelas_' . ($tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX'));
    }
}

if (empty($hasil_perhitungan)) {
    setAlert('warning', 'Tidak ada data hasil perhitungan untuk diekspor!');
    header('Location: index.php');
    exit();
}

// Set headers untuk download Excel
$filename = 'Hasil_Beasiswa_Prestasi_' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV
$output = fopen('php://output', 'w');

// UTF-8 BOM untuk Excel
fputs($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header berdasarkan view mode
if ($view_mode === 'global') {
    fputcsv($output, [
        'Ranking Global',
        'NIS',
        'Nama Siswa',
        'Kelas',
        'Tingkat',
        'Skor S',
        'Skor V',
        'Status',
        'Tanggal Perhitungan'
    ], ';');
    
    // Data rows untuk global
    foreach ($hasil_perhitungan as $hasil) {
        fputcsv($output, [
            $hasil['ranking_global'],
            $hasil['nis'],
            $hasil['nama'],
            $hasil['kelas'],
            'Tingkat ' . $hasil['tingkat'],
            number_format($hasil['skor_s'], 6, ',', '.'),
            number_format($hasil['skor_v'], 6, ',', '.'),
            'Penerima Beasiswa Global',
            date('d/m/Y', strtotime($hasil['tanggal_hitung']))
        ], ';');
    }
} else {
    fputcsv($output, [
        'Ranking Tingkat',
        'NIS', 
        'Nama Siswa',
        'Kelas',
        'Tingkat',
        'Skor S',
        'Skor V',
        'Ranking Global',
        'Status Tingkat',
        'Status Global',
        'Tanggal Perhitungan'
    ], ';');
    
    // Data rows untuk per tingkat
    foreach ($hasil_perhitungan as $hasil) {
        $status_tingkat = $hasil['ranking_tingkat'] <= 3 ? 'Top 3 Tingkat' : 'Tidak Masuk Top 3';
        $status_global = $hasil['ranking_global'] <= 10 ? 'Top 10 Global' : 'Tidak Masuk Top 10';
        
        fputcsv($output, [
            $hasil['ranking_tingkat'],
            $hasil['nis'],
            $hasil['nama'],
            $hasil['kelas'],
            'Tingkat ' . $hasil['tingkat'],
            number_format($hasil['skor_s'], 6, ',', '.'),
            number_format($hasil['skor_v'], 6, ',', '.'),
            $hasil['ranking_global'],
            $status_tingkat,
            $status_global,
            date('d/m/Y', strtotime($hasil['tanggal_hitung']))
        ], ';');
    }
}

// Tambahan informasi
fputcsv($output, [], ';'); // Empty row
fputcsv($output, ['=== INFORMASI PERHITUNGAN ==='], ';');
fputcsv($output, ['Metode', 'Weighted Product'], ';');
fputcsv($output, ['Total Siswa Dinilai', count($hasil_perhitungan)], ';');
fputcsv($output, ['Jenis Laporan', $view_mode === 'global' ? 'Top 10 Penerima Beasiswa Global' : 'Hasil Per Tingkat Kelas'], ';');
if ($tingkat_filter) {
    $tingkat_name = $tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX');
    fputcsv($output, ['Filter Tingkat', "Kelas $tingkat_name"], ';');
}
fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')], ';');

// Sistem Beasiswa
fputcsv($output, [], ';'); // Empty row
fputcsv($output, ['=== SISTEM BEASISWA PRESTASI ==='], ';');
fputcsv($output, ['Penerima per Tingkat', '3 siswa terbaik per tingkat (VII, VIII, IX)'], ';');
fputcsv($output, ['Penerima Global', '10 siswa terbaik dari seluruh tingkat'], ';');
fputcsv($output, ['Total Penerima Maksimal', '10 siswa (bisa overlap dengan penerima per tingkat)'], ';');

// Kriteria yang digunakan
fputcsv($output, [], ';'); // Empty row
fputcsv($output, ['=== KRITERIA PENILAIAN ==='], ';');
fputcsv($output, ['Kode', 'Nama Kriteria', 'Bobot', 'Jenis', 'Keterangan'], ';');

$kriteria_list = getAllKriteria($pdo);
foreach ($kriteria_list as $kriteria) {
    fputcsv($output, [
        $kriteria['kode'],
        $kriteria['nama'],
        number_format($kriteria['bobot'], 4, ',', '.') . ' (' . number_format($kriteria['bobot'] * 100, 1) . '%)',
        ucfirst($kriteria['jenis']),
        $kriteria['keterangan'] ?: '-'
    ], ';');
}

// Summary statistik
fputcsv($output, [], ';'); // Empty row
fputcsv($output, ['=== STATISTIK HASIL ==='], ';');

if ($view_mode === 'global') {
    fputcsv($output, ['Top 10 Global:', count($hasil_perhitungan) . ' siswa'], ';');
    
    // Breakdown per tingkat dalam top 10
    $breakdown_tingkat = [];
    foreach ($hasil_perhitungan as $hasil) {
        $tingkat = $hasil['tingkat'];
        if (!isset($breakdown_tingkat[$tingkat])) {
            $breakdown_tingkat[$tingkat] = 0;
        }
        $breakdown_tingkat[$tingkat]++;
    }
    
    foreach (['7', '8', '9'] as $tingkat) {
        $tingkat_name = $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX');
        $jumlah = $breakdown_tingkat[$tingkat] ?? 0;
        fputcsv($output, ["- Dari Kelas $tingkat_name", "$jumlah siswa"], ';');
    }
} else {
    // Statistik per tingkat
    if ($tingkat_filter) {
        $tingkat_name = $tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX');
        fputcsv($output, ["Kelas $tingkat_name:", count($hasil_perhitungan) . ' siswa dinilai'], ';');
        
        $top_3_count = 0;
        $top_10_global_count = 0;
        foreach ($hasil_perhitungan as $hasil) {
            if ($hasil['ranking_tingkat'] <= 3) $top_3_count++;
            if ($hasil['ranking_global'] <= 10) $top_10_global_count++;
        }
        
        fputcsv($output, ['- Top 3 Tingkat', "$top_3_count siswa"], ';');
        fputcsv($output, ['- Masuk Top 10 Global', "$top_10_global_count siswa"], ';');
    } else {
        // Semua tingkat
        $stats_tingkat = [];
        foreach ($hasil_perhitungan as $hasil) {
            $tingkat = $hasil['tingkat'];
            if (!isset($stats_tingkat[$tingkat])) {
                $stats_tingkat[$tingkat] = ['total' => 0, 'top3' => 0, 'top10_global' => 0];
            }
            $stats_tingkat[$tingkat]['total']++;
            if ($hasil['ranking_tingkat'] <= 3) $stats_tingkat[$tingkat]['top3']++;
            if ($hasil['ranking_global'] <= 10) $stats_tingkat[$tingkat]['top10_global']++;
        }
        
        foreach (['7', '8', '9'] as $tingkat) {
            $tingkat_name = $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX');
            $stat = $stats_tingkat[$tingkat] ?? ['total' => 0, 'top3' => 0, 'top10_global' => 0];
            fputcsv($output, ["Kelas $tingkat_name", "{$stat['total']} siswa dinilai"], ';');
            fputcsv($output, ["- Top 3 Tingkat", "{$stat['top3']} siswa"], ';');
            fputcsv($output, ["- Masuk Top 10 Global", "{$stat['top10_global']} siswa"], ';');
        }
    }
}

fclose($output);
exit();
?>