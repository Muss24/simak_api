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
        SELECT id, nama_lengkap, nomor_porsi, whatsapp, role, 
               address, birthDate, birthPlace, companion, nama_mahram, education, experience, 
               fatherName, gender, healthCondition, is_completed, job, 
               positiveTrait, program, skill, subDistrict, zona, village, referensi_nama, referensi_wa, referensi_asal, gambar 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["status" => "success", 
        "data" => [
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