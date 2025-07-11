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

// Export to PDF
exportToPDF($hasil_perhitungan, $view_mode, $tingkat_filter, $filename_suffix);

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
            margin: 1.5cm 1cm;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
        }
        
        body {
            font-family: "Times New Roman", serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 3px 0;
            color: #000;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 14px;
            margin: 2px 0;
            color: #000;
            text-transform: uppercase;
        }
        
        .header p {
            margin: 1px 0;
            font-size: 10px;
            color: #000;
        }
        
        .report-title {
            text-align: center;
            margin: 20px 0;
            padding: 12px;
            background: #f8f9fa;
        }
        
        .report-title h2 {
            font-size: 14px;
            color: #000;
            margin: 0 0 3px 0;
            text-decoration: underline;
        }
        
        .report-title p {
            margin: 0 0 10px 0;
            font-weight: bold;
            font-size: 11px;
            color: #000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        th {
            background: #000;
            color: white;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
        }
        
        td {
            padding: 5px 4px;
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
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
            color: #000;
            font-size: 10px;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin: 40px 0 8px 0;
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
    </div>';

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
    echo '<div class="signature-section" style="justify-content: flex-end;">
        <div class="signature-box">
            <p>Ampek Angkek, ' . date('d F Y') . '</p>
            <p><strong>Kepala Sekolah<br>SMPN 2 Ampek Angkek</strong></p>
            <div class="signature-line"></div>
            <p><strong>Drs. H. Ahmad Suhendra, M.Pd</strong></p>
            <p>NIP. 196512121990031007</p>
        </div>
    </div>';

    echo '

</body>
</html>';
    
    exit();
}


?>