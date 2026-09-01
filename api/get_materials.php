<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
// header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/koneksi.php';

$user_id = $_POST['user_id'] ?? '';
$event_id = $_POST['event_id'] ?? '';

if(empty($user_id) || empty($event_id)) {
    http_response_code(400);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Bad Request",
        "message" => "Incomplete data."
    ]);
    exit;
}

try {
    // 1. Cek apakah user sudah absen di event ini
    $stmt_absen = $conn->prepare("SELECT id FROM attendances WHERE user_id = ? AND event_id = ?");
    $stmt_absen->execute([$user_id, $event_id]);

    if ($stmt_absen->rowCount() == 0) {
        http_response_code(400);
        echo json_encode([
            "status" => "failed",
            "status_code" => "Bad Request",
            "message" => "You have not checked in for this event. Access to materials is denied."
        ]);
        exit;
    }

    // 2. Cek apakah event sedang aktif
    $stmt_event = $conn->prepare("SELECT status FROM events WHERE id = ?");
    $stmt_event->execute([$event_id]);
    $event = $stmt_event->fetch(PDO::FETCH_ASSOC);

    if (!$event || $event['status'] === 'selesai') {
        http_response_code(400);
        echo json_encode([
            "status" => "failed",
            "status_code" => "Bad Request",
            "message" => "Event has ended. Access to materials is closed."
        ]);
        exit;
    }

    // 3. Ambil data materi TERMASUK file_path
    $stmt_materi = $conn->prepare("SELECT id, judul AS title, konten AS content, file_path AS filePath FROM materials WHERE event_id = ?");
    $stmt_materi->execute([$event_id]);
    $materials = $stmt_materi->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $materials]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Internal Server Error",
        "message" => "Database Error: " . $e->getMessage()
    ]);
}
?>