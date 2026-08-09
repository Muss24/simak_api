<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require 'koneksi.php';

try {
    // Mengambil data event. Kita gunakan LIMIT 1 karena fokus pada satu event permanen.
    $stmt = $pdo->query("SELECT id, nama_event, status FROM events LIMIT 1");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($events) {
        echo json_encode([
            "status" => "success",
            "data" => $events
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Belum ada data event di database."
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error", 
        "message" => "Gagal mengambil data event: " . $e->getMessage()
    ]);
}
?>