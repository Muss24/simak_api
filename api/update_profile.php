<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/koneksi.php';

// 1. TANGKAP PAYLOAD JSON DARI REACT
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    $data = $_POST;
}

// 2. AMBIL ID USER
$user_id = $data['user_id'] ?? '';

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Bad Request",
        "message" => "User ID tidak valid atau tidak ditemukan"
    ]);
    exit;
}

// 3. AMBIL DATA TEKS
$address = $data['address'] ?? '';
$birthDate = $data['birthDate'] ?? '';
$birthPlace = $data['birthPlace'] ?? '';
$companion = $data['companion'] ?? '';
$nama_mahram = $data['nama_mahram'] ?? '';
$education = $data['education'] ?? '';
$experience = $data['experience'] ?? '';
$fatherName = $data['fatherName'] ?? '';
$gender = $data['gender'] ?? '';
$is_completed = !empty($data['is_completed']) ? 1 : 0;
$job = $data['job'] ?? '';
$program = $data['program'] ?? '';
$subDistrict = $data['subDistrict'] ?? '';
$village = $data['village'] ?? '';

// --- TAMBAHAN DATA REFERENSI ---
$referensi_nama = $data['referensiNama'] ?? '';
$referensi_wa = $data['referensiWa'] ?? '';
$referensi_asal = $data['referensiAsal'] ?? '';

// --- LOGIKA PENENTUAN ZONA BERDASARKAN KECAMATAN ---
function tentukanZona($kecamatan)
{
    // Hilangkan spasi berlebih dan ubah ke huruf besar agar pengecekan kebal typo
    $kecamatan = strtoupper(trim($kecamatan));

    $zonaA = ['CIKAMPEK', 'JATISARI', 'KOTABARU', 'PURWASARI', 'TIRTAMULYA'];
    $zonaB = ['BANYUSARI', 'CILAMAYA KULON', 'CILAMAYA WETAN', 'LEMAHABANG', 'MAJALAYA', 'RAWAMERTA', 'TELAGASARI', 'TEMPURAN'];
    $zonaC = ['CIAMPEL', 'KARAWANG TIMUR', 'KLARI'];
    $zonaD = ['KARAWANG BARAT', 'TELUKJAMBE BARAT', 'TELUKJAMBE TIMUR'];
    $zonaE = ['PANGKALAN', 'TEGALWARU'];
    $zonaF = ['BATUJAYA', 'CIBUAYA', 'CILEBAR', 'JAYAKERTA', 'KUTAWALUYA', 'PAKISJAYA', 'PEDES', 'RENGASDENGKLOK', 'TIRTAJAYA'];

    if (in_array($kecamatan, $zonaA))
        return 'A';
    if (in_array($kecamatan, $zonaB))
        return 'B';
    if (in_array($kecamatan, $zonaC))
        return 'C';
    if (in_array($kecamatan, $zonaD))
        return 'D';
    if (in_array($kecamatan, $zonaE))
        return 'E';
    if (in_array($kecamatan, $zonaF))
        return 'F';

    return null; // Jika kecamatan tidak terdaftar di list
}

// Eksekusi fungsi untuk mendapatkan zona
$zona = tentukanZona($subDistrict);

// 4. AMBIL DATA ARRAY
$healthCondition = (isset($data['healthCondition']) && is_array($data['healthCondition'])) ? implode(", ", $data['healthCondition']) : '';
$positiveTrait = (isset($data['positiveTrait']) && is_array($data['positiveTrait'])) ? implode(", ", $data['positiveTrait']) : '';
$skill = (isset($data['skill']) && is_array($data['skill'])) ? implode(", ", $data['skill']) : '';

// 5. HANDLE URL GAMBAR
$gambar = $data['profileImage'] ?? $data['gambar'] ?? null;

try {
    // Tambahkan field `zona=?` ke dalam query SQL
    if ($gambar) {
        $sql = "UPDATE users SET 
                address=?, birthDate=?, birthPlace=?, companion=?,nama_mahram=?, education=?, 
                experience=?, fatherName=?, gender=?, healthCondition=?, is_completed=?, 
                job=?, positiveTrait=?, program=?, skill=?, subDistrict=?, zona=?, village=?, referensi_nama=?, referensi_wa=?, referensi_asal=?, gambar=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $address,
            $birthDate,
            $birthPlace,
            $companion,
            $nama_mahram,
            $education,
            $experience,
            $fatherName,
            $gender,
            $healthCondition,
            $is_completed,
            $job,
            $positiveTrait,
            $program,
            $skill,
            $subDistrict,
            $zona,
            $village,
            $referensi_nama,
            $referensi_wa,
            $referensi_asal,
            $gambar,
            $user_id
        ]);
    } else {
        $sql = "UPDATE users SET 
                address=?, birthDate=?, birthPlace=?, companion=?,nama_mahram=?, education=?, 
                experience=?, fatherName=?, gender=?, healthCondition=?, is_completed=?, 
                job=?, positiveTrait=?, program=?, skill=?, subDistrict=?, zona=?, village=?, referensi_nama=?, referensi_wa=?, referensi_asal=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $address,
            $birthDate,
            $birthPlace,
            $companion,
            $nama_mahram,
            $education,
            $experience,
            $fatherName,
            $gender,
            $healthCondition,
            $is_completed,
            $job,
            $positiveTrait,
            $program,
            $skill,
            $subDistrict,
            $zona,
            $village,
            $referensi_nama,
            $referensi_wa,
            $referensi_asal,
            $user_id
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Data profil berhasil diperbarui!"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Internal Server Error",
        "message" => "Gagal update data: " . $e->getMessage()
    ]);
}
?>