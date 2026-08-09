<?php
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require 'koneksi.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'get_events') {
        $stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    
    elseif ($action === 'create_event') {
        $nama = $_POST['nama_event'];
        $stmt = $pdo->prepare("INSERT INTO events (nama_event, status) VALUES (?, 'selesai')");
        $stmt->execute([$nama]);
        echo json_encode(["status" => "success", "message" => "Event dibuat."]);
    } 
    
    elseif ($action === 'toggle_event') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(["status" => "success", "message" => "Status diubah."]);
    }
    
    elseif ($action === 'delete_event') {
        $id = $_POST['id'];
        // Hapus materi & absen terkait lebih dulu (Foreign Key Constraint)
        $pdo->prepare("DELETE FROM materials WHERE event_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM attendances WHERE event_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Event dihapus."]);
    }

    elseif ($action === 'get_materials') {
        $event_id = $_POST['event_id'];
        $stmt = $pdo->prepare("SELECT * FROM materials WHERE event_id = ?");
        $stmt->execute([$event_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'upload_material') {
        $event_id = $_POST['event_id'];
        $judul = $_POST['judul'];
        $konten = $_POST['konten'] ?? '';
        $file_path = null;

        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
            $filename = time() . "_" . basename($_FILES['file_materi']['name']);
            $target = "uploads/" . $filename;
            if (move_uploaded_file($_FILES['file_materi']['tmp_name'], $target)) {
                $file_path = $filename;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO materials (event_id, judul, konten, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$event_id, $judul, $konten, $file_path]);
        echo json_encode(["status" => "success", "message" => "Materi ditambahkan."]);
    }

    elseif ($action === 'delete_material') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Materi dihapus."]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>