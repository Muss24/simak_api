<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/koneksi.php';

// 1. TANGKAP PAYLOAD JSON DARI REACT
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Jika ternyata request bukan JSON (fallback ke $_POST)
if (!$data) {
    $data = $_POST;
}

// 2. AMBIL ID USER DARI PAYLOAD JSON
$user_id = $data['usr_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(["status" => "error", "message" => "User ID tidak valid"]);
    exit;
}

// 3. AMBIL DATA TEKS BIASA
$address = $data['address'] ?? '';
$birthDate = $data['birthDate'] ?? '';
$birthPlace = $data['birthPlace'] ?? '';
$companion = $data['companion'] ?? '';
$education = $data['education'] ?? '';
$experience = $data['experience'] ?? '';
$fatherName = $data['fatherName'] ?? '';
$gender = $data['gender'] ?? '';
$is_completed = !empty($data['is_completed']) ? 1 : 0; // Pastikan boolean tersimpan sebagai 1 atau 0 di database
$job = $data['job'] ?? '';
$program = $data['program'] ?? '';
$subDistrict = $data['subDistrict'] ?? '';
$village = $data['village'] ?? '';

// 4. AMBIL DATA ARRAY DAN JADIKAN STRING (KOMA SEPARATOR)
// Karena JSON yang dikirim sudah berupa array murni, kita tinggal mengecek apakah itu array lalu meng-implode-nya
$healthCondition = (isset($data['healthCondition']) && is_array($data['healthCondition'])) ? implode(", ", $data['healthCondition']) : '';
$positiveTrait = (isset($data['positiveTrait']) && is_array($data['positiveTrait'])) ? implode(", ", $data['positiveTrait']) : '';
$skill = (isset($data['skill']) && is_array($data['skill'])) ? implode(", ", $data['skill']) : '';

// 5. HANDLE URL GAMBAR (jika ada)
$gambar = $data['gambar'] ?? null;

try {
    if ($gambar) {
        $sql = "UPDATE users SET 
                address=?, birthDate=?, birthPlace=?, companion=?, education=?, 
                experience=?, fatherName=?, gender=?, healthCondition=?, is_completed=?, 
                job=?, positiveTrait=?, program=?, skill=?, subDistrict=?, village=?, gambar=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $address, $birthDate, $birthPlace, $companion, $education, 
            $experience, $fatherName, $gender, $healthCondition, $is_completed, 
            $job, $positiveTrait, $program, $skill, $subDistrict, $village, $gambar, 
            $user_id
        ]);
    } else {
        $sql = "UPDATE users SET 
                address=?, birthDate=?, birthPlace=?, companion=?, education=?, 
                experience=?, fatherName=?, gender=?, healthCondition=?, is_completed=?, 
                job=?, positiveTrait=?, program=?, skill=?, subDistrict=?, village=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $address, $birthDate, $birthPlace, $companion, $education, 
            $experience, $fatherName, $gender, $healthCondition, $is_completed, 
            $job, $positiveTrait, $program, $skill, $subDistrict, $village, 
            $user_id
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Data profil berhasil diperbarui!"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal update data: " . $e->getMessage()]);
}
?>