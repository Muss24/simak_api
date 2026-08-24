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
        "message" => "User ID tidak Valid atau tidak ditemukan"
    ]);
    exit;
}

try {
    // Ambil semua data user termasuk ZONA
    $stmt = $conn->prepare("
        SELECT id, nama_lengkap, nomor_porsi, whatsapp, role, 
               address, birthDate, birthPlace, companion, education, experience, 
               fatherName, gender, healthCondition, is_completed, job, 
               positiveTrait, program, skill, subDistrict, zona, village, gambar 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["status" => "success", "data" => $user]);
    } else {
       http_response_code(404);
       echo json_encode([
        "status" => "failed",
        "status_code" => "Not Found",
        "message" => "User ID tidak ditemukan"
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