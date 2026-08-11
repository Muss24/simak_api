<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/koneksi.php';

$event_id = $_POST['event_id'] ?? 1;
$status = $_POST['status']; // 'aktif' atau 'selesai'

$stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
if ($stmt->execute([$status, $event_id])) {
    echo json_encode(["status" => "success", "message" => "Status event berhasil diubah menjadi " . $status]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal mengubah status event"]);
}
?>