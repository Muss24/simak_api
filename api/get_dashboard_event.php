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
    $stmt_user = $conn->prepare("SELECT zona FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $zona_user = $user['zona'] ?? '';

    $sql = "
        SELECT e.id,
               e.nama_event AS eventName, e.tempat AS venue, e.pembicara AS speaker,
               e.waktu_event AS eventTime, e.status, e.jenis_event AS eventType, e.zona_target AS targetZone,
               e.qr_hash AS qrHash, e.latitude, e.longitude, e.radius,
               (SELECT COUNT(id) FROM attendances WHERE event_id = e.id AND user_id = ?) as isAttended
        FROM events e
        WHERE (e.jenis_event = 'umum' OR e.zona_target = ?) 
          AND e.status IN ('live', 'mendatang')
        ORDER BY 
          FIELD(e.status, 'live', 'mendatang'), 
          e.waktu_event ASC, 
          e.id ASC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $zona_user]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        echo json_encode(["status" => "success", "data" => $event]);
    } else {
        echo json_encode(["status" => "success", "data" => null, "message" => "No live or upcoming events."]);
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