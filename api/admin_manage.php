<?php
error_reporting(0);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/koneksi.php';

// Tangkap data dari JSON Axios atau $_POST biasa
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'get_events') {
        $stmt = $conn->query("
        SELECT id, nama_event AS eventName, tempat AS venue, pembicara AS speaker,
                   latitude, longitude, radius, waktu_event AS eventTime, status,
                   jenis_event AS eventType, zona_target AS targetZone, qr_hash AS qrHash
            FROM events ORDER BY id DESC
        ");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    
    elseif ($action === 'create_event') {
        $nama = $data['nama_event'] ?? $_POST['nama_event'] ?? '';
        $tempat = $data['tempat'] ?? $_POST['tempat'] ?? '';
        $pembicara = $data['pembicara'] ?? $_POST['pembicara'] ?? '';
        $jenis = $data['jenis_event'] ?? $_POST['jenis_event'] ?? 'umum';
        $zona_target = $data['zona_target'] ?? $_POST['zona_target'] ?? null;
        $waktu = $data['waktu_event'] ?? $_POST['waktu_event'] ?? date('Y-m-d H:i:s'); 
        
        $latitude = $data['latitude'] ?? $_POST['latitude'] ?? null;
        $longitude = $data['longitude'] ?? $_POST['longitude'] ?? null;
        $radius = $data['radius'] ?? $_POST['radius'] ?? 100; 
        $qr_hash = 'EVT-' . bin2hex(random_bytes(5)); 
        
        if ($jenis === 'umum') {
            $zona_target = null;
        }

        $stmt = $conn->prepare("INSERT INTO events (nama_event, tempat, pembicara, latitude, longitude, radius, waktu_event, status, jenis_event, zona_target, qr_hash) VALUES (?, ?, ?, ?, ?, ?, ?, 'mendatang', ?, ?, ?)");
        $stmt->execute([$nama, $tempat, $pembicara, $latitude, $longitude, $radius, $waktu, $jenis, $zona_target, $qr_hash]);
        echo json_encode(["status" => "success", "message" => "Event created successfully with pending status and QR Code ready."]);
    }
    
    elseif ($action === 'create_user') {
        $nama = $data['nama_lengkap'] ?? $_POST['nama_lengkap'] ?? '';
        $porsi = $data['nomor_porsi'] ?? $_POST['nomor_porsi'] ?? '';
        $wa = $data['whatsapp'] ?? $_POST['whatsapp'] ?? '';
        $password = $data['password'] ?? $_POST['password'] ?? '';
        
        if (empty($nama) || empty($porsi) || empty($wa) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "All fields are required"]);
            exit;
        }

        $cek = $conn->prepare("SELECT id FROM users WHERE nomor_porsi = ? OR whatsapp = ?");
        $cek->execute([$porsi, $wa]);
        if ($cek->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Number or WhatsApp already registered!"]);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';

        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, nomor_porsi, whatsapp, password, role, is_completed) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nama, $porsi, $wa, $hashed_password, $role]);
        
        $new_user_id = $conn->lastInsertId();
        
        echo json_encode([
            "status" => "success", 
            "message" => "Jamaah account created successfully!",
            "user_id" => $new_user_id
        ]);
    }
    
    elseif ($action === 'get_users') {
        $stmt = $conn->query("
            SELECT 
                u.id, 
                u.gambar AS PhotoUrl, 
                u.nama_lengkap AS fullName, 
                u.nomor_porsi AS portionNumber, 
                u.whatsapp AS PhoneNumber, 
                COALESCE(k.status_jamaah, 'aktif') AS status
            FROM users u
            LEFT JOIN keberangkatan k ON u.id = k.user_id
            ORDER BY u.nama_lengkap ASC
        ");
        
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'delete_user') {
        $user_id = $data['user_id'] ?? $_POST['user_id'] ?? '';
        if (empty($user_id)) {
            echo json_encode(["status" => "error", "message" => "User ID is required."]);
            exit;
        }
        $conn->prepare("DELETE FROM attendances WHERE user_id = ?")->execute([$user_id]);
        $conn->prepare("DELETE FROM keberangkatan WHERE user_id = ?")->execute([$user_id]);
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        echo json_encode(["status" => "success", "message" => "Jamaah account deleted successfully."]);
    }

    elseif ($action === 'toggle_event') {
        $id = $data['id'] ?? $_POST['id'] ?? '';
        $status = $data['status'] ?? $_POST['status'] ?? '';
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(["status" => "success", "message" => "Status updated successfully."]);
    }
    
    elseif ($action === 'delete_event') {
        $id = $data['id'] ?? $_POST['id'] ?? '';
        $conn->prepare("DELETE FROM materials WHERE event_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM attendances WHERE event_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Event deleted successfully."]);
    }

    elseif ($action === 'get_materials') {
        $event_id = $data['event_id'] ?? $_POST['event_id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM materials WHERE event_id = ?");
        $stmt->execute([$event_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'upload_material') {
        $event_id = $_POST['event_id'] ?? $data['event_id'] ?? '';
        $judul = $_POST['judul'] ?? $data['judul'] ?? '';
        $konten = $_POST['konten'] ?? $data['konten'] ?? '';
        $file_path = null;

        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
            $filename = time() . "_" . basename($_FILES['file_materi']['name']);
            $target = "uploads/" . $filename;
            if (move_uploaded_file($_FILES['file_materi']['tmp_name'], $target)) {
                $file_path = $filename;
            }
        }

        $stmt = $conn->prepare("INSERT INTO materials (event_id, judul, konten, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$event_id, $judul, $konten, $file_path]);
        echo json_encode(["status" => "success", "message" => "Material added successfully."]);
    }

    elseif ($action === 'delete_material') {
        $id = $data['id'] ?? $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Material deleted successfully."]);
    }

    elseif ($action === 'get_jamaah') {
        $user_id = $data['user_id'] ?? $_POST['user_id'] ?? '';
        if (empty($user_id)) {
            http_response_code(400);
            echo json_encode(["status" => "failed", "statusCode" => "Bad Request", "message" => "User ID is required."]);
            exit;
        }
 
        $stmtUser = $conn->prepare("
            SELECT id, nama_lengkap, nomor_porsi, whatsapp, role,
                   address, birthDate, birthPlace, companion, nama_mahram, education, experience,
                   fatherName, gender, healthCondition, is_completed, job,
                   positiveTrait, program, skill, subDistrict, zona, village,
                   referensi_nama, referensi_wa, referensi_asal, gambar
            FROM users WHERE id = ?
        ");
        $stmtUser->execute([$user_id]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
 
        if (!$user) {
            http_response_code(404);
            echo json_encode(["status" => "failed", "statusCode" => "Not Found", "message" => "User ID not found"]);
            exit;
        }
 
        $stmtDok = $conn->prepare("SELECT * FROM keberangkatan WHERE user_id = ? LIMIT 1");
        $stmtDok->execute([$user_id]);
        $dok = $stmtDok->fetch(PDO::FETCH_ASSOC);
 
        echo json_encode([
            "status" => "success",
            "data" => [
                "id" => $user['id'],
                "fullName" => $user['nama_lengkap'],
                "portionNumber" => $user['nomor_porsi'],
                "whatsapp" => $user['whatsapp'],
                "role" => $user['role'],
                "address" => $user['address'],
                "birthDate" => $user['birthDate'],
                "birthPlace" => $user['birthPlace'],
                "companion" => $user['companion'],
                "mahramName" => $user['nama_mahram'],
                "education" => $user['education'],
                "experience" => $user['experience'],
                "fatherName" => $user['fatherName'],
                "gender" => $user['gender'],
                "health" => $user['healthCondition'],
                "isCompleted" => (bool) $user['is_completed'],
                "job" => $user['job'],
                "contribution" => $user['positiveTrait'],
                "depature" => $user['program'],
                "expertise" => $user['skill'],
                "subDistrict" => $user['subDistrict'],
                "zone" => $user['zona'],
                "village" => $user['village'],
                "referenceName" => $user['referensi_nama'],
                "referencePhone" => $user['referensi_wa'],
                "referenceOrigin" => $user['referensi_asal'],
                "PhotoUrl" => $user['gambar'],
                "pilgrimStatus" => $dok['status_jamaah'] ?? null,
                "plotNumber" => $dok['plot'] ?? null,
                "batch" => $dok['kloter'] ?? null,
                "group" => $dok['rombongan'] ?? null,
                "team" => $dok['regu'] ?? null,
                "currPorsionPosition" => $dok['posisi_porsi'] ?? null,
                "currPorstionStatus" => $dok['status_porsi'] ?? null,
                "currPorsionPositionBackup" => $dok['porsi_cadangan'] ?? null,
                "currPorstionStatusBackup" => $dok['status_cadangan'] ?? null,
                "passport" => $dok['passport'] ?? null,
                "visa" => $dok['visa'] ?? null,
                "mutationStatus" => $dok['mutasi'] ?? null,
                "biometricStatus" => $dok['bio_metrik'] ?? null,
                "puskesmasStatus" => $dok['puskesmas'] ?? null,
                "mcuStatus" => $dok['mcu'] ?? null,
                "paymentStatus" => $dok['pelunasan'] ?? null,
                "googleFormStatus" => $dok['gform'] ?? null,
                "photoStatus" => $dok['foto'] ?? null,
                "spphStatus" => $dok['spph'] ?? null,
            ],
        ]);
    }

     elseif ($action === 'edit_users') {
        $user_id = $data['user_id'] ?? $_POST['user_id'] ?? '';
        if (empty($user_id)) {
            http_response_code(400);
            echo json_encode(["status" => "failed", "statusCode" => "Bad Request", "message" => "User ID is required."]);
            exit;
        }
 
        function toCsvAdmin($val) {
            if (is_array($val)) return implode(", ", $val);
            if (is_string($val)) return trim($val);
            return '';
        }
 
        function tentukanZonaAdmin($kecamatan) {
            $kecamatan = strtoupper(trim($kecamatan));
            $zonaA = ['CIKAMPEK', 'JATISARI', 'KOTABARU', 'PURWASARI', 'TIRTAMULYA'];
            $zonaB = ['BANYUSARI', 'CILAMAYA KULON', 'CILAMAYA WETAN', 'LEMAHABANG', 'MAJALAYA', 'RAWAMERTA', 'TELAGASARI', 'TEMPURAN'];
            $zonaC = ['CIAMPEL', 'KARAWANG TIMUR', 'KLARI'];
            $zonaD = ['KARAWANG BARAT', 'TELUKJAMBE BARAT', 'TELUKJAMBE TIMUR'];
            $zonaE = ['PANGKALAN', 'TEGALWARU'];
            $zonaF = ['BATUJAYA', 'CIBUAYA', 'CILEBAR', 'JAYAKERTA', 'KUTAWALUYA', 'PAKISJAYA', 'PEDES', 'RENGASDENGKLOK', 'TIRTAJAYA'];
            if (in_array($kecamatan, $zonaA)) return 'A';
            if (in_array($kecamatan, $zonaB)) return 'B';
            if (in_array($kecamatan, $zonaC)) return 'C';
            if (in_array($kecamatan, $zonaD)) return 'D';
            if (in_array($kecamatan, $zonaE)) return 'E';
            if (in_array($kecamatan, $zonaF)) return 'F';
            return null;
        }
 
        $conn->beginTransaction();
        try {
            // 1. Account + profile (users table) -- only touched if at
            // least one relevant field was sent, so a departure-only edit
            // doesn't blank out the profile.
            $profileFields = ['fullName', 'portionNumber', 'whatsapp', 'fatherName', 'birthDate', 'birthPlace',
                'address', 'subDistrict', 'village', 'gender', 'education', 'job', 'experience', 'depature',
                'companion', 'mahramName', 'health', 'contribution', 'expertise', 'referenceName',
                'referencePhone', 'referenceOrigin', 'profileImage', 'isCompleted'];
            $touchesProfile = false;
            foreach ($profileFields as $f) {
                if (array_key_exists($f, $data)) { $touchesProfile = true; break; }
            }
 
            if ($touchesProfile) {
                $nama_lengkap = $data['fullName'] ?? null;
                $nomor_porsi = $data['portionNumber'] ?? null;
                $whatsapp = $data['whatsapp'] ?? null;
                $fatherName = $data['fatherName'] ?? '';
                $birthDate = $data['birthDate'] ?? '';
                $birthPlace = $data['birthPlace'] ?? '';
                $address = $data['address'] ?? '';
                $subDistrict = $data['subDistrict'] ?? '';
                $village = $data['village'] ?? '';
                $gender = $data['gender'] ?? '';
                $education = $data['education'] ?? '';
                $job = $data['job'] ?? '';
                $experience = $data['experience'] ?? '';
                $program = $data['depature'] ?? '';
                $companion = $data['companion'] ?? '';
                $nama_mahram = $data['mahramName'] ?? '';
                $healthCondition = isset($data['health']) ? toCsvAdmin($data['health']) : '';
                $positiveTrait = isset($data['contribution']) ? toCsvAdmin($data['contribution']) : '';
                $skill = isset($data['expertise']) ? toCsvAdmin($data['expertise']) : '';
                $referensi_nama = $data['referenceName'] ?? '';
                $referensi_wa = $data['referencePhone'] ?? '';
                $referensi_asal = $data['referenceOrigin'] ?? '';
                $gambar = $data['profileImage'] ?? null;
                $is_completed = !empty($data['isCompleted']) ? 1 : 0;
                $zona = $subDistrict ? tentukanZonaAdmin($subDistrict) : null;
 
                if (!empty($nomor_porsi)) {
                    $cek = $conn->prepare("SELECT id FROM users WHERE nomor_porsi = ? AND id != ?");
                    $cek->execute([$nomor_porsi, $user_id]);
                    if ($cek->rowCount() > 0) throw new Exception("Porsi number is already used by another user.");
                }
 
                $sets = [];
                $params = [];
                $maybe = [
                    'nama_lengkap' => $nama_lengkap, 'nomor_porsi' => $nomor_porsi, 'whatsapp' => $whatsapp,
                ];
                foreach ($maybe as $col => $val) {
                    if ($val !== null) { $sets[] = "$col = ?"; $params[] = $val; }
                }
                $sets = array_merge($sets, [
                    "address = ?", "birthDate = ?", "birthPlace = ?", "companion = ?", "nama_mahram = ?",
                    "education = ?", "experience = ?", "fatherName = ?", "gender = ?", "healthCondition = ?",
                    "is_completed = ?", "job = ?", "positiveTrait = ?", "program = ?", "skill = ?",
                    "subDistrict = ?", "zona = ?", "village = ?", "referensi_nama = ?", "referensi_wa = ?",
                    "referensi_asal = ?",
                ]);
                $params = array_merge($params, [
                    $address, $birthDate, $birthPlace, $companion, $nama_mahram, $education, $experience,
                    $fatherName, $gender, $healthCondition, $is_completed, $job, $positiveTrait, $program,
                    $skill, $subDistrict, $zona, $village, $referensi_nama, $referensi_wa, $referensi_asal,
                ]);
                if ($gambar) { $sets[] = "gambar = ?"; $params[] = $gambar; }
                $params[] = $user_id;
 
                $conn->prepare("UPDATE users SET " . implode(", ", $sets) . " WHERE id = ?")->execute($params);
            }
 
            // 2. Departure document (keberangkatan table) -- only touched
            // if at least one relevant field was sent.
            $dokFields = ['pilgrimStatus', 'plotNumber', 'batch', 'group', 'team', 'currPorsionPosition',
                'currPorstionStatus', 'currPorsionPositionBackup', 'currPorstionStatusBackup', 'passport',
                'visa', 'mutationStatus', 'biometricStatus', 'puskesmasStatus', 'mcuStatus', 'paymentStatus',
                'googleFormStatus', 'photoStatus', 'spphStatus'];
            $touchesDok = false;
            foreach ($dokFields as $f) {
                if (array_key_exists($f, $data)) { $touchesDok = true; break; }
            }
 
            if ($touchesDok) {
                $status_jamaah = $data['pilgrimStatus'] ?? 'aktif';
                $plot = $data['plotNumber'] ?? '';
                $kloter = $data['batch'] ?? '';
                $rombongan = $data['group'] ?? '';
                $regu = $data['team'] ?? '';
                $posisi_porsi = $data['currPorsionPosition'] ?? '';
                $status_porsi = $data['currPorstionStatus'] ?? '';
                $porsi_cadangan = $data['currPorsionPositionBackup'] ?? '';
                $status_cadangan = $data['currPorstionStatusBackup'] ?? '';
                $passport = $data['passport'] ?? '';
                $visa = $data['visa'] ?? '';
                $mutasi = $data['mutationStatus'] ?? 'menunggu';
                $bio_metrik = $data['biometricStatus'] ?? 'menunggu';
                $puskesmas = $data['puskesmasStatus'] ?? 'menunggu';
                $mcu = $data['mcuStatus'] ?? 'menunggu';
                $pelunasan = $data['paymentStatus'] ?? 'menunggu';
                $gform = $data['googleFormStatus'] ?? 'menunggu';
                $foto = $data['photoStatus'] ?? 'menunggu';
                $spph = $data['spphStatus'] ?? 'menunggu';
 
                $cekDok = $conn->prepare("SELECT id FROM keberangkatan WHERE user_id = ?");
                $cekDok->execute([$user_id]);
 
                if ($cekDok->fetch()) {
                    $sql = "UPDATE keberangkatan SET
                            status_jamaah=?, plot=?, kloter=?, rombongan=?, regu=?,
                            posisi_porsi=?, status_porsi=?, porsi_cadangan=?, status_cadangan=?,
                            passport=?, visa=?, mutasi=?, bio_metrik=?, puskesmas=?,
                            mcu=?, pelunasan=?, gform=?, foto=?, spph=?
                            WHERE user_id=?";
                    $conn->prepare($sql)->execute([
                        $status_jamaah, $plot, $kloter, $rombongan, $regu,
                        $posisi_porsi, $status_porsi, $porsi_cadangan, $status_cadangan,
                        $passport, $visa, $mutasi, $bio_metrik, $puskesmas,
                        $mcu, $pelunasan, $gform, $foto, $spph, $user_id
                    ]);
                } else {
                    $sql = "INSERT INTO keberangkatan (
                                user_id, status_jamaah, plot, kloter, rombongan, regu,
                                posisi_porsi, status_porsi, porsi_cadangan, status_cadangan,
                                passport, visa, mutasi, bio_metrik, puskesmas,
                                mcu, pelunasan, gform, foto, spph
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $conn->prepare($sql)->execute([
                        $user_id, $status_jamaah, $plot, $kloter, $rombongan, $regu,
                        $posisi_porsi, $status_porsi, $porsi_cadangan, $status_cadangan,
                        $passport, $visa, $mutasi, $bio_metrik, $puskesmas,
                        $mcu, $pelunasan, $gform, $foto, $spph
                    ]);
                }
            }
 
            $conn->commit();
            echo json_encode(["status" => "success", "message" => "Jamaah data updated successfully."]);
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(400);
            echo json_encode(["status" => "failed", "statusCode" => "Bad Request", "message" => $e->getMessage()]);
        }
    }
 
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "status_code" => "Internal Server Error",
        "message" => $e->getMessage()
    ]);
}
?>