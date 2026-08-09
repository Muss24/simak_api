<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Tangani Preflight Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'koneksi.php';

$nomor_porsi = $_POST['nomor_porsi'] ?? '';
$password = $_POST['password'] ?? '';

// PERBAIKAN: Tambahkan nomor_porsi dan qr_code_hash di dalam SELECT
$stmt = $conn->prepare("SELECT id, nama_lengkap, nomor_porsi, role, password, qr_code_hash FROM users WHERE nomor_porsi = ?");
$stmt->execute([$nomor_porsi]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    // Jangan kembalikan password ke frontend
    unset($user['password']); 
    
    echo json_encode([
        "status" => "success",
        "message" => "Login berhasil",
        "data" => $user // Sekarang data ini sudah memuat qr_code_hash
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Nomor Porsi atau Password salah"]);
}
?>