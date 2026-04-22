<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once("../proses/autoload.php");

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $kode_dealer = isset($_POST['kode_dealer']) ? $_POST['kode_dealer'] : '';
    $area = isset($_POST['area']) ? $_POST['area'] : '';

    if (empty($id) || empty($kode_dealer)) {
        $response = array('status' => 'error', 'message' => 'ID dan Kode Dealer tidak boleh kosong');
        echo json_encode($response);
        exit;
    }

    $stmt_dealer = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
    $query_dealer = mysqli_query($conn2, $stmt_dealer);
    
    if ($query_dealer && mysqli_num_rows($query_dealer) > 0) {
        $data_dealer = mysqli_fetch_assoc($query_dealer);
        $nama_dealer = $data_dealer['nama_dealer'];

        $stmt = "UPDATE `salesman` SET `kode_dealer` = '$kode_dealer', `area` = '$area', `nama_dealer` = '$nama_dealer' WHERE `id` = '$id'";
        $query = mysqli_query($conn2, $stmt);

        if ($query) {
            $response = array('status' => 'success', 'message' => 'Mutasi salesman berhasil di Server B');
        } else {
            $response = array('status' => 'error', 'message' => 'Gagal mutasi salesman di Server B: ' . mysqli_error($conn2));
        }
    } else {
        $response = array('status' => 'error', 'message' => "Dealer dengan kode $kode_dealer tidak ditemukan di Server B");
    }
} else {
    $response = array('status' => 'error', 'message' => 'Invalid Request Method');
}

echo json_encode($response);
?>
