<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once("../proses/autoload.php");

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if (empty($id)) {
        $response = array('status' => 'error', 'message' => 'ID tidak boleh kosong');
        echo json_encode($response);
        exit;
    }

    $stmt = "UPDATE `salesman` SET `status` = '$status' WHERE `id` = '$id'";
    $query = mysqli_query($conn2, $stmt);

    if ($query) {
        $response = array('status' => 'success', 'message' => 'Status salesman berhasil diubah di Server B');
    } else {
        $response = array('status' => 'error', 'message' => 'Gagal mengubah status di Server B: ' . mysqli_error($conn2));
    }
} else {
    $response = array('status' => 'error', 'message' => 'Invalid Request Method');
}

echo json_encode($response);
?>
