<?php
require("autoload.php");

$id = $_POST['id'];
$kode_dealer = $_POST['kode_dealer'];
$area = $_POST['area'];

$stmt = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));
$data_dealer = mysqli_fetch_all($query, MYSQLI_ASSOC);

$nama_dealer = $data_dealer[0]['nama_dealer'];

$stmt = "UPDATE `salesman` SET `kode_dealer` = '$kode_dealer', `area` = '$area', `nama_dealer` = '$nama_dealer' WHERE `id` = '$id'";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));

header("Location: ../salesman.php?area=$area");
?>