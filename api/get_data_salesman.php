<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("../proses/autoload.php");

$area   = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';

$response = array(
    'data_area' => array(),
    'data_salesman' => array(),
    'data_dealer_modal' => array() // Grouped by area
);

// Get distinct areas
$stmt       = "SELECT DISTINCT area FROM `salesman` WHERE area != '' AND jabatan = 'SHOP MANAGER' ORDER BY area ASC";
$query      = mysqli_query($conn2, $stmt);
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $response['data_area'][] = $row;
    }
}

// Get salesmen
$stmt           = "SELECT `salesman`.* FROM `salesman` JOIN `dealer` ON `salesman`.`kode_dealer` = `dealer`.`kode_dealer` WHERE `dealer`.`area` = '$area' AND `dealer`.`status_group` = 'M/D' AND `salesman`.`jabatan` = 'SHOP MANAGER' ORDER BY `salesman`.`status` ASC";
$query          = mysqli_query($conn2, $stmt);
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $response['data_salesman'][] = $row;
    }
}

// Get dealers for all distinct areas to populate the modal select group
// In the original file, it looped through $data_area and queried dealers for each.
foreach ($response['data_area'] as $area_item) {
    $current_area = $area_item['area'];
    $stmt_dealer = "SELECT * FROM `dealer` WHERE area = '$current_area' AND status_group = 'M/D' ORDER BY nama_dealer ASC";
    $query_dealer = mysqli_query($conn2, $stmt_dealer);
    
    $dealers_in_area = array();
    if ($query_dealer) {
        while ($row = mysqli_fetch_assoc($query_dealer)) {
            $dealers_in_area[] = $row;
        }
    }
    $response['data_dealer_modal'][$current_area] = $dealers_in_area;
}

echo json_encode($response);
?>
