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
        "code" => 400,
        "status_code" => "Bad Request",
        "message" => "User ID tidak valid atau tidak ditemukan"
    ]);
    exit;
}

try {
    $stmt_user = $conn->prepare("SELECT zona FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $zona_user = $user['zona'] ?? '';

    $sql = "
        SELECT e.id, e.nama_event, e.tempat, e.pembicara, e.waktu_event, e.status, e.jenis_event, e.zona_target,
               (SELECT COUNT(id) FROM attendances WHERE event_id = e.id AND user_id = ?) as is_attended
        FROM events e
        WHERE (e.jenis_event = 'umum' OR e.zona_target = ?)
        ORDER BY e.waktu_event DESC, e.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $zona_user]);
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $jadwal]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "code" => 500,
        "status_code" => "Internal Server Error",
        "message" => "Database Error: " . $e->getMessage()
    ]);
}
?>