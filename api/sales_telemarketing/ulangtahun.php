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

// Ambil parameter pagination
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 300;  // Default 1000 row per request
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;   // Default mulai dari awal
$area_dealer = isset($_GET['area_dealer']) ? $_GET['area_dealer'] : '';
// Query untuk mengambil data
$query = "SELECT *
          FROM `data_customer` dc 
          where dc.tanggal_lahir IS NOT NULL
            AND MONTH(dc.tanggal_lahir) = MONTH(CURDATE()) and area_dealer = ?
          ORDER BY dc.ro_sales DESC, dc. ro_service DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $area_dealer, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

// Output JSON
echo json_encode([
    "status" => "success",
    "limit" => $limit,
    "offset" => $offset,
    "data" => $data
]);

// Tutup koneksi
$conn->close();

?>
