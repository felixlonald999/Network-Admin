<?php
require("autoload.php");

$kode_dealer = $_POST['kode_dealer'];
$kode_yimm = $_POST['kode_yimm'];
$nama_dealer = $_POST['nama_dealer'];
$nama_alias = $_POST['nama_alias'];
$area = $_POST['area'];
$alamat = $_POST['alamat'];
$kabupaten = $_POST['kabupaten'];
$kecamatan = $_POST['kecamatan'];
$nama_perusahaan = $_POST['nama_perusahaan'];
$no_telp = $_POST['nomor_telepon'];
$status_group = $_POST['status_group'];
$status_dealer = $_POST['status_dealer'];
$tabel = $_POST['tabel'];
$status_kepemilikan = $_POST['status_kepemilikan'];

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

if ($tabel == 'yamahast_crm') {
    $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
    $query_cek = mysqli_query($conn2, $stmt_cek);

    if (mysqli_num_rows($query_cek) > 0) {
        $_SESSION['alert_message'] = "Dealer dengan kode_dealer $kode_dealer sudah ada di tabel yamahast_crm!";
        header("Location: ../dealer.php");
        exit;
    }
    
    $query = mysqli_query($conn2, $stmt);
    $id = mysqli_insert_id($conn2);

    $stmt_update = "UPDATE `dealer` SET `password` = '$id' WHERE `id` = '$id'";  
    $query = mysqli_query($conn2, $stmt_update);
} else if ($tabel == 'yamahast_dealer') {
    $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
    $query_cek = mysqli_query($conn3, $stmt_cek);

    if (mysqli_num_rows($query_cek) > 0) {
        $_SESSION['alert_message'] = "Dealer dengan kode_dealer $kode_dealer sudah ada di tabel yamahast_dealer!";
        header("Location: ../dealer.php");
        exit;
    }

    $query = mysqli_query($conn3, $stmt);
    $id = mysqli_insert_id($conn3);

    $stmt_update = "UPDATE `dealer` SET `password` = '$id' WHERE `id` = '$id'";  
    $query = mysqli_query($conn3, $stmt_update);
} else if ($tabel == 'yamahast_sigaplegal') {
    $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
    $query_cek = mysqli_query($conn4, $stmt_cek);

    if (mysqli_num_rows($query_cek) > 0) {
        $_SESSION['alert_message'] = "Dealer dengan kode_dealer $kode_dealer sudah ada di tabel yamahast_sigaplegal!";
        header("Location: ../dealer.php");
        exit;
    }

    $query = mysqli_query($conn4, $stmt);
    $id = mysqli_insert_id($conn4);

    $stmt_update = "UPDATE `dealer` SET `password` = '$id' WHERE `id` = '$id'";  
    $query = mysqli_query($conn4, $stmt_update);
} else {
    $pesan_error = [];
    $stmt_cek = "SELECT * FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";

    $query_cek2 = mysqli_query($conn2, $stmt_cek);
    if (mysqli_num_rows($query_cek2) > 0) {
        $pesan_error[] = "yamahast_crm";
    } else {
        $query = mysqli_query($conn2, $stmt);
        $id1 = mysqli_insert_id($conn2);
        $stmt_update = "UPDATE `dealer` SET `password` = '$id1' WHERE `id` = '$id1'";  
        $query_update = mysqli_query($conn2, $stmt_update);
    }

    $query_cek3 = mysqli_query($conn3, $stmt_cek);
    if (mysqli_num_rows($query_cek3) > 0) {
        $pesan_error[] = "yamahast_dealer";
    } else {
        $query = mysqli_query($conn3, $stmt);
        $id2 = mysqli_insert_id($conn3);
        $stmt_update = "UPDATE `dealer` SET `password` = '$id2' WHERE `id` = '$id2'";  
        $query_update = mysqli_query($conn3, $stmt_update);
    }

    $query_cek4 = mysqli_query($conn4, $stmt_cek);
    if (mysqli_num_rows($query_cek4) > 0) {
        $pesan_error[] = "yamahast_sigaplegal";
    } else {
        $query = mysqli_query($conn4, $stmt);
        $id3 = mysqli_insert_id($conn4);
        $stmt_update = "UPDATE `dealer` SET `password` = '$id3' WHERE `id` = '$id3'";  
        $query_update = mysqli_query($conn4, $stmt_update);
    }

    if (count($pesan_error) > 0) {
        $tabel_error = implode(', ', $pesan_error);
        $_SESSION['alert_message'] = "Dealer dengan kode_dealer $kode_dealer sudah ada di tabel $tabel_error!";
    }
}

header("Location: ../dealer.php");