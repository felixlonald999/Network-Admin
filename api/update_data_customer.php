<?php
require("autoload.php");

$no_ktp = isset($_GET['ktp_customer']) ? $_GET['ktp_customer'] : null;
$no_hp = isset($_GET['no_hp']) ? $_GET['no_hp'] : null;
$status_followup_terakhir = isset($_GET['status_followup_terakhir']) ? $_GET['status_followup_terakhir'] : null;

$query = "UPDATE `data_customer`
    SET nohp2_customer = ?, status_followup_terakhir = ? 
    WHERE ktp_customer = ?";
$stmt_update = $conn->prepare($query);
$stmt_update->bind_param("sss", $no_hp, $status_followup_terakhir, $no_ktp);
$stmt_update->execute();

if ($stmt_update->execute()) {
    echo "Update berhasil!";
} else {
    echo "Error: " . $conn->error;
}

?>