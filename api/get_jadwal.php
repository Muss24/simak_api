<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/koneksi.php';

try {
    // Ambil data event dari yang terbaru
    $stmt = $conn->query("SELECT id, nama_event, status FROM events ORDER BY id DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($events) {
        echo json_encode(["status" => "success", "data" => $events]);
    } else {
        echo json_encode(["status" => "success", "data" => [], "message" => "Belum ada jadwal event"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>