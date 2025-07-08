<?php
require_once '../../includes/functions.php';

requireLogin();

// Get parameters
$view_mode = $_GET['view'] ?? 'global';
$tingkat_filter = $_GET['tingkat'] ?? '';
$format = $_GET['format'] ?? 'csv'; // csv, excel, pdf

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

// Export based on format
switch ($format) {
    case 'pdf':
        exportToPDF($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix);
        break;
    case 'excel':
        exportToExcel($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix);
        break;
    default:
        exportToCSV($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix);
        break;
}

function exportToPDF($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix) {
    global $pdo;
    
    $filename = 'Laporan_Beasiswa_Prestasi_' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.html';
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    
    $tingkat_name = '';
    if ($tingkat_filter) {
        $tingkat_name = $tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX');
    }
    
    $report_title = $view_mode === 'global' ? 'LAPORAN PENERIMA BEASISWA PRESTASI GLOBAL' : 'LAPORAN PENERIMA BEASISWA PRESTASI PER TINGKAT';
    if ($tingkat_filter) {
        $report_title .= " KELAS $tingkat_name";
    }
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Beasiswa Prestasi SMPN 2 Ampek Angkek</title>
    <style>
        @page {
            size: A4;
            margin: 2cm 1.5cm;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
        }
        
        body {
            font-family: "Times New Roman", serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
            color: #000;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 16px;
            margin: 3px 0;
            color: #000;
            text-transform: uppercase;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 11px;
            color: #000;
        }
        
        .report-title {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #000;
            border-radius: 5px;
        }
        
        .report-title h2 {
            font-size: 16px;
            color: #000;
            margin: 0 0 5px 0;
            text-decoration: underline;
        }
        
        .report-title p {
            margin: 0;
            font-weight: bold;
            font-size: 12px;
            color: #000;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #000;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            width: 200px;
            color: #000;
        }
        
        .info-value {
            flex: 1;
            color: #000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        th {
            background: #000;
            color: white;
            padding: 10px 5px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
        }
        
        td {
            padding: 8px 5px;
            border: 1px solid #000;
            text-align: center;
            color: #000;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .ranking-1 { background: #fff3cd !important; font-weight: bold; }
        .ranking-2 { background: #d1ecf1 !important; font-weight: bold; }
        .ranking-3 { background: #d4edda !important; font-weight: bold; }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
            color: #000;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin: 60px 0 10px 0;
        }
        
        .statistics {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #000;
        }
        
        .statistics h3 {
            margin-top: 0;
            color: #000;
            font-size: 14px;
        }
        
        .stat-item {
            display: inline-block;
            margin: 5px 15px 5px 0;
            font-weight: bold;
            color: #000;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .no-print {
            background: #f0f0f0;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #000;
            color: #000;
        }
        
        .footer-info {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 15px;
        }
        
        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <p><strong>Instruksi:</strong> Klik tombol Print di atas, kemudian pilih "Save as PDF" sebagai printer untuk menyimpan sebagai file PDF.</p>
        <p><em>Untuk hasil terbaik, gunakan margin "Minimum" dan centang "Background graphics"</em></p>
    </div>

    <div class="header">
        <h1>PEMERINTAH KABUPATEN AGAM</h1>
        <h1>DINAS PENDIDIKAN</h1>
        <h2>SMP NEGERI 2 AMPEK ANGKEK</h2>
        <p>Alamat: Jl. Raya Ampek Angkek, Kecamatan Ampek Angkek, Kabupaten Agam, Sumatera Barat</p>
        <p>Telepon: (0752) 7051234 | Email: smpn2ampekangkek@gmail.com | NPSN: 10307315</p>
    </div>

    <div class="report-title">
        <h2>' . $report_title . '</h2>
        <p>TAHUN AJARAN 2024/2025</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Jenis Laporan:</div>
            <div class="info-value">' . ($view_mode === 'global' ? 'Top 10 Penerima Beasiswa Global' : 'Hasil Penilaian Per Tingkat Kelas') . '</div>
        </div>';
        
    if ($tingkat_filter) {
        echo '<div class="info-row">
            <div class="info-label">Filter Tingkat:</div>
            <div class="info-value">Kelas ' . $tingkat_name . '</div>
        </div>';
    }
    
    echo '<div class="info-row">
            <div class="info-label">Metode Perhitungan:</div>
            <div class="info-value">Weighted Product (WP)</div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Siswa Dinilai:</div>
            <div class="info-value">' . count($hasil_perhitungan) . ' siswa</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Laporan:</div>
            <div class="info-value">' . date('d F Y') . '</div>
        </div>
    </div>';

    // Statistics - WAJIB DITAMPILKAN
    echo '<div class="statistics">
        <h3>STATISTIK HASIL PERHITUNGAN</h3>';
    
    if ($view_mode === 'global') {
        $breakdown_tingkat = [];
        foreach ($hasil_perhitungan as $hasil) {
            $tingkat = $hasil['tingkat'];
            if (!isset($breakdown_tingkat[$tingkat])) {
                $breakdown_tingkat[$tingkat] = 0;
            }
            $breakdown_tingkat[$tingkat]++;
        }
        
        echo '<div class="stat-item">Total Penerima Beasiswa Global: <strong>' . count($hasil_perhitungan) . ' siswa</strong></div><br>';
        foreach (['7', '8', '9'] as $tingkat) {
            $tingkat_name_stat = $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX');
            $jumlah = $breakdown_tingkat[$tingkat] ?? 0;
            echo '<div class="stat-item">Dari Kelas ' . $tingkat_name_stat . ': <strong>' . $jumlah . ' siswa</strong></div>';
        }
    } else {
        if ($tingkat_filter) {
            $top_3_count = 0;
            $top_10_global_count = 0;
            foreach ($hasil_perhitungan as $hasil) {
                if ($hasil['ranking_tingkat'] <= 3) $top_3_count++;
                if ($hasil['ranking_global'] <= 10) $top_10_global_count++;
            }
            echo '<div class="stat-item">Total Siswa Kelas ' . $tingkat_name . ': <strong>' . count($hasil_perhitungan) . ' siswa</strong></div><br>';
            echo '<div class="stat-item">Top 3 Tingkat: <strong>' . $top_3_count . ' siswa</strong></div>';
            echo '<div class="stat-item">Masuk Top 10 Global: <strong>' . $top_10_global_count . ' siswa</strong></div>';
        } else {
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
                $tingkat_name_stat = $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX');
                $stat = $stats_tingkat[$tingkat] ?? ['total' => 0, 'top3' => 0, 'top10_global' => 0];
                echo '<div class="stat-item">Kelas ' . $tingkat_name_stat . ': <strong>' . $stat['total'] . ' siswa</strong> (Top 3: ' . $stat['top3'] . ', Global: ' . $stat['top10_global'] . ')</div><br>';
            }
        }
    }
    
    echo '</div>';

    // Table
    echo '<table>';
    
    if ($view_mode === 'global') {
        echo '<thead>
            <tr>
                <th width="8%">Ranking</th>
                <th width="10%">NIS</th>
                <th width="25%">Nama Siswa</th>
                <th width="10%">Kelas</th>
                <th width="10%">Tingkat</th>
                <th width="12%">Skor S</th>
                <th width="12%">Skor V</th>
                <th width="13%">Status</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($hasil_perhitungan as $hasil) {
            $row_class = '';
            if ($hasil['ranking_global'] == 1) $row_class = 'ranking-1';
            elseif ($hasil['ranking_global'] == 2) $row_class = 'ranking-2';
            elseif ($hasil['ranking_global'] == 3) $row_class = 'ranking-3';
            
            echo '<tr class="' . $row_class . '">
                <td class="text-center"><strong>' . $hasil['ranking_global'] . '</strong></td>
                <td class="text-center">' . htmlspecialchars($hasil['nis']) . '</td>
                <td class="text-left" style="padding-left: 10px;"><strong>' . htmlspecialchars($hasil['nama']) . '</strong></td>
                <td class="text-center">' . htmlspecialchars($hasil['kelas']) . '</td>
                <td class="text-center">Tingkat ' . $hasil['tingkat'] . '</td>
                <td class="text-center">' . formatNumber($hasil['skor_s'], 6) . '</td>
                <td class="text-center fw-bold">' . formatNumber($hasil['skor_v'], 6) . '</td>
                <td class="text-center"><strong>Penerima Beasiswa</strong></td>
            </tr>';
        }
    } else {
        echo '<thead>
            <tr>
                <th width="8%">Rank Tingkat</th>
                <th width="8%">NIS</th>
                <th width="22%">Nama Siswa</th>
                <th width="8%">Kelas</th>';
        if (!$tingkat_filter) {
            echo '<th width="8%">Tingkat</th>';
        }
        echo '<th width="10%">Skor S</th>
                <th width="10%">Skor V</th>
                <th width="8%">Rank Global</th>
                <th width="10%">Status Tingkat</th>
                <th width="8%">Status Global</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($hasil_perhitungan as $hasil) {
            $status_tingkat = $hasil['ranking_tingkat'] <= 3 ? 'Top 3 Tingkat' : 'Tidak Masuk';
            $status_global = $hasil['ranking_global'] <= 10 ? 'Top 10 Global' : 'Tidak Masuk';
            
            $row_class = '';
            if ($hasil['ranking_tingkat'] == 1) $row_class = 'ranking-1';
            elseif ($hasil['ranking_tingkat'] == 2) $row_class = 'ranking-2';
            elseif ($hasil['ranking_tingkat'] == 3) $row_class = 'ranking-3';
            
            echo '<tr class="' . $row_class . '">
                <td class="text-center"><strong>' . $hasil['ranking_tingkat'] . '</strong></td>
                <td class="text-center">' . htmlspecialchars($hasil['nis']) . '</td>
                <td class="text-left" style="padding-left: 5px;"><strong>' . htmlspecialchars($hasil['nama']) . '</strong></td>
                <td class="text-center">' . htmlspecialchars($hasil['kelas']) . '</td>';
            if (!$tingkat_filter) {
                echo '<td class="text-center">Tingkat ' . $hasil['tingkat'] . '</td>';
            }
            echo '<td class="text-center">' . formatNumber($hasil['skor_s'], 6) . '</td>
                <td class="text-center fw-bold">' . formatNumber($hasil['skor_v'], 6) . '</td>
                <td class="text-center">' . $hasil['ranking_global'] . '</td>
                <td class="text-center"><small>' . $status_tingkat . '</small></td>
                <td class="text-center"><small>' . $status_global . '</small></td>
            </tr>';
        }
    }
    
    echo '</tbody></table>';

    // Signature section
    echo '<div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p><strong>Kepala Sekolah<br>SMPN 2 Ampek Angkek</strong></p>
            <div class="signature-line"></div>
            <p><strong>Drs. H. Ahmad Suhendra, M.Pd</strong></p>
            <p>NIP. 196512121990031007</p>
        </div>
        
        <div class="signature-box">
            <p>Ampek Angkek, ' . date('d F Y') . '</p>
            <p><strong>Ketua Panitia Beasiswa</strong></p>
            <div class="signature-line"></div>
            <p><strong>Ahmad Rizki, S.Pd</strong></p>
            <p>NIP. 198505102010011021</p>
        </div>
    </div>';

    echo '<div class="footer-info">
        <p><strong>*** Dokumen ini dibuat secara elektronik dan sah tanpa tanda tangan basah ***</strong></p>
        <p>Dicetak pada: ' . date('d F Y H:i:s') . ' WIB | Sistem Informasi Beasiswa Prestasi SMPN 2 Ampek Angkek</p>
        <p>Alamat: Jl. Raya Ampek Angkek, Kecamatan Ampek Angkek, Kabupaten Agam, Sumatera Barat</p>
    </div>

</body>
</html>';
    
    exit();
}

function exportToExcel($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix) {
    global $pdo;
    
    $filename = 'Hasil_Beasiswa_Prestasi_' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $tingkat_name = '';
    if ($tingkat_filter) {
        $tingkat_name = $tingkat_filter === '7' ? 'VII' : ($tingkat_filter === '8' ? 'VIII' : 'IX');
    }

    // Start Excel format
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Beasiswa</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
    <style>
        .header { 
            font-family: Times New Roman; 
            font-size: 16pt; 
            font-weight: bold; 
            text-align: center; 
            background-color: #E6F3FF; 
            border: 2px solid #000;
            color: #000;
        }
        .subheader { 
            font-family: Times New Roman; 
            font-size: 12pt; 
            text-align: center; 
            background-color: #F0F8FF; 
            border: 1px solid #000;
            color: #000;
        }
        .title { 
            font-family: Times New Roman; 
            font-size: 14pt; 
            font-weight: bold; 
            text-align: center; 
            background-color: #000; 
            color: white; 
            border: 2px solid #000;
        }
        .table-header { 
            font-family: Calibri; 
            font-size: 11pt; 
            font-weight: bold; 
            text-align: center; 
            background-color: #000; 
            color: white; 
            border: 1px solid black;
        }
        .data-cell { 
            font-family: Calibri; 
            font-size: 10pt; 
            text-align: center; 
            border: 1px solid black;
            color: #000;
        }
        .data-left { 
            font-family: Calibri; 
            font-size: 10pt; 
            text-align: left; 
            border: 1px solid black;
            color: #000;
        }
        .ranking-1 { 
            background-color: #FFD700; 
            font-weight: bold; 
            border: 2px solid #B8860B;
            color: #000;
        }
        .ranking-2 { 
            background-color: #C0C0C0; 
            font-weight: bold; 
            border: 2px solid #2F4F4F;
            color: #000;
        }
        .ranking-3 { 
            background-color: #CD7F32; 
            font-weight: bold; 
            border: 2px solid #8B4513;
            color: #000;
        }
        .info-label { 
            font-family: Calibri; 
            font-size: 11pt; 
            font-weight: bold; 
            background-color: #F8F9FA; 
            border: 1px solid black;
            color: #000;
        }
        .info-value { 
            font-family: Calibri; 
            font-size: 11pt; 
            border: 1px solid black;
            color: #000;
        }
        .section-header { 
            font-family: Calibri; 
            font-size: 12pt; 
            font-weight: bold; 
            text-align: center; 
            background-color: #000; 
            color: white; 
            border: 2px solid black;
        }
        .stat-section {
            font-family: Calibri; 
            font-size: 11pt; 
            background-color: #F8F9FA; 
            border: 1px solid black;
            color: #000;
            font-weight: bold;
        }
    </style>
</head>
<body>';

    $report_title = $view_mode === 'global' ? 'LAPORAN PENERIMA BEASISWA PRESTASI GLOBAL' : 'LAPORAN PENERIMA BEASISWA PRESTASI PER TINGKAT';
    if ($tingkat_filter) {
        $report_title .= " KELAS $tingkat_name";
    }

    echo '<table border="1" cellpadding="3" cellspacing="0">';
    
    // Header rows
    echo '<tr><td colspan="10" class="header">PEMERINTAH KABUPATEN AGAM - DINAS PENDIDIKAN</td></tr>';
    echo '<tr><td colspan="10" class="header">SMP NEGERI 2 AMPEK ANGKEK</td></tr>';
    echo '<tr><td colspan="10" class="subheader">Jl. Raya Ampek Angkek, Kec. Ampek Angkek, Kab. Agam, Sumatera Barat</td></tr>';
    echo '<tr><td colspan="10"></td></tr>';
    echo '<tr><td colspan="10" class="title">' . $report_title . ' - T.A 2024/2025</td></tr>';
    echo '<tr><td colspan="10"></td></tr>';

    // Info section
    echo '<tr><td class="info-label">INFORMASI LAPORAN</td><td colspan="9" class="info-value"></td></tr>';
    echo '<tr><td class="info-label">Jenis Laporan:</td><td colspan="9" class="info-value">' . ($view_mode === 'global' ? 'Top 10 Penerima Beasiswa Global' : 'Hasil Penilaian Per Tingkat Kelas') . '</td></tr>';
    
    if ($tingkat_filter) {
        echo '<tr><td class="info-label">Filter Tingkat:</td><td colspan="9" class="info-value">Kelas ' . $tingkat_name . '</td></tr>';
    }
    
    echo '<tr><td class="info-label">Metode Perhitungan:</td><td colspan="9" class="info-value">Weighted Product (WP)</td></tr>';
    echo '<tr><td class="info-label">Total Siswa Dinilai:</td><td colspan="9" class="info-value">' . count($hasil_perhitungan) . '</td></tr>';
    echo '<tr><td class="info-label">Tanggal Laporan:</td><td colspan="9" class="info-value">' . date('d F Y') . '</td></tr>';
    echo '<tr><td colspan="10"></td></tr>';

    // Statistics section - WAJIB DITAMPILKAN
    echo '<tr><td colspan="10" class="section-header">STATISTIK HASIL PERHITUNGAN</td></tr>';
    
    if ($view_mode === 'global') {
        $breakdown_tingkat = [];
        foreach ($hasil_perhitungan as $hasil) {
            $tingkat = $hasil['tingkat'];
            if (!isset($breakdown_tingkat[$tingkat])) {
                $breakdown_tingkat[$tingkat] = 0;
            }
            $breakdown_tingkat[$tingkat]++;
        }
        
        echo '<tr><td class="stat-section">Total Penerima Beasiswa Global:</td><td class="stat-section">' . count($hasil_perhitungan) . '</td><td class="stat-section">siswa</td><td colspan="7"></td></tr>';
        
        foreach (['7', '8', '9'] as $tingkat) {
            $tingkat_name_stat = $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX');
            $jumlah = $breakdown_tingkat[$tingkat] ?? 0;
            echo '<tr><td class="stat-section">Dari Kelas ' . $tingkat_name_stat . ':</td><td class="stat-section">' . $jumlah . '</td><td class="stat-section">siswa</td><td colspan="7"></td></tr>';
        }
    } else {
        if ($tingkat_filter) {
            $top_3_count = 0;
            $top_10_global_count = 0;
            foreach ($hasil_perhitungan as $hasil) {
                if ($hasil['ranking_tingkat'] <= 3) $top_3_count++;
                if ($hasil['ranking_global'] <= 10) $top_10_global_count++;
            }
            echo '<tr><td class="stat-section">Total Siswa Kelas ' . $tingkat_name . ':</td><td class="stat-section">' . count($hasil_perhitungan) . '</td><td class="stat-section">siswa</td><td colspan="7"></td></tr>';
            echo '<tr><td class="stat-section">Top 3 Tingkat:</td><td class="stat-section">' . $top_3_count . '</td><td class="stat-section">siswa</td><td colspan="7"></td></tr>';
            echo '<tr><td class="stat-section">Masuk Top 10 Global:</td><td class="stat-section">' . $top_10_global_count . '</td><td class="stat-section">siswa</td><td colspan="7"></td></tr>';
        } else {
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
                $tingkat_name_stat = $tingkat === '7' ? 'VII' : ($tingkat === '8' ? 'VIII' : 'IX');
                $stat = $stats_tingkat[$tingkat] ?? ['total' => 0, 'top3' => 0, 'top10_global' => 0];
                echo '<tr><td class="stat-section">Kelas ' . $tingkat_name_stat . ':</td><td class="stat-section">' . $stat['total'] . ' siswa</td><td class="stat-section">Top 3: ' . $stat['top3'] . '</td><td class="stat-section">Global: ' . $stat['top10_global'] . '</td><td colspan="6"></td></tr>';
            }
        }
    }
    
    echo '<tr><td colspan="10"></td></tr>';

    // Table headers
    echo '<tr>';
    if ($view_mode === 'global') {
        echo '<td class="table-header">RANKING GLOBAL</td>';
        echo '<td class="table-header">NIS</td>';
        echo '<td class="table-header">NAMA SISWA</td>';
        echo '<td class="table-header">KELAS</td>';
        echo '<td class="table-header">TINGKAT</td>';
        echo '<td class="table-header">SKOR S</td>';
        echo '<td class="table-header">SKOR V</td>';
        echo '<td class="table-header">STATUS</td>';
        echo '<td colspan="2"></td>';
    } else {
        echo '<td class="table-header">RANK TINGKAT</td>';
        echo '<td class="table-header">NIS</td>';
        echo '<td class="table-header">NAMA SISWA</td>';
        echo '<td class="table-header">KELAS</td>';
        echo '<td class="table-header">TINGKAT</td>';
        echo '<td class="table-header">SKOR S</td>';
        echo '<td class="table-header">SKOR V</td>';
        echo '<td class="table-header">RANK GLOBAL</td>';
        echo '<td class="table-header">STATUS TINGKAT</td>';
        echo '<td class="table-header">STATUS GLOBAL</td>';
    }
    echo '</tr>';

    // Data rows
    foreach ($hasil_perhitungan as $hasil) {
        $ranking_col = $view_mode === 'global' ? $hasil['ranking_global'] : $hasil['ranking_tingkat'];
        $row_class = '';
        if ($ranking_col == 1) $row_class = 'ranking-1';
        elseif ($ranking_col == 2) $row_class = 'ranking-2';
        elseif ($ranking_col == 3) $row_class = 'ranking-3';
        
        echo '<tr>';
        
        if ($view_mode === 'global') {
            echo '<td class="data-cell ' . $row_class . '">' . $hasil['ranking_global'] . '</td>';
            echo '<td class="data-cell">' . htmlspecialchars($hasil['nis']) . '</td>';
            echo '<td class="data-left">' . htmlspecialchars($hasil['nama']) . '</td>';
            echo '<td class="data-cell">' . htmlspecialchars($hasil['kelas']) . '</td>';
            echo '<td class="data-cell">Tingkat ' . $hasil['tingkat'] . '</td>';
            echo '<td class="data-cell">' . formatNumber($hasil['skor_s'], 6) . '</td>';
            echo '<td class="data-cell">' . formatNumber($hasil['skor_v'], 6) . '</td>';
            echo '<td class="data-cell">PENERIMA BEASISWA GLOBAL</td>';
            echo '<td colspan="2"></td>';
        } else {
            $status_tingkat = $hasil['ranking_tingkat'] <= 3 ? 'TOP 3 TINGKAT' : 'TIDAK MASUK TOP 3';
            $status_global = $hasil['ranking_global'] <= 10 ? 'TOP 10 GLOBAL' : 'TIDAK MASUK TOP 10';
            
            echo '<td class="data-cell ' . $row_class . '">' . $hasil['ranking_tingkat'] . '</td>';
            echo '<td class="data-cell">' . htmlspecialchars($hasil['nis']) . '</td>';
            echo '<td class="data-left">' . htmlspecialchars($hasil['nama']) . '</td>';
            echo '<td class="data-cell">' . htmlspecialchars($hasil['kelas']) . '</td>';
            echo '<td class="data-cell">Tingkat ' . $hasil['tingkat'] . '</td>';
            echo '<td class="data-cell">' . formatNumber($hasil['skor_s'], 6) . '</td>';
            echo '<td class="data-cell">' . formatNumber($hasil['skor_v'], 6) . '</td>';
            echo '<td class="data-cell">' . $hasil['ranking_global'] . '</td>';
            echo '<td class="data-cell">' . $status_tingkat . '</td>';
            echo '<td class="data-cell">' . $status_global . '</td>';
        }
        
        echo '</tr>';
    }

    echo '</table>';
    echo '</body></html>';
    
    exit();
}

