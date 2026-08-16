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

// Mendukung JSON atau Form-Data
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) $data = $_POST;

$user_id = $data['usr_id'] ?? '';

if(empty($user_id)) {
    echo json_encode(["status" => "error", "message" => "User ID tidak valid"]);
    exit;
}

try {
    // 1. Ambil zona user saat ini
    $stmt_user = $conn->prepare("SELECT zona FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $zona_user = $user['zona'] ?? '';

    // 2. Ambil event AKTIF yang Universal ATAU sesuai Zona User.
    // Sekaligus mengecek apakah user sudah melakukan absen (is_attended > 0)
    $sql = "
        SELECT e.id, e.nama_event, e.status, e.jenis_event, e.zona_target,
               (SELECT COUNT(id) FROM attendances WHERE event_id = e.id AND user_id = ?) as is_attended
        FROM events e
        WHERE (e.jenis_event = 'universal' OR e.zona_target = ?)
          AND e.status = 'aktif'
        ORDER BY e.id DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $zona_user]);
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $jadwal]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>