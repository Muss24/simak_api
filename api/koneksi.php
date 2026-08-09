<?php
// api/koneksi.php

$host = getenv('DB_HOST'); // Diisi dari Vercel (Host Waguri)
$db   = getenv('DB_NAME'); // Diisi dari Vercel (Nama DB Waguri)
$user = getenv('DB_USER'); // Diisi dari Vercel (User DB Waguri)
$pass = getenv('DB_PASS'); // Diisi dari Vercel (Password DB Waguri)
$port = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal terhubung ke database Waguri: " . $e->getMessage()
    ]);
    exit;
}
?>