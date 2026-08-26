<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) $data = $_POST;

// Menangkap 'action' dan 'user_id'
$action = $data['action'] ?? $_GET['action'] ?? '';
$user_id = $data['user_id'] ?? $data['usr_id'] ?? '';

if (empty($action) || empty($user_id)) {
    http_response_code(400);
    echo json_encode(["status" => "failed", "message" => "Action dan User ID wajib diisi."]);
    exit;
}

try {
    // --- 1. GET DATA KEBERANGKATAN ---
    if ($action === 'get_dokumen') {
        $stmt = $conn->prepare("SELECT * FROM keberangkatan WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $dokumen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dokumen) {
            echo json_encode(["status" => "success", "data" => $dokumen]);
        } else {
            // Jika belum ada data di tabel, kembalikan null (FE bisa pakai nilai default)
            echo json_encode(["status" => "success", "data" => null, "message" => "Data dokumen belum diinisialisasi."]);
        }
    } 
    
    // --- 2. UPDATE / INSERT DATA KEBERANGKATAN ---
    elseif ($action === 'update_dokumen') {
        // Ambil data teks & varchar
        $status_jamaah = $data['status_jamaah'] ?? 'aktif';
        $plot = $data['plot'] ?? '';
        $kloter = $data['kloter'] ?? '';
        $rombongan = $data['rombongan'] ?? '';
        $regu = $data['regu'] ?? '';
        $posisi_porsi = $data['posisi_porsi'] ?? '';
        $status_porsi = $data['status_porsi'] ?? '';
        $porsi_cadangan = $data['porsi_cadangan'] ?? '';
        $status_cadangan = $data['status_cadangan'] ?? '';
        $passport = $data['passport'] ?? '';
        $visa = $data['visa'] ?? '';

        // Ambil data Enum (Default: 'menunggu')
        $mutasi = $data['mutasi'] ?? 'menunggu';
        $bio_metrik = $data['bio_metrik'] ?? 'menunggu';
        $puskesmas = $data['puskesmas'] ?? 'menunggu';
        $mcu = $data['mcu'] ?? 'menunggu';
        $pelunasan = $data['pelunasan'] ?? 'menunggu';
        $gform = $data['gform'] ?? 'menunggu';
        $foto = $data['foto'] ?? 'menunggu';
        $spph = $data['spph'] ?? 'menunggu';

        // Cek apakah data user sudah ada di tabel keberangkatan
        $cek = $conn->prepare("SELECT id FROM keberangkatan WHERE user_id = ?");
        $cek->execute([$user_id]);
        $exists = $cek->fetch();

        if ($exists) {
            // Jika ada -> UPDATE
            $sql = "UPDATE keberangkatan SET 
                    status_jamaah=?, plot=?, kloter=?, rombongan=?, regu=?, 
                    posisi_porsi=?, status_porsi=?, porsi_cadangan=?, status_cadangan=?, 
                    passport=?, visa=?, mutasi=?, bio_metrik=?, puskesmas=?, 
                    mcu=?, pelunasan=?, gform=?, foto=?, spph=?
                    WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $status_jamaah, $plot, $kloter, $rombongan, $regu, 
                $posisi_porsi, $status_porsi, $porsi_cadangan, $status_cadangan, 
                $passport, $visa, $mutasi, $bio_metrik, $puskesmas, 
                $mcu, $pelunasan, $gform, $foto, $spph, $user_id
            ]);
            echo json_encode(["status" => "success", "message" => "Data dokumen keberangkatan berhasil diperbarui!"]);
        } else {
            // Jika belum ada -> INSERT
            $sql = "INSERT INTO keberangkatan (
                        user_id, status_jamaah, plot, kloter, rombongan, regu, 
                        posisi_porsi, status_porsi, porsi_cadangan, status_cadangan, 
                        passport, visa, mutasi, bio_metrik, puskesmas, 
                        mcu, pelunasan, gform, foto, spph
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $user_id, $status_jamaah, $plot, $kloter, $rombongan, $regu, 
                $posisi_porsi, $status_porsi, $porsi_cadangan, $status_cadangan, 
                $passport, $visa, $mutasi, $bio_metrik, $puskesmas, 
                $mcu, $pelunasan, $gform, $foto, $spph
            ]);
            echo json_encode(["status" => "success", "message" => "Data dokumen keberangkatan berhasil dibuat!"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "failed", "message" => "Action tidak valid."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "failed", "message" => "Database Error: " . $e->getMessage()]);
}
?>