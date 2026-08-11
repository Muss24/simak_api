<?php
// 1. Izinkan CORS dari React secara eksplisit
header("Access-Control-Allow-Origin: https://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 2. Tangani Preflight Request dari browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Pastikan output selalu JSON
header("Content-Type: application/json");

// 4. Matikan pesan error bawaan PHP agar tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/koneksi.php';
    
    $qr_scanned = $_POST['qr_code'] ?? '';
    $event_id = $_POST['event_id'] ?? 1;

    if(empty($qr_scanned)) {
        echo json_encode(["status" => "error", "message" => "QR Code kosong tidak dapat diproses."]);
        exit;
    }

    // Cek apakah event ada dan berstatus aktif
    $cek_event = $conn->prepare("SELECT status FROM events WHERE id = ?");
    $cek_event->execute([$event_id]);
    $event = $cek_event->fetch(PDO::FETCH_ASSOC);

    if (!$event || $event['status'] === 'selesai') {
        echo json_encode(["status" => "error", "message" => "Gagal absen: Event belum dimulai atau sudah diakhiri."]);
        exit;
    }

    // Cari data user berdasarkan QR Code
    $stmt = $conn->prepare("SELECT id FROM users WHERE qr_code_hash = ?");
    $stmt->execute([$qr_scanned]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Cek apakah user sudah absen di event ini
        $check_stmt = $conn->prepare("SELECT id FROM attendances WHERE user_id = ? AND event_id = ?");
        $check_stmt->execute([$user['id'], $event_id]);
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(["status" => "warning", "message" => "Peserta ini sudah pernah absen."]);
        } else {
            // Catat absensi
            $insert = $conn->prepare("INSERT INTO attendances (user_id, event_id) VALUES (?, ?)");
            if($insert->execute([$user['id'], $event_id])) {
                echo json_encode(["status" => "success", "message" => "Absensi berhasil dicatat!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Gagal menyimpan absensi ke database."]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "QR Code tidak dikenali oleh sistem!"]);
    }
} catch (Exception $e) {
    // 5. Tangkap error database dan jadikan JSON
    echo json_encode([
        "status" => "error", 
        "message" => "Database Error: " . $e->getMessage()
    ]);
}
?>