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
    echo json_encode(["status" => "failed", "message" => "Action and User ID are required."]);
    exit;
}

try {
    // --- 1. GET DATA KEBERANGKATAN ---
    if ($action === 'get_dokumen') {
        $stmt = $conn->prepare("SELECT * FROM keberangkatan WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $dokumen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dokumen) {
            echo json_encode(["status" => "success", 
            "data" => [
                    "id" => $dokumen['id'],
                    "userId" => $dokumen['user_id'],
                    "pilgrimStatus" => $dokumen['status_jamaah'],
                    "plotNumber" => $dokumen['plot'],
                    "batch" => $dokumen['kloter'],
                    "group" => $dokumen['rombongan'],
                    "team" => $dokumen['regu'],
                    "currPorsionPosition" => $dokumen['posisi_porsi'],
                    "currPorstionStatus" => $dokumen['status_porsi'],
                    "currPorsionPositionBackup" => $dokumen['porsi_cadangan'],
                    "currPorstionStatusBackup" => $dokumen['status_cadangan'],
                    "passport" => $dokumen['passport'],
                    "visa" => $dokumen['visa'],
                    "mutationStatus" => $dokumen['mutasi'],
                    "biometricStatus" => $dokumen['bio_metrik'],
                    "puskesmasStatus" => $dokumen['puskesmas'],
                    "mcuStatus" => $dokumen['mcu'],
                    "paymentStatus" => $dokumen['pelunasan'],
                    "googleFormStatus" => $dokumen['gform'],
                    "photoStatus" => $dokumen['foto'],
                    "spphStatus" => $dokumen['spph'],
                ], 
            ]);
        } else {
            // Jika belum ada data di tabel, kembalikan null (FE bisa pakai nilai default)
            echo json_encode(["status" => "success", "data" => null, "message" => "Document data not initialized."]);
        }
    } 
    
    // --- 2. UPDATE / INSERT DATA KEBERANGKATAN ---
    elseif ($action === 'update_dokumen') {
        // Ambil data teks & varchar
       $status_jamaah = $data['pilgrimStatus'] ?? 'aktif';
        $plot = $data['plotNumber'] ?? '';
        $kloter = $data['batch'] ?? '';
        $rombongan = $data['group'] ?? '';
        $regu = $data['team'] ?? '';
        $posisi_porsi = $data['currPorsionPosition'] ?? '';
        $status_porsi = $data['currPorstionStatus'] ?? '';
        $porsi_cadangan = $data['currPorsionPositionBackup'] ?? '';
        $status_cadangan = $data['currPorstionStatusBackup'] ?? '';
        $passport = $data['passport'] ?? '';
        $visa = $data['visa'] ?? '';

        // Ambil data Enum (Default: 'menunggu')
        $mutasi = $data['mutationStatus'] ?? 'menunggu';
        $bio_metrik = $data['biometricStatus'] ?? 'menunggu';
        $puskesmas = $data['puskesmasStatus'] ?? 'menunggu';
        $mcu = $data['mcuStatus'] ?? 'menunggu';
        $pelunasan = $data['paymentStatus'] ?? 'menunggu';
        $gform = $data['googleFormStatus'] ?? 'menunggu';
        $foto = $data['photoStatus'] ?? 'menunggu';
        $spph = $data['spphStatus'] ?? 'menunggu';

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
            echo json_encode(["status" => "success", "message" => "Document data updated successfully!"]);
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
            echo json_encode(["status" => "success", "message" => "Document data inserted successfully!"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "failed", "message" => "Invalid action."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "failed", "message" => "Database Error: " . $e->getMessage()]);
}
?>