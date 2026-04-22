<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("../proses/autoload.php");

$area   = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';
$tabel  = isset($_GET['tabel']) ? $_GET['tabel'] : 'crm';

$response = array(
    'data_area' => array(),
    'data_dealer' => array()
);

// Get distinct areas
$stmt       = "SELECT DISTINCT area FROM `dealer` WHERE area != '' ORDER BY area ASC";
$query      = mysqli_query($conn, $stmt);
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $response['data_area'][] = $row;
    }
}

// Get dealers based on tabel and area
if ($tabel == 'crm') {
    $stmt           = "SELECT * FROM `dealer` WHERE area = '$area'";
    $query          = mysqli_query($conn2, $stmt);
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $response['data_dealer'][] = $row;
        }
    }
} elseif ($tabel == 'dealer') {
    $stmt           = "SELECT * FROM `dealer` WHERE area = '$area'";
    $query          = mysqli_query($conn3, $stmt);
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $response['data_dealer'][] = $row;
        }
    }
} elseif ($tabel == 'sigap_legal') {
    $stmt           = "SELECT * FROM `dealer` WHERE area = '$area'";
    $query          = mysqli_query($conn4, $stmt);
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $response['data_dealer'][] = $row;
        }
    }
}

echo json_encode($response);
?>
