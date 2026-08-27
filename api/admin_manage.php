<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/koneksi.php';

// Tangkap data dari JSON Axios atau $_POST biasa
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'get_events') {
        $stmt = $conn->query("SELECT * FROM events ORDER BY id DESC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    
    elseif ($action === 'create_event') {
        $nama = $data['nama_event'] ?? $_POST['nama_event'] ?? '';
        $tempat = $data['tempat'] ?? $_POST['tempat'] ?? '';
        $pembicara = $data['pembicara'] ?? $_POST['pembicara'] ?? '';
        $jenis = $data['jenis_event'] ?? $_POST['jenis_event'] ?? 'umum';
        $zona_target = $data['zona_target'] ?? $_POST['zona_target'] ?? null;
        $waktu = $data['waktu_event'] ?? $_POST['waktu_event'] ?? date('Y-m-d H:i:s'); 
        
        $latitude = $data['latitude'] ?? $_POST['latitude'] ?? null;
        $longitude = $data['longitude'] ?? $_POST['longitude'] ?? null;
        $radius = $data['radius'] ?? $_POST['radius'] ?? 100; 
        $qr_hash = 'EVT-' . bin2hex(random_bytes(5)); 
        
        if ($jenis === 'umum') {
            $zona_target = null;
        }

        $stmt = $conn->prepare("INSERT INTO events (nama_event, tempat, pembicara, latitude, longitude, radius, waktu_event, status, jenis_event, zona_target, qr_hash) VALUES (?, ?, ?, ?, ?, ?, ?, 'mendatang', ?, ?, ?)");
        $stmt->execute([$nama, $tempat, $pembicara, $latitude, $longitude, $radius, $waktu, $jenis, $zona_target, $qr_hash]);
        echo json_encode(["status" => "success", "message" => "Event berhasil dibuat dengan status mendatang dan QR Code siap."]);
    }
    
    elseif ($action === 'create_user') {
        $nama = $data['nama_lengkap'] ?? $_POST['nama_lengkap'] ?? '';
        $porsi = $data['nomor_porsi'] ?? $_POST['nomor_porsi'] ?? '';
        $wa = $data['whatsapp'] ?? $_POST['whatsapp'] ?? '';
        $password = $data['password'] ?? $_POST['password'] ?? '';
        
        if (empty($nama) || empty($porsi) || empty($wa) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "Semua kolom wajib diisi"]);
            exit;
        }

        $cek = $conn->prepare("SELECT id FROM users WHERE nomor_porsi = ? OR whatsapp = ?");
        $cek->execute([$porsi, $wa]);
        if ($cek->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Nomor Porsi atau WhatsApp sudah terdaftar!"]);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';

        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, nomor_porsi, whatsapp, password, role, is_completed) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nama, $porsi, $wa, $hashed_password, $role]);
        
        $new_user_id = $conn->lastInsertId();
        
        echo json_encode([
            "status" => "success", 
            "message" => "Akun jamaah berhasil dibuat!",
            "user_id" => $new_user_id
        ]);
    }
    
    elseif ($action === 'get_users') {
        $stmt = $conn->query("
            SELECT 
                u.id, 
                u.gambar AS foto, 
                u.nama_lengkap, 
                u.nomor_porsi, 
                u.whatsapp AS nomor_telepon, 
                COALESCE(k.status_jamaah, 'aktif') AS status
            FROM users u
            LEFT JOIN keberangkatan k ON u.id = k.user_id
            ORDER BY u.nama_lengkap ASC
        ");
        
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'toggle_event') {
        $id = $data['id'] ?? $_POST['id'] ?? '';
        $status = $data['status'] ?? $_POST['status'] ?? '';
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(["status" => "success", "message" => "Status diubah."]);
    }
    
    elseif ($action === 'delete_event') {
        $id = $data['id'] ?? $_POST['id'] ?? '';
        $conn->prepare("DELETE FROM materials WHERE event_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM attendances WHERE event_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Event dihapus."]);
    }

    elseif ($action === 'get_materials') {
        $event_id = $data['event_id'] ?? $_POST['event_id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM materials WHERE event_id = ?");
        $stmt->execute([$event_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'upload_material') {
        $event_id = $_POST['event_id'] ?? $data['event_id'] ?? '';
        $judul = $_POST['judul'] ?? $data['judul'] ?? '';
        $konten = $_POST['konten'] ?? $data['konten'] ?? '';
        $file_path = null;

        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
            $filename = time() . "_" . basename($_FILES['file_materi']['name']);
            $target = "uploads/" . $filename;
            if (move_uploaded_file($_FILES['file_materi']['tmp_name'], $target)) {
                $file_path = $filename;
            }
        }

        $stmt = $conn->prepare("INSERT INTO materials (event_id, judul, konten, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$event_id, $judul, $konten, $file_path]);
        echo json_encode(["status" => "success", "message" => "Materi ditambahkan."]);
    }

    elseif ($action === 'delete_material') {
        $id = $data['id'] ?? $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Materi dihapus."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Internal Server Error",
        "message" => $e->getMessage()
    ]);
}
?>