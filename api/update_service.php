<?php
require("autoload.php");

$stmt = "UPDATE `service` AS s
    JOIN `faktur` AS f 
    ON s.nomor_rangka = f.nomor_rangka
    SET s.tanggal_beli_motor = f.tanggal_beli_motor";

if ($conn->query($stmt) === TRUE) {
    echo "Update berhasil!";
} else {
    echo "Error: " . $conn->error;
}

?>