function exportToCSV($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix) {
    global $pdo;
    
    $filename = 'Hasil_Beasiswa_Prestasi_' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF).chr(0xBB).chr(0xBF));

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
        
        foreach ($hasil_perhitungan as $hasil) {
            fputcsv($output, [
                $hasil['ranking_global'],
                $hasil['nis'],
                $hasil['nama'],
                $hasil['kelas'],
                'Tingkat ' . $hasil['tingkat'],
                formatNumber($hasil['skor_s'], 6),
                formatNumber($hasil['skor_v'], 6),
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
        
        foreach ($hasil_perhitungan as $hasil) {
            $status_tingkat = $hasil['ranking_tingkat'] <= 3 ? 'Top 3 Tingkat' : 'Tidak Masuk Top 3';
            $status_global = $hasil['ranking_global'] <= 10 ? 'Top 10 Global' : 'Tidak Masuk Top 10';
            
            fputcsv($output, [
                $hasil['ranking_tingkat'],
                $hasil['nis'],
                $hasil['nama'],
                $hasil['kelas'],
                'Tingkat ' . $hasil['tingkat'],
                formatNumber($hasil['skor_s'], 6),
                formatNumber($hasil['skor_v'], 6),
                $hasil['ranking_global'],
                $status_tingkat,
                $status_global,
                date('d/m/Y', strtotime($hasil['tanggal_hitung']))
            ], ';');
        }
    }

    // Additional information - HANYA STATISTIK
    fputcsv($output, [], ';');
    fputcsv($output, ['=== STATISTIK HASIL PERHITUNGAN ==='], ';');
    
    if ($view_mode === 'global') {
        fputcsv($output, ['Top 10 Global:', count($hasil_perhitungan) . ' siswa'], ';');
        
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
    }
    
    fputcsv($output, ['Metode Perhitungan', 'Weighted Product'], ';');
    fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')], ';');

    fclose($output);
    exit();
}
?>