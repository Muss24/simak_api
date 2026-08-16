<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Content-Type: application/json");
// header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/koneksi.php';

try {
    // Mengambil data event. Kita gunakan LIMIT 1 karena fokus pada satu event permanen.
    $stmt = $conn->query("SELECT id, nama_event, status FROM events LIMIT 1");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($events) {
        echo json_encode([
            "status" => "success",
            "data" => $events
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "data" => []
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "code" => 500,
        "status_code" => "Internal Server Error",
        "message" => "Gagal mengambil data event: " . $e->getMessage()
    ]);
}
?>