<?php

require("../autoload.php");

header('Content-Type: application/json');

// Konfigurasi Database
$host   = 'localhost';
$user   = 'root';
$pass   = '';
$db     = 'yamahast_data';

// Koneksi ke Database
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi database gagal: " . $conn->connect_error]));
}


// Query untuk mengambil data
$query = "SELECT count(id) as jumlah, 
            COUNT(CASE WHEN tanggal_lahir IS NOT NULL AND tanggal_lahir != '' THEN 1 END) AS jumlah_ultah,
            COUNT(CASE WHEN id_faktur_terakhir IS NOT NULL AND id_faktur_terakhir != '' THEN 1 END) AS jumlah_service
          FROM `data_customer` 
           ";

$stmt = $conn->prepare($query);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

// Output JSON
echo json_encode([
    "status" => "success",
    "data" => $data
]);

// Tutup koneksi
$conn->close();

?>
