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
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 1000;  // Default 1000 row per request
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;   // Default mulai dari awal
$area = isset($_GET['area']) ? $_GET['area'] : ''; // Default area dealer
// Query untuk mengambil data
$query = "SELECT dc.*, f.nama_dealer, f.tipe_motor,f.tanggal_beli_motor
          FROM `data_customer` dc 
          LEFT JOIN `faktur` f ON dc.id_faktur_terakhir = f.id 
          WHERE dc.id_faktur_terakhir IS NOT NULL
            AND f.tanggal_beli_motor < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            AND f.area_dealer = ?
          ORDER BY dc.RO_SALES DESC, dc.RO_SERVICE DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sii",$area, $limit, $offset);
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
