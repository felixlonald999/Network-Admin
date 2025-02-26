<?php
require("autoload.php");

$stmt   = "SELECT * FROM `faktur` WHERE YEAR(tanggal_beli_motor) = 2024";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
$row    = mysqli_fetch_all($query, MYSQLI_ASSOC);

echo json_encode($row);
?>