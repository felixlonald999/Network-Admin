<?php
require("autoload.php");


$area = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';


// $stmt   = "SELECT * FROM `data_customer` WHERE DATE_FORMAT(tanggal_lahir, '%m-%d) = DATE_FORMAT(NOW(), '%m-%d') AND `area_dealer` = ?";
$stmt = "SELECT * FROM `data_customer` WHERE DATE_FORMAT(tanggal_lahir, '%m-%d') = DATE_FORMAT(NOW(), '%m-%d') AND `area_dealer` = ?";
$query = $conn->prepare($stmt);
$query->bind_param('s',  $area);
$query->execute();
$res = $query->get_result();
$row = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode($row);
?>