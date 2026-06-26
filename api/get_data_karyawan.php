<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("../proses/autoload.php");

$area = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';

$response = array(
    'data_area' => array(),
    'data_karyawan' => array()
);

// Mendapatkan list area yang unik dari tabel karyawan
$stmt = "SELECT DISTINCT area FROM `karyawan` WHERE area != '' ORDER BY area ASC";
$query = mysqli_query($conn2, $stmt);
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $response['data_area'][] = $row;
    }
}

// Mendapatkan data karyawan pada area terpilih tanpa filter jabatan tertentu
$stmt_karyawan = "SELECT * FROM `karyawan` WHERE `area` = '$area' ORDER BY `status` ASC, `nama` ASC";
$query_karyawan = mysqli_query($conn2, $stmt_karyawan);
if ($query_karyawan) {
    while ($row = mysqli_fetch_assoc($query_karyawan)) {
        $response['data_karyawan'][] = $row;
    }
}

echo json_encode($response);
?>
