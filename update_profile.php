<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/koneksi.php';

$user_id = $_POST['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(["status" => "error", "message" => "User ID tidak valid."]);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt_check = $conn->prepare("SELECT id_pendaftar FROM pendaftar WHERE user_id = ?");
    $stmt_check->execute([$user_id]);
    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

    $nama_referensi = $_POST['nama_referensi'] ?? null;
    $no_wa_referensi = $_POST['no_wa_referensi'] ?? null;
    $id_asal_referensi = $_POST['id_asal_referensi'] ?? null;
    $asal_referensi_lainnya = $_POST['asal_referensi_lainnya'] ?? null;
    
    $nama_ayah_kandung = $_POST['nama_ayah_kandung'] ?? '';
    $alamat_sppa = $_POST['alamat_sppa'] ?? '';
    $desa_kelurahan = $_POST['desa_kelurahan'] ?? '';
    $id_kecamatan = $_POST['id_kecamatan'] ?? 1;
    $kecamatan_lainnya = $_POST['kecamatan_lainnya'] ?? null;
    
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
    $tempat_lahir = $_POST['tempat_lahir'] ?? 'KARAWANG';
    $tempat_lahir_lainnya = $_POST['tempat_lahir_lainnya'] ?? null;
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? date('Y-m-d');
    
    $pendidikan_terakhir = $_POST['pendidikan_terakhir'] ?? 'SLTA';
    $program_keberangkatan = $_POST['program_keberangkatan'] ?? 'REGULER';
    $program_keberangkatan_lainnya = $_POST['program_keberangkatan_lainnya'] ?? null;
    $pengalaman_haji_umroh = $_POST['pengalaman_haji_umroh'] ?? 'BELUM';
    $berangkat_bersama = $_POST['berangkat_bersama'] ?? 'SUAMI';
    $berangkat_bersama_lainnya = $_POST['berangkat_bersama_lainnya'] ?? null;

    if ($existing) {
        $id_pendaftar = $existing['id_pendaftar'];
        $stmt_update = $conn->prepare("
            UPDATE pendaftar SET 
                nama_referensi = ?, no_wa_referensi = ?, id_asal_referensi = ?, asal_referensi_lainnya = ?,
                nama_ayah_kandung = ?, alamat_sppa = ?, desa_kelurahan = ?, 
                id_kecamatan = ?, kecamatan_lainnya = ?, jenis_kelamin = ?, 
                tempat_lahir = ?, tempat_lahir_lainnya = ?, tanggal_lahir = ?, 
                pendidikan_terakhir = ?, program_keberangkatan = ?, 
                program_keberangkatan_lainnya = ?, pengalaman_haji_umroh = ?, 
                berangkat_bersama = ?, berangkat_bersama_lainnya = ?
            WHERE user_id = ?
        ");
        $stmt_update->execute([
            $nama_referensi, $no_wa_referensi, $id_asal_referensi, $asal_referensi_lainnya,
            $nama_ayah_kandung, $alamat_sppa, $desa_kelurahan, 
            $id_kecamatan, $kecamatan_lainnya, $jenis_kelamin, 
            $tempat_lahir, $tempat_lahir_lainnya, $tanggal_lahir, 
            $pendidikan_terakhir, $program_keberangkatan, 
            $program_keberangkatan_lainnya, $pengalaman_haji_umroh, 
            $berangkat_bersama, $berangkat_bersama_lainnya, 
            $user_id
        ]);
    } else {
        $stmt_insert = $conn->prepare("
            INSERT INTO pendaftar (
                user_id, nama_referensi, no_wa_referensi, id_asal_referensi, asal_referensi_lainnya,
                nama_ayah_kandung, alamat_sppa, desa_kelurahan, id_kecamatan, 
                kecamatan_lainnya, jenis_kelamin, tempat_lahir, tempat_lahir_lainnya, 
                tanggal_lahir, pendidikan_terakhir, program_keberangkatan, 
                program_keberangkatan_lainnya, pengalaman_haji_umroh, berangkat_bersama, 
                berangkat_bersama_lainnya        
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert->execute([
            $user_id, $nama_referensi, $no_wa_referensi, $id_asal_referensi, $asal_referensi_lainnya,
            $nama_ayah_kandung, $alamat_sppa, $desa_kelurahan, $id_kecamatan, 
            $kecamatan_lainnya, $jenis_kelamin, $tempat_lahir, $tempat_lahir_lainnya, 
            $tanggal_lahir, $pendidikan_terakhir, $program_keberangkatan, 
            $program_keberangkatan_lainnya, $pengalaman_haji_umroh, $berangkat_bersama, 
            $berangkat_bersama_lainnya
        ]);
        $id_pendaftar = $conn->lastInsertId();
    }

    $conn->prepare("DELETE FROM pendaftar_kemampuan WHERE id_pendaftar = ?")->execute([$id_pendaftar]);
    $conn->prepare("DELETE FROM pendaftar_hal_positif WHERE id_pendaftar = ?")->execute([$id_pendaftar]);
    $conn->prepare("DELETE FROM pendaftar_kesehatan WHERE id_pendaftar = ?")->execute([$id_pendaftar]);
    $conn->prepare("DELETE FROM pendaftar_pekerjaan WHERE id_pendaftar = ?")->execute([$id_pendaftar]);

    if (!empty($_POST['kemampuans']) && is_array($_POST['kemampuans'])) {
        $stmt_kemampuan = $conn->prepare("INSERT INTO pendaftar_kemampuan (id_pendaftar, id_kemampuan, keterangan_lainnya) VALUES (?, ?, ?)");
        foreach ($_POST['kemampuans'] as $id_kemampuan) {
            $ket = ($id_kemampuan == 6) ? ($_POST['ket_kemampuan_lainnya'] ?? null) : null;
            $stmt_kemampuan->execute([$id_pendaftar, $id_kemampuan, $ket]);
        }
    }

    if (!empty($_POST['hal_positifs']) && is_array($_POST['hal_positifs'])) {
        $stmt_hp = $conn->prepare("INSERT INTO pendaftar_hal_positif (id_pendaftar, id_hal_positif, keterangan_lainnya) VALUES (?, ?, ?)");
        foreach ($_POST['hal_positifs'] as $id_hp) {
            $ket = ($id_hp == 10) ? ($_POST['ket_hal_positif_lainnya'] ?? null) : null;
            $stmt_hp->execute([$id_pendaftar, $id_hp, $ket]);
        }
    }

    if (!empty($_POST['kondisi_kesehatans']) && is_array($_POST['kondisi_kesehatans'])) {
        $stmt_kes = $conn->prepare("INSERT INTO pendaftar_kesehatan (id_pendaftar, id_kondisi, keterangan_lainnya) VALUES (?, ?, ?)");
        foreach ($_POST['kondisi_kesehatans'] as $id_kondisi) {
            $ket = ($id_kondisi == 10) ? ($_POST['ket_kesehatan_lainnya'] ?? null) : null;
            $stmt_kes->execute([$id_pendaftar, $id_kondisi, $ket]);
        }
    }

    if (!empty($_POST['pekerjaans']) && is_array($_POST['pekerjaans'])) {
        $stmt_pek = $conn->prepare("INSERT INTO pendaftar_pekerjaan (id_pendaftar, id_pekerjaan, keterangan_lainnya) VALUES (?, ?, ?)");
        foreach ($_POST['pekerjaans'] as $id_pekerjaan) {
            $ket = ($id_pekerjaan == 17) ? ($_POST['ket_pekerjaan_lainnya'] ?? null) : null;
            $stmt_pek->execute([$id_pendaftar, $id_pekerjaan, $ket]);
        }
    }

    $conn->commit();

    $stmt_set_complete = $conn->prepare("UPDATE users SET is_profile_complete = 1 WHERE id = ?");
    $stmt_set_complete->execute([$user_id]);

    $stmt_user = $conn->prepare("
        SELECT id, nama_lengkap, nomor_porsi, role, qr_code_hash, is_profile_complete 
        FROM users 
        WHERE id = ?
    ");
    $stmt_user->execute([$user_id]);
    $updated_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // HANYA ADA SATU KELUARAN JSON DI SINI
    echo json_encode([
        "status" => "success",
        "message" => "Data diri pendaftar berhasil disimpan!",
        "data" => $updated_user
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        "status" => "error",
        "message" => "Gagal menyimpan data: " . $e->getMessage(),
        "debug_line" => $e->getLine()
    ]);
}
?>