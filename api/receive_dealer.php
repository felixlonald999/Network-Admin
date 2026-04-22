<?php
// Set Header agar API bisa dipanggil lintas server (CORS) jika diperlukan
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Sertakan config & koneksi database dari folder proses
require_once("../proses/autoload.php");

$response = array();

// Pastikan hanya request bertipe POST yang diproses
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_dealer = isset($_POST['kode_dealer']) ? $_POST['kode_dealer'] : '';
    $kode_yimm = isset($_POST['kode_yimm']) ? $_POST['kode_yimm'] : '';
    $nama_dealer = isset($_POST['nama_dealer']) ? $_POST['nama_dealer'] : '';
    $nama_alias = isset($_POST['nama_alias']) ? $_POST['nama_alias'] : '';
    $area = isset($_POST['area']) ? $_POST['area'] : '';
    $alamat = isset($_POST['alamat']) ? $_POST['alamat'] : '';
    $kabupaten = isset($_POST['kabupaten']) ? $_POST['kabupaten'] : '';
    $kecamatan = isset($_POST['kecamatan']) ? $_POST['kecamatan'] : '';
    $nama_perusahaan = isset($_POST['nama_perusahaan']) ? $_POST['nama_perusahaan'] : '';
    $no_telp = isset($_POST['nomor_telepon']) ? $_POST['nomor_telepon'] : '';
    $status_group = isset($_POST['status_group']) ? $_POST['status_group'] : '';
    $status_dealer = isset($_POST['status_dealer']) ? $_POST['status_dealer'] : '';
    $tabel = isset($_POST['tabel']) ? $_POST['tabel'] : '';
    $status_kepemilikan = isset($_POST['status_kepemilikan']) ? $_POST['status_kepemilikan'] : '';

    $nama_pemilik = 'Mr. Robert Tansil';
    $pabrikan = 'YAMAHA';
    $status_operasional = 'AKTIF';

    if ($area == 'SURABAYA INSIDE' || $area == 'SURABAYA OUTSIDE' || $area == 'MALANG' || $area == 'JEMBER' || $area == 'NTB' || $area == 'NTT') {
        $teritori = 'IV';
    } else {
        $teritori = 'VIII';
    }

    $stmt = "INSERT INTO `dealer` (`teritori`, `area`, `kabupaten`, `kecamatan`, `kode_dealer`, `kode_yimm`, `nama_dealer`, `nama_alias`, `nama_perusahaan`, `alamat_dealer`, `telepon`, `nama_pemilik`, `status_group`, `status_dealer`, `status_kepemilikan`, `pabrikan`, `status_operational`) VALUES
                ('$teritori', '$area', '$kabupaten', '$kecamatan', '$kode_dealer', '$kode_yimm', '$nama_dealer', '$nama_alias', '$nama_perusahaan', '$alamat', '$no_telp', '$nama_pemilik', '$status_group', '$status_dealer', '$status_kepemilikan', '$pabrikan', '$status_operasional')";          

    $berhasil = false;
    $pesan_error = array();

    if ($tabel == 'yamahast_crm') {
        $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
        $query_cek = mysqli_query($conn2, $stmt_cek);

        if (mysqli_num_rows($query_cek) > 0) {
            $pesan_error[] = "Dealer dengan kode $kode_dealer sudah ada di tabel yamahast_crm (Server B)!";
        } else {
            mysqli_query($conn2, $stmt);
            $id = mysqli_insert_id($conn2);
            $stmt_update = "UPDATE `dealer` SET `password` = '$id' WHERE `id` = '$id'";  
            mysqli_query($conn2, $stmt_update);
            $berhasil = true;
        }
    } else if ($tabel == 'yamahast_dealer') {
        $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
        $query_cek = mysqli_query($conn3, $stmt_cek);

        if (mysqli_num_rows($query_cek) > 0) {
            $pesan_error[] = "Dealer dengan kode $kode_dealer sudah ada di tabel yamahast_dealer (Server B)!";
        } else {
            mysqli_query($conn3, $stmt);
            $id = mysqli_insert_id($conn3);
            $stmt_update = "UPDATE `dealer` SET `password` = '$id' WHERE `id` = '$id'";  
            mysqli_query($conn3, $stmt_update);
            $berhasil = true;
        }
    } else if ($tabel == 'yamahast_sigaplegal') {
        $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
        $query_cek = mysqli_query($conn4, $stmt_cek);

        if (mysqli_num_rows($query_cek) > 0) {
            $pesan_error[] = "Dealer dengan kode $kode_dealer sudah ada di tabel yamahast_sigaplegal (Server B)!";
        } else {
            mysqli_query($conn4, $stmt);
            $id = mysqli_insert_id($conn4);
            $stmt_update = "UPDATE `dealer` SET `password` = '$id' WHERE `id` = '$id'";  
            mysqli_query($conn4, $stmt_update);
            $berhasil = true;
        }
    } else {
        $berhasil_sebagian = false;
        
        $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";

        $query_cek2 = mysqli_query($conn2, $stmt_cek);
        if (mysqli_num_rows($query_cek2) > 0) {
            $pesan_error[] = "yamahast_crm";
        } else {
            mysqli_query($conn2, $stmt);
            $id1 = mysqli_insert_id($conn2);
            $stmt_update = "UPDATE `dealer` SET `password` = '$id1' WHERE `id` = '$id1'";  
            mysqli_query($conn2, $stmt_update);
            $berhasil_sebagian = true;
        }

        $query_cek3 = mysqli_query($conn3, $stmt_cek);
        if (mysqli_num_rows($query_cek3) > 0) {
            $pesan_error[] = "yamahast_dealer";
        } else {
            mysqli_query($conn3, $stmt);
            $id2 = mysqli_insert_id($conn3);
            $stmt_update = "UPDATE `dealer` SET `password` = '$id2' WHERE `id` = '$id2'";  
            mysqli_query($conn3, $stmt_update);
            $berhasil_sebagian = true;
        }

        $query_cek4 = mysqli_query($conn4, $stmt_cek);
        if (mysqli_num_rows($query_cek4) > 0) {
            $pesan_error[] = "yamahast_sigaplegal";
        } else {
            mysqli_query($conn4, $stmt);
            $id3 = mysqli_insert_id($conn4);
            $stmt_update = "UPDATE `dealer` SET `password` = '$id3' WHERE `id` = '$id3'";  
            mysqli_query($conn4, $stmt_update);
            $berhasil_sebagian = true;
        }

        if (count($pesan_error) > 0) {
            $tabel_error = implode(', ', $pesan_error);
            $pesan_error = array("Dealer dengan kode_dealer $kode_dealer sudah ada di tabel $tabel_error (Server B)!");
        }

        if ($berhasil_sebagian) {
            $berhasil = true;
        }
    }

    // Buat Respon
    if ($berhasil && count($pesan_error) == 0) {
        $response = array('status' => 'success', 'message' => 'Dealer berhasil ditambahkan secara utuh di Server B');
    } else if ($berhasil && count($pesan_error) > 0) {
        $response = array('status' => 'partial_success', 'message' => 'Sebagian berhasil: ' . implode(' | ', $pesan_error));
    } else {
        $response = array('status' => 'error', 'message' => implode(' | ', $pesan_error));
    }

} else {
    $response = array('status' => 'error', 'message' => 'Invalid Request Method');
}

// Kembalikan output JSON
echo json_encode($response);
?>
