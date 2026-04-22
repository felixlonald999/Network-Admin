<?php
require("autoload.php");

$id = $_POST['id'];
$status = $_POST['status'];

$stmt = "UPDATE `salesman` SET `status` = '$status' WHERE `id` = '$id'";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));

if ($query) {
    echo "success";
} else {
    echo "error: " . mysqli_error($conn2);
}
?>