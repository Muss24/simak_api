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
$event_id = $_POST['event_id'] ?? '';

if(empty($user_id) || empty($event_id)) {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
    exit;
}

try {
    // 1. Cek apakah user sudah absen di event ini
    $stmt_absen = $pdo->prepare("SELECT id FROM attendances WHERE user_id = ? AND event_id = ?");
    $stmt_absen->execute([$user_id, $event_id]);

    if ($stmt_absen->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Anda belum melakukan absensi. Akses materi ditolak."]);
        exit;
    }

    // 2. Cek apakah event sedang aktif
    $stmt_event = $pdo->prepare("SELECT status FROM events WHERE id = ?");
    $stmt_event->execute([$event_id]);
    $event = $stmt_event->fetch(PDO::FETCH_ASSOC);

    if (!$event || $event['status'] === 'selesai') {
        echo json_encode(["status" => "error", "message" => "Event telah berakhir. Akses ke materi ditutup."]);
        exit;
    }

    // 3. Ambil data materi TERMASUK file_path
    $stmt_materi = $pdo->prepare("SELECT id, judul, konten, file_path FROM materials WHERE event_id = ?");
    $stmt_materi->execute([$event_id]);
    $materials = $stmt_materi->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $materials]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>