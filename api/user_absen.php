<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
// header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['usr_id'] ?? '';
$qr_hash = $data['qr_hash'] ?? '';
// Tangkap koordinat dari HP User
$user_lat = $data['user_lat'] ?? '';
$user_lon = $data['user_lon'] ?? '';

if(empty($user_id) || empty($qr_hash) || empty($user_lat) || empty($user_lon)) {
    echo json_encode(["status" => "error", "message" => "Data atau akses lokasi tidak lengkap. Pastikan GPS menyala!"]); exit;
}

// Fungsi Hitung Jarak (Haversine Formula) dalam satuan METER
function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    return $earth_radius * $c;
}

try {
    // 1. Cari event
    $stmt = $conn->prepare("SELECT id, status, latitude, longitude, radius FROM events WHERE qr_hash = ?");
    $stmt->execute([$qr_hash]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(["status" => "error", "message" => "QR Code tidak valid!"]); exit;
    }
    if ($event['status'] !== 'aktif') {
        echo json_encode(["status" => "error", "message" => "Event ini belum aktif atau sudah selesai."]); exit;
    }

    // 2. LOGIKA VALIDASI RADIUS
    if (!empty($event['latitude']) && !empty($event['longitude'])) {
        $jarak = hitungJarak($user_lat, $user_lon, $event['latitude'], $event['longitude']);
        $batas_radius = $event['radius'] ? $event['radius'] : 100;

        if ($jarak > $batas_radius) {
            $jarak_bulat = round($jarak);
            echo json_encode(["status" => "error", "message" => "Gagal Absen! Anda berada di luar radius lokasi acara (Jarak Anda: {$jarak_bulat} meter)."]); exit;
        }
    }

    // 3. Cek absen ganda
    $cek_absen = $conn->prepare("SELECT id FROM attendances WHERE event_id = ? AND user_id = ?");
    $cek_absen->execute([$event['id'], $user_id]);
    if ($cek_absen->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Anda sudah melakukan absen untuk event ini."]); exit;
    }

    // 4. Catat absen
    $insert = $conn->prepare("INSERT INTO attendances (event_id, user_id, waktu_absen) VALUES (?, ?, NOW())");
    $insert->execute([$event['id'], $user_id]);

    echo json_encode(["status" => "success", "message" => "Absen berhasil dicatat! Materi sudah bisa dibuka."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Sistem Error: " . $e->getMessage()]);
}
?>