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
$qr_code_hash = 'USR-' . bin2hex(random_bytes(8));

try {
    $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, nomor_porsi, whatsapp, password, role, qr_code_hash) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$nama, $nomor_porsi, $whatsapp, $password, $role, $qr_code_hash])) {
        echo json_encode([
            "status" => "success",
            "message" => "Registrasi berhasil",
            "qr_code" => $qr_code_hash
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "failed",
            "code" => 500,
            "status_code" => "Internal Server Error",
            "message" => "Gagal melakukan registrasi. Silakan coba lagi."
        ]);
    }
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "failed",
        "code" => 400,
        "status_code" => "Bad Request",
        "message" => "Nomor porsi sudah terdaftar"
    ]);
}
?>