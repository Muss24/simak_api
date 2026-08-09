<?php
// api/koneksi.php

// Mendukung variabel dari Railway (MYSQLHOST) atau kustom Vercel (DB_HOST)
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST');
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME');
$user = getenv('MYSQLUSER') ?: getenv('DB_USER');
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS');
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal terhubung ke database Railway: " . $e->getMessage()
    ]);
    exit;
}
?>