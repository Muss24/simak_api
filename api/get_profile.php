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
if (!$data)
    $data = $_POST;

$user_id = $data['user_id'] ?? '';

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Bad Request",
        "message" => "User ID is invalid or missing."
    ]);
    exit;
}

try {
    // Ambil semua data user termasuk ZONA
    $stmt = $conn->prepare("
        SELECT 
            u.id, u.nama_lengkap, u.nomor_porsi, u.whatsapp, u.role, 
            u.address, u.birthDate, u.birthPlace, u.companion, u.nama_mahram, u.education, u.experience, 
            u.fatherName, u.gender, u.healthCondition, u.is_completed, u.job, 
            u.positiveTrait, u.program, u.skill, u.subDistrict, u.zona, u.village, 
            u.referensi_nama, u.referensi_wa, u.referensi_asal, u.gambar,
            
            k.status_jamaah, k.plot, k.kloter, k.rombongan, k.regu, 
            k.posisi_porsi, k.status_porsi, k.porsi_cadangan, k.status_cadangan, 
            k.passport, k.visa, k.mutasi, k.bio_metrik, k.puskesmas, 
            k.mcu, k.pelunasan, k.gform, k.foto AS foto_berkas, k.spph
        FROM users u
        LEFT JOIN keberangkatan k ON u.id = k.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            "status" => "success", 
            "data" => [
                // Data Profil & Biodata Utama
                "id" => $user['id'],
                "fullName" => $user['nama_lengkap'],
                "porsiNumber" => $user['nomor_porsi'],
                "whatsapp" => $user['whatsapp'],
                "role" => $user['role'],
                "address" => $user['address'],
                "birthDate" => $user['birthDate'],
                "birthPlace" => $user['birthPlace'],
                "companion" => $user['companion'],
                "mahramName" => $user['nama_mahram'],
                "education" => $user['education'],
                "experience" => $user['experience'],
                "fatherName" => $user['fatherName'],
                "gender" => $user['gender'],
                "health" => $user['healthCondition'],
                "isCompleted" => (bool) $user['is_completed'],
                "job" => $user['job'],
                "contribution" => $user['positiveTrait'],
                "depature" => $user['program'],
                "expertise" => $user['skill'],
                "subDistrict" => $user['subDistrict'],
                "zone" => $user['zona'],
                "village" => $user['village'],
                "referenceName" => $user['referensi_nama'],
                "referencePhone" => $user['referensi_wa'],
                "referenceOrigin" => $user['referensi_asal'],
                "profileImage" => $user['gambar'],

                // Data Keberangkatan & Dokumen (Default value jika tabel keberangkatan kosong)
                "pilgrimStatus" => $user['status_jamaah'] ?? 'aktif',
                "plotNumber" => $user['plot'] ?? '',
                "batch" => $user['kloter'] ?? '',
                "group" => $user['rombongan'] ?? '',
                "team" => $user['regu'] ?? '',
                "currPorsionPosition" => $user['posisi_porsi'] ?? '',
                "currPorstionStatus" => $user['status_porsi'] ?? '',
                "currPorsionPositionBackup" => $user['porsi_cadangan'] ?? '',
                "currPorstionStatusBackup" => $user['status_cadangan'] ?? '',
                "passport" => $user['passport'] ?? '',
                "visa" => $user['visa'] ?? '',
                
                // Status Berkas (Enum: lengkap / menunggu / gagal)
                "mutationStatus" => $user['mutasi'] ?? 'menunggu',
                "biometricStatus" => $user['bio_metrik'] ?? 'menunggu',
                "puskesmasStatus" => $user['puskesmas'] ?? 'menunggu',
                "mcuStatus" => $user['mcu'] ?? 'menunggu',
                "paymentStatus" => $user['pelunasan'] ?? 'menunggu',
                "googleFormStatus" => $user['gform'] ?? 'menunggu',
                "photoStatus" => $user['foto_berkas'] ?? 'menunggu',
                "spphStatus" => $user['spph'] ?? 'menunggu',
            ],
        ]);
    } else {
       http_response_code(404);
       echo json_encode([
        "status" => "failed",
        "status_code" => "Not Found",
        "message" => "User ID not found"
       ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Internal Server Error",
        "message" => "Database Error: " . $e->getMessage()
    ]);
}
?>