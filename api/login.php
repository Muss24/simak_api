<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Content-Type: application/json");
// header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Tangani Preflight Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/koneksi.php';

$nomor_porsi = $_POST['nomor_porsi'] ?? '';
$password = $_POST['password'] ?? '';

// Ambil langsung kolom is_profile_complete dari tabel users
$stmt = $conn->prepare("SELECT id, nama_lengkap, nomor_porsi, role, password, qr_code_hash, is_profile_complete FROM users WHERE nomor_porsi = ?");
$stmt->execute([$nomor_porsi]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    unset($user['password']); 
    
    // Perhatikan tambahan kurung siku [ ] untuk membungkus object
    echo json_encode([[
        "status" => "success",
        "message" => "login successfully",
        "data" => $user 
    ]]);
} else {
    // Dibungkus juga untuk respons error agar seragam
    echo json_encode([[
        "status" => "error",
        "message" => "Nomor Porsi atau Password salah"
    ]]);
}
?>