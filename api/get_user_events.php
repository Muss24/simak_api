<?php
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'koneksi.php';

$user_id = $_POST['user_id'] ?? '';

if(empty($user_id)) {
    echo json_encode(["status" => "error", "message" => "User ID tidak valid."]);
    exit;
}

try {
    // Ambil data event yang sudah dihadiri user (JOIN tabel attendances dan events)
    $stmt = $conn->prepare("
        SELECT e.id, e.nama_event, e.status, a.waktu_absen 
        FROM attendances a 
        JOIN events e ON a.event_id = e.id 
        WHERE a.user_id = ? 
        ORDER BY a.waktu_absen DESC
    ");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $events]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>