<?php
// api/koneksi.php

$host = getenv('waguri.kawaiihost.net');
$db   = getenv('tlaoqwq_simak');
$user = getenv('tlaoqwq_simak');
$pass = getenv('P@g=.zA,TiSU.5E');
$port = getenv('DB_PORT', 3306);

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $e->getMessage()
    ]);
    exit;
}
?>