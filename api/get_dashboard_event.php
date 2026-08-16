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

require 'koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) $data = $_POST;

$user_id = $data['usr_id'] ?? '';

if(empty($user_id)) {
    echo json_encode(["status" => "error", "message" => "User ID tidak valid"]);
    exit;
}

try {
    $stmt_user = $conn->prepare("SELECT zona FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $zona_user = $user['zona'] ?? '';

    // PERBAIKAN: Hanya ambil event yang 'aktif' atau 'mendatang'. 
    // Event yang 'selesai' otomatis diabaikan untuk dashboard utama.
    $sql = "
        SELECT e.id, e.nama_event, e.waktu_event, e.status, e.jenis_event, e.zona_target,
               (SELECT COUNT(id) FROM attendances WHERE event_id = e.id AND user_id = ?) as is_attended
        FROM events e
        WHERE (e.jenis_event = 'universal' || e.zona_target = ?)
          AND e.status IN ('aktif', 'mendatang')
        ORDER BY 
          FIELD(e.status, 'aktif', 'mendatang'), 
          e.waktu_event ASC, 
          e.id ASC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $zona_user]);
    $event = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        echo json_encode(["status" => "success", "data" => $event]);
    } else {
        echo json_encode(["status" => "success", "data" => null, "message" => "Tidak ada event aktif atau mendatang"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>