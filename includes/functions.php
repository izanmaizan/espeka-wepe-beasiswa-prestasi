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

// Fungsi untuk normalisasi bobot kriteria
function normalisasiBobot($kriteria) {
    $total_bobot = 0;
    foreach ($kriteria as $k) {
        $total_bobot += $k['bobot'];
    }
    
    $kriteria_normalized = [];
    foreach ($kriteria as $k) {
        $k['bobot_normalized'] = $total_bobot > 0 ? $k['bobot'] / $total_bobot : 0;
        $kriteria_normalized[] = $k;
    }
    
    return $kriteria_normalized;
}

// Fungsi untuk menghitung Weighted Product - DIPERBAIKI
function hitungSemuaTingkat($pdo) {
    // Ambil semua siswa aktif dan kriteria
    $semua_siswa = getAllSiswaAktif($pdo);
    $kriteria = getAllKriteria($pdo);
    
    if (empty($semua_siswa) || empty($kriteria)) {
        return false;
    }
    
    // Normalisasi bobot kriteria
    $kriteria_normalized = normalisasiBobot($kriteria);
    
    $semua_hasil = [];
    $hasil_per_tingkat = [];
    
    // LANGKAH 1: Hitung skor S untuk SEMUA siswa (tidak per tingkat)
    foreach ($semua_siswa as $siswa) {
        $tingkat = $siswa['tingkat'];
        $skor_s = 1;
        $penilaian = getPenilaianSiswa($pdo, $siswa['id']);
        $nilai_lengkap = true;
        
        // Cek kelengkapan penilaian
        $kriteria_terpenuhi = 0;
        foreach ($kriteria_normalized as $k) {
            $nilai_kriteria = null;
            foreach ($penilaian as $p) {
                if ($p['kode'] === $k['kode']) {
                    $nilai_kriteria = $p['nilai'];
                    break;
                }
            }
            
            if ($nilai_kriteria === null || $nilai_kriteria <= 0) {
                $nilai_lengkap = false;
                break;
            } else {
                $kriteria_terpenuhi++;
            }
        }
        
        // Hanya proses jika penilaian lengkap
        if ($nilai_lengkap && $kriteria_terpenuhi >= count($kriteria_normalized)) {
            // Hitung skor S menggunakan formula Weighted Product
            foreach ($kriteria_normalized as $k) {
                $nilai_kriteria = null;
                foreach ($penilaian as $p) {
                    if ($p['kode'] === $k['kode']) {
                        $nilai_kriteria = $p['nilai'];
                        break;
                    }
                }
                
                if ($nilai_kriteria > 0) {
                    if ($k['jenis'] === 'cost') {
                        // Untuk kriteria cost: (1/nilai)^bobot
                        $skor_s *= pow((1 / $nilai_kriteria), $k['bobot_normalized']);
                    } else {
                        // Untuk kriteria benefit: nilai^bobot
                        $skor_s *= pow($nilai_kriteria, $k['bobot_normalized']);
                    }
                }
            }
            
            $semua_hasil[$siswa['id']] = [
                'siswa' => $siswa,
                'tingkat' => $tingkat,
                'skor_s' => $skor_s,
                'skor_v' => 0,
                'penilaian' => $penilaian,
                'ranking_tingkat' => 0,
                'ranking_global' => 0
            ];
            
            // Kelompokkan per tingkat untuk ranking tingkat
            if (!isset($hasil_per_tingkat[$tingkat])) {
                $hasil_per_tingkat[$tingkat] = [];
            }
            $hasil_per_tingkat[$tingkat][$siswa['id']] = &$semua_hasil[$siswa['id']];
        }
    }
    
    // LANGKAH 2: Hitung total S untuk normalisasi GLOBAL
    $total_s_global = 0;
    foreach ($semua_hasil as $hasil) {
        $total_s_global += $hasil['skor_s'];
    }
    
    // LANGKAH 3: Hitung skor V global
    foreach ($semua_hasil as $id => &$hasil) {
        $hasil['skor_v'] = $total_s_global > 0 ? $hasil['skor_s'] / $total_s_global : 0;
    }
    
    // LANGKAH 4: Ranking GLOBAL
    uasort($semua_hasil, function($a, $b) {
        return $b['skor_v'] <=> $a['skor_v'];
    });
    
    $ranking_global = 1;
    foreach ($semua_hasil as $id => &$hasil) {
        $hasil['ranking_global'] = $ranking_global++;
    }
    
    // LANGKAH 5: Ranking PER TINGKAT
    foreach (['7', '8', '9'] as $tingkat) {
        if (isset($hasil_per_tingkat[$tingkat])) {
            // Urutkan per tingkat berdasarkan skor V
            uasort($hasil_per_tingkat[$tingkat], function($a, $b) {
                return $b['skor_v'] <=> $a['skor_v'];
            });
            
            $ranking_tingkat = 1;
            foreach ($hasil_per_tingkat[$tingkat] as $id => &$hasil) {
                $hasil['ranking_tingkat'] = $ranking_tingkat++;
            }
        }
    }
    
    // LANGKAH 6: Tentukan status penerima
    foreach ($semua_hasil as $id => &$hasil) {
        $hasil['is_penerima_tingkat'] = $hasil['ranking_tingkat'] <= 3;
        $hasil['is_penerima_global'] = $hasil['ranking_global'] <= 10;
    }
    
    return $semua_hasil;
}

