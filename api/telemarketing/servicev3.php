<?php

require("../autoload.php");
header('Content-Type: application/json');

// Konfigurasi database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "yamahast_data";

// Fungsi untuk mendapatkan data service berdasarkan array nomor rangka
function getServiceDataBatch($conn, $norangkas = []) {
    try {
        if (empty($norangkas)) {
            return [
                'status' => 'error',
                'message' => "No frame numbers provided"
            ];
        }

        $placeholders = implode(',', array_fill(0, count($norangkas), '?'));

        $sql = "SELECT s.nomor_rangka, 
                       s.tanggal_terakhir_service, 
                       sm.ro_service 
                FROM `service` s
                JOIN `summary` sm ON s.nomor_rangka = sm.nomor_rangka
                WHERE s.nomor_rangka IN ($placeholders)";

        $stmt = $conn->prepare($sql);

        foreach ($norangkas as $index => $norangka) {
            $stmt->bindValue($index + 1, $norangka);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert to associative array with nomor_rangka as key
        $data = [];
        foreach ($result as $row) {
            $data[$row['nomor_rangka']] = $row;
        }

        return [
            'status' => 'success',
            'data' => $data,
            'count' => count($data)
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => "Database error: " . $e->getMessage()
        ];
    }
}

try {
    // Buat koneksi database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Ambil input JSON dari body request
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);

    if (!isset($input['norangka']) || !is_array($input['norangka'])) {
        echo json_encode([
            'status' => 'error',
            'message' => "Missing or invalid 'norangka' array"
        ]);
        exit;
    }

    // Jalankan batch query
    $response = getServiceDataBatch($conn, $input['norangka']);

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => "Connection failed: " . $e->getMessage()
    ]);
}
?>