<?php
require("autoload.php");

$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$area = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';

$stmt   = "SELECT * FROM `service` WHERE YEAR(tanggal_terakhir_service) = ? AND MONTH(tanggal_terakhir_service) = ? AND `area_dealer` = ?";
$query = $conn->prepare($stmt);
$query->bind_param('iis', $tahun, $bulan, $area);
$query->execute();
$res = $query->get_result();
$row = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode($row);
?>