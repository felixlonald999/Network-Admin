<?php
require("autoload.php");

$stmt = "SELECT s.`nomor_rangka`, f.`tanggal_beli_motor` FROM `service` AS s LEFT JOIN `faktur` AS f ON s.`nomor_rangka` = f.`nomor_rangka`";
$result = $conn->query($stmt);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$row = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($row);
?>