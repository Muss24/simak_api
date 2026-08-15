<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/koneksi.php';

try {
    // Ambil data dari masing-masing tabel master
    $kecamatan = $conn->query("SELECT * FROM kecamatan ORDER BY nama_kecamatan ASC")->fetchAll(PDO::FETCH_ASSOC);
    $referensi = $conn->query("SELECT * FROM asal_referensi ORDER BY nama_asal ASC")->fetchAll(PDO::FETCH_ASSOC);
    $pekerjaan = $conn->query("SELECT * FROM pekerjaan ORDER BY nama_pekerjaan ASC")->fetchAll(PDO::FETCH_ASSOC);
    $kemampuan = $conn->query("SELECT * FROM kemampuan ORDER BY nama_kemampuan ASC")->fetchAll(PDO::FETCH_ASSOC);
    $hal_positif = $conn->query("SELECT * FROM hal_positif ORDER BY nama_hal_positif ASC")->fetchAll(PDO::FETCH_ASSOC);
    $kesehatan = $conn->query("SELECT * FROM kondisi_kesehatan ORDER BY nama_kondisi ASC")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "kecamatan" => $kecamatan,
            "asal_referensi" => $referensi,
            "pekerjaan" => $pekerjaan,
            "kemampuan" => $kemampuan,
            "hal_positif" => $hal_positif,
            "kondisi_kesehatan" => $kesehatan
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal memuat master data: " . $e->getMessage()
    ]);
}
?>