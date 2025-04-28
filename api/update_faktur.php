<?php
require("autoload.php");

ini_set('memory_limit', '4096M');
$stmt = "UPDATE `faktur` AS f
    JOIN `faktur_temporary` AS ft 
    ON f.nomor_rangka = ft.nomor_rangka
    SET f.kabupaten = ft.kabupaten,
    f.pekerjaan = ft.pekerjaan,
    f.pendidikan = ft.pendidikan,
    f.tenor_kredit = ft.tenor_kredit";

if ($conn->query($stmt) === TRUE) {
    echo "Update berhasil!";
} else {
    echo "Error: " . $conn->error;
}

?>