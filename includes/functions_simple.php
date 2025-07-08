<?php
session_start();
require_once __DIR__ . '/../config/simple.php';

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

// Fungsi untuk mendapatkan penilaian siswa
function getPenilaianSiswa($pdo, $siswa_id) {
    $stmt = $pdo->prepare("
        SELECT k.kode, k.nama, k.bobot, k.jenis, p.nilai 
        FROM kriteria k 
        LEFT JOIN penilaian p ON k.id = p.kriteria_id AND p.siswa_id = ?
        ORDER BY k.kode
    ");
    $stmt->execute([$siswa_id]);
    return $stmt->fetchAll();
}

// Fungsi untuk menghitung Weighted Product
function hitungWeightedProduct($pdo) {
    // Ambil semua siswa dan kriteria
    $siswa = getAllSiswaAktif($pdo);
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
        
        foreach ($penilaian as $p) {
            if ($p['nilai'] !== null && $p['nilai'] > 0) {
                if ($p['jenis'] === 'cost') {
                    // Untuk kriteria cost, gunakan 1/nilai
                    $skor_s *= pow((1 / $p['nilai']), $p['bobot']);
                } else {
                    // Untuk kriteria benefit, gunakan nilai langsung
                    $skor_s *= pow($p['nilai'], $p['bobot']);
                }
            }
        }
        
        $hasil[$s['id']] = [
            'siswa' => $s,
            'skor_s' => $skor_s,
            'skor_v' => 0
        ];
        
        $total_s += $skor_s;
    }
    
    // Langkah 2: Hitung nilai V (normalisasi)
    foreach ($hasil as $id => &$h) {
        $h['skor_v'] = $h['skor_s'] / $total_s;
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

// Fungsi untuk menyimpan hasil perhitungan
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
                (siswa_id, skor_s, skor_v, ranking, tahun_ajaran, tanggal_hitung) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $hasil['siswa']['id'],
                $hasil['skor_s'],
                $hasil['skor_v'],
                $hasil['ranking'],
                $tahun_ajaran,
                date('Y-m-d')
            ]);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk mendapatkan hasil perhitungan terakhir
function getHasilPerhitunganTerakhir($pdo) {
    $stmt = $pdo->query("
        SELECT hp.*, s.nis, s.nama, s.kelas 
        FROM hasil_perhitungan hp
        JOIN siswa s ON hp.siswa_id = s.id
        ORDER BY hp.ranking ASC
    ");
    return $stmt->fetchAll();
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