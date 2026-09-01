<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Content-Type: application/json");
// header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/koneksi.php';

$nama = $_POST['nama'] ?? '';
$nomor_porsi = $_POST['nomor_porsi'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';
$password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : '';
$role = 'user';

try {
    $cek = $conn->prepare("SELECT id FROM users WHERE nomor_porsi = ? OR whatsapp = ?");
    $cek->execute([$nomor_porsi, $whatsapp]);

    if ($cek->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            "status" => "error",
            "status_code" => "Conflict",
            "message" => "Portion number or WhatsApp already registered!"
        ]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, nomor_porsi, whatsapp, password, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$nama, $nomor_porsi, $whatsapp, $password, $role])) {
        echo json_encode([
            "status" => "success",
            "message" => "Registration successful",
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "failed",
            "status_code" => "Internal Server Error",
            "message" => "Failed to register. Please try again."
        ]);
    }
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Bad Request",
        "message" => "Portion number already registered"
    ]);
}
?>