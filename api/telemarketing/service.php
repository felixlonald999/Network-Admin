<?php

require("../autoload.php");
header('Content-Type: application/json');

// Konfigurasi database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "yamahast_data";



// Fungsi untuk mendapatkan data service berdasarkan nomor rangka
function getServiceData($conn, $norangka = null) {
    try {
        $sql = "SELECT s.nomor_rangka, 
                s.tanggal_terakhir_service, sm.ro_service as ro_service FROM `service` s
    JOIN `summary` sm ON s.nomor_rangka = sm.nomor_rangka 
                ";
        
        // Jika nomor rangka diberikan, filter berdasarkan nomor rangka
        if ($norangka) {
            $sql .= " WHERE s.nomor_rangka = :norangka";
        }
        
        
        $sql .= " LIMIT 5000";
        
        $stmt = $conn->prepare($sql);
        
        if ($norangka) {
            $stmt->bindParam(':norangka', $norangka);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $result,
            'count' => count($result)
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => "Database error: " . $e->getMessage()
        ];
    }
}

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
                s.tanggal_terakhir_service, sm.ro_service as ro_service FROM `service` s
    JOIN `summary` sm ON s.nomor_rangka = sm.nomor_rangka
                WHERE s.nomor_rangka IN ($placeholders)
                ";
        
        $stmt = $conn->prepare($sql);
        
        foreach ($norangkas as $index => $norangka) {
            $stmt->bindValue($index + 1, $norangka);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to associative array with nomor_rangka as key for easier lookup
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
    
    // Periksa apakah ada parameter nomor rangka
    if (isset($_GET['norangka'])) {
        // Jika parameter adalah array (multiple nomor rangka)
        if (is_array($_GET['norangka'])) {
            $response = getServiceDataBatch($conn, $_GET['norangka']);
        } else {
            // Jika parameter adalah string tunggal
            $response = getServiceData($conn, $_GET['norangka']);
        }
    } else {
        // Jika tidak ada parameter, ambil semua data
        $response = getServiceData($conn);
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => "Connection failed: " . $e->getMessage()
    ]);
}
?>