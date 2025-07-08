<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Fungsi untuk mengecek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Fungsi untuk mengecek role user
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Fungsi untuk redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Fungsi untuk redirect jika tidak memiliki akses
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ../dashboard/index.php');
        exit();
    }
}

// Fungsi untuk membersihkan input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Fungsi untuk format angka
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

// Fungsi untuk mendapatkan semua kriteria
function getAllKriteria($pdo) {
    $stmt = $pdo->query("SELECT * FROM kriteria ORDER BY kode");
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan semua siswa aktif
function getAllSiswaAktif($pdo) {
    $stmt = $pdo->query("SELECT * FROM siswa WHERE status = 'aktif' ORDER BY nama");
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan penilaian siswa (updated)
function getPenilaianSiswa($pdo, $siswa_id) {
    $stmt = $pdo->prepare("
        SELECT k.kode, k.nama, k.bobot, k.jenis, p.nilai_numerik as nilai, p.nilai_kategori
        FROM kriteria k 
        LEFT JOIN penilaian p ON k.id = p.kriteria_id AND p.siswa_id = ?
        ORDER BY k.kode
    ");
    $stmt->execute([$siswa_id]);
    return $stmt->fetchAll();
}

// Fungsi untuk konversi kategori ke numerik
function konversiKategoriKeNumerik($kategori) {
    $mapping = [
        'SB' => 5.0, // Sangat Baik
        'B' => 4.0,  // Baik
        'C' => 3.0,  // Cukup
        'KB' => 2.0, // Kurang Baik
        'SKB' => 1.0 // Sangat Kurang Baik
    ];
    
    return $mapping[strtoupper($kategori)] ?? 3.0;
}

// Fungsi untuk konversi nilai raport
function konversiRaport($nilai) {
    if ($nilai >= 81) return 5.0;
    if ($nilai >= 61) return 4.0;
    if ($nilai >= 41) return 3.0;
    if ($nilai >= 21) return 2.0;
    return 1.0;
}

// Fungsi untuk konversi absensi (cost criteria)
function konversiAbsensi($jumlah) {
    if ($jumlah == 0) return 1.0;  // Tidak pernah absen = terbaik
    if ($jumlah <= 2) return 2.0;
    if ($jumlah == 3) return 3.0;
    if ($jumlah <= 5) return 4.0;
    return 5.0; // >5 = terburuk
}

// Fungsi untuk mendapatkan siswa per tingkat
function getSiswaPerTingkat($pdo, $tingkat = null) {
    $where = "WHERE status = 'aktif'";
    $params = [];
    
    if ($tingkat) {
        $where .= " AND tingkat = ?";
        $params[] = $tingkat;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM siswa $where ORDER BY tingkat, kelas, nama");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Fungsi untuk auto-detect tingkat dari kelas
function detectTingkatFromKelas($kelas) {
    $kelas = strtoupper(trim($kelas));
    
    if (strpos($kelas, 'IX') === 0 || strpos($kelas, '9') === 0) {
        return '9';
    } elseif (strpos($kelas, 'VIII') === 0 || strpos($kelas, '8') === 0) {
        return '8';
    } elseif (strpos($kelas, 'VII') === 0 || strpos($kelas, '7') === 0) {
        return '7';
    }
    
    return '7'; // default
}

// Fungsi untuk menghitung Weighted Product per tingkat
function hitungWeightedProductPerTingkat($pdo, $tingkat = null) {
    // Ambil siswa dan kriteria
    $siswa = getSiswaPerTingkat($pdo, $tingkat);
    $kriteria = getAllKriteria($pdo);
    
    if (empty($siswa) || empty($kriteria)) {
        return false;
    }
    
    $hasil = [];
    $total_s = 0;
    
    // Langkah 1: Hitung nilai S untuk setiap alternatif
    foreach ($siswa as $s) {
        $skor_s = 1;
        $penilaian = getPenilaianSiswa($pdo, $s['id']);
        $nilai_lengkap = true;
        
        foreach ($penilaian as $p) {
            if ($p['nilai'] === null || $p['nilai'] <= 0) {
                $nilai_lengkap = false;
                break;
            }
            
            if ($p['jenis'] === 'cost') {
                // Untuk kriteria cost, gunakan 1/nilai
                $skor_s *= pow((1 / $p['nilai']), $p['bobot']);
            } else {
                // Untuk kriteria benefit, gunakan nilai langsung
                $skor_s *= pow($p['nilai'], $p['bobot']);
            }
        }
        
        // Hanya masukkan jika penilaian lengkap
        if ($nilai_lengkap) {
            $hasil[$s['id']] = [
                'siswa' => $s,
                'skor_s' => $skor_s,
                'skor_v' => 0,
                'penilaian' => $penilaian
            ];
            
            $total_s += $skor_s;
        }
    }
    
    // Langkah 2: Hitung nilai V (normalisasi)
    foreach ($hasil as $id => &$h) {
        $h['skor_v'] = $total_s > 0 ? $h['skor_s'] / $total_s : 0;
    }
    
    // Langkah 3: Urutkan berdasarkan skor V (descending)
    uasort($hasil, function($a, $b) {
        return $b['skor_v'] <=> $a['skor_v'];
    });
    
    // Langkah 4: Berikan ranking
    $ranking = 1;
    foreach ($hasil as $id => &$h) {
        $h['ranking'] = $ranking++;
    }
    
    return $hasil;
}

// Fungsi untuk menghitung semua tingkat dan ranking global
function hitungSemuaTingkat($pdo) {
    $semua_hasil = [];
    $tingkat_list = ['7', '8', '9'];
    
    // Hitung per tingkat
    foreach ($tingkat_list as $tingkat) {
        $hasil_tingkat = hitungWeightedProductPerTingkat($pdo, $tingkat);
        if ($hasil_tingkat) {
            foreach ($hasil_tingkat as $id => $data) {
                $data['tingkat'] = $tingkat;
                $data['ranking_tingkat'] = $data['ranking'];
                $semua_hasil[$id] = $data;
            }
        }
    }
    
    // Hitung ranking global
    uasort($semua_hasil, function($a, $b) {
        return $b['skor_v'] <=> $a['skor_v'];
    });
    
    $ranking_global = 1;
    foreach ($semua_hasil as $id => &$data) {
        $data['ranking_global'] = $ranking_global++;
        // Top 3 per tingkat dan top 10 global
        $data['is_penerima_tingkat'] = $data['ranking_tingkat'] <= 3;
        $data['is_penerima_global'] = $ranking_global <= 10;
    }
    
    return $semua_hasil;
}

// Fungsi untuk menyimpan hasil perhitungan (updated untuk multi-tingkat)
function simpanHasilPerhitungan($pdo, $hasil_perhitungan) {
    try {
        // Hapus hasil perhitungan lama untuk tahun ajaran yang sama
        $tahun_ajaran = date('Y') . '/' . (date('Y') + 1);
        $stmt = $pdo->prepare("DELETE FROM hasil_perhitungan WHERE tahun_ajaran = ?");
        $stmt->execute([$tahun_ajaran]);
        
        // Simpan hasil baru
        foreach ($hasil_perhitungan as $id => $hasil) {
            $stmt = $pdo->prepare("
                INSERT INTO hasil_perhitungan 
                (siswa_id, tingkat, skor_s, skor_v, ranking_tingkat, ranking_global, tahun_ajaran, tanggal_hitung, is_penerima) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $hasil['siswa']['id'],
                $hasil['tingkat'],
                $hasil['skor_s'],
                $hasil['skor_v'],
                $hasil['ranking_tingkat'],
                $hasil['ranking_global'],
                $tahun_ajaran,
                date('Y-m-d'),
                $hasil['is_penerima_global'] ? 1 : 0
            ]);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk mendapatkan hasil perhitungan per tingkat
function getHasilPerhitunganPerTingkat($pdo, $tingkat = null) {
    $where = "WHERE 1=1";
    $params = [];
    
    if ($tingkat) {
        $where .= " AND hp.tingkat = ?";
        $params[] = $tingkat;
    }
    
    $stmt = $pdo->prepare("
        SELECT hp.*, s.nis, s.nama, s.kelas, s.tingkat
        FROM hasil_perhitungan hp
        JOIN siswa s ON hp.siswa_id = s.id
        $where
        ORDER BY hp.tingkat, hp.ranking_tingkat ASC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan hasil perhitungan global (top 10)
function getHasilPerhitunganGlobal($pdo) {
    $stmt = $pdo->prepare("
        SELECT hp.*, s.nis, s.nama, s.kelas, s.tingkat
        FROM hasil_perhitungan hp
        JOIN siswa s ON hp.siswa_id = s.id
        ORDER BY hp.ranking_global ASC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan top 3 per tingkat
function getTop3PerTingkat($pdo) {
    $hasil = [];
    $tingkat_list = ['7', '8', '9'];
    
    foreach ($tingkat_list as $tingkat) {
        $stmt = $pdo->prepare("
            SELECT hp.*, s.nis, s.nama, s.kelas, s.tingkat
            FROM hasil_perhitungan hp
            JOIN siswa s ON hp.siswa_id = s.id
            WHERE hp.tingkat = ? AND hp.ranking_tingkat <= 3
            ORDER BY hp.ranking_tingkat ASC
        ");
        $stmt->execute([$tingkat]);
        $hasil["tingkat_$tingkat"] = $stmt->fetchAll();
    }
    
    return $hasil;
}

// Fungsi untuk mendapatkan statistik hasil
function getStatistikHasil($pdo) {
    $stats = [];
    
    // Total siswa per tingkat
    foreach (['7', '8', '9'] as $tingkat) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   COUNT(CASE WHEN ranking_tingkat <= 3 THEN 1 END) as penerima_tingkat
            FROM hasil_perhitungan 
            WHERE tingkat = ?
        ");
        $stmt->execute([$tingkat]);
        $stats["tingkat_$tingkat"] = $stmt->fetch();
    }
    
    // Total penerima global
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_global,
               COUNT(CASE WHEN is_penerima = 1 THEN 1 END) as penerima_global
        FROM hasil_perhitungan
    ");
    $stats['global'] = $stmt->fetch();
    
    return $stats;
}

// Fungsi untuk alert/notification
function setAlert($type, $message) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Fungsi untuk generate breadcrumb
function generateBreadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        if ($index === count($items) - 1) {
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . $item['text'] . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['text'] . '</a></li>';
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}
?>