// Fungsi untuk menghitung Weighted Product per tingkat (untuk keperluan khusus)
function hitungWeightedProductPerTingkat($pdo, $tingkat = null) {
    $semua_hasil = hitungSemuaTingkat($pdo);
    
    if (!$semua_hasil || !$tingkat) {
        return false;
    }
    
    // Filter hasil untuk tingkat tertentu
    $hasil_tingkat = array_filter($semua_hasil, function($hasil) use ($tingkat) {
        return $hasil['tingkat'] === $tingkat;
    });
    
    return $hasil_tingkat;
}

// Fungsi untuk menyimpan hasil perhitungan (updated)
function simpanHasilPerhitungan($pdo, $hasil_perhitungan) {
    try {
        $pdo->beginTransaction();
        
        // Hapus hasil perhitungan lama
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
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error saving calculation results: " . $e->getMessage());
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
        WHERE hp.ranking_global <= 10
        ORDER BY hp.ranking_global ASC
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
               COUNT(CASE WHEN ranking_global <= 10 THEN 1 END) as penerima_global
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

// Fungsi untuk debug perhitungan (optional - untuk testing)
function debugPerhitungan($pdo) {
    $hasil = hitungSemuaTingkat($pdo);
    
    echo "<h3>Debug Hasil Perhitungan:</h3>";
    echo "<h4>Top 10 Global:</h4>";
    $count = 0;
    foreach ($hasil as $h) {
        if ($count >= 10) break;
        echo "{$h['ranking_global']}. {$h['siswa']['nama']} (Tingkat {$h['tingkat']}) - Skor V: " . formatNumber($h['skor_v'], 6) . "<br>";
        $count++;
    }
    
    echo "<h4>Top 3 Per Tingkat:</h4>";
    foreach (['7', '8', '9'] as $tingkat) {
        echo "<strong>Tingkat $tingkat:</strong><br>";
        $tingkat_results = array_filter($hasil, function($h) use ($tingkat) {
            return $h['tingkat'] === $tingkat && $h['ranking_tingkat'] <= 3;
        });
        
        usort($tingkat_results, function($a, $b) {
            return $a['ranking_tingkat'] <=> $b['ranking_tingkat'];
        });
        
        foreach ($tingkat_results as $h) {
            echo "{$h['ranking_tingkat']}. {$h['siswa']['nama']} - Skor V: " . formatNumber($h['skor_v'], 6) . "<br>";
        }
        echo "<br>";
    }
}
?>