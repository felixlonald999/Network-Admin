<?php
require("../autoload.php");
header('Content-Type: application/json');
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




$stmt   = "SELECT s.nomor_rangka, 
                s.tanggal_terakhir_service, sm.ro_service as ro_service FROM `service` s
    JOIN `summary` sm ON s.nomor_rangka = sm.nomor_rangka";
$query = $conn->prepare($stmt);
// $query->bind_param('iis', $tahun, $bulan, $area);
$query->execute();
$res = $query->get_result();
$row = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode($row);
?>