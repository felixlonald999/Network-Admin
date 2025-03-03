<?php 
require("autoload.php");

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=service.xls");

$tahun  = isset($_GET['tahun'])? $_GET['tahun'] : date('Y');
$bulan  = isset($_GET['bulan'])? $_GET['bulan'] : date('m');
$area   = isset($_GET['area'])? $_GET['area'] : 'SURABAYA INSIDE';

$stmt           = "SELECT * FROM `service` WHERE YEAR(tanggal_terakhir_service) = '$tahun' AND MONTH(tanggal_terakhir_service) = '$bulan' AND area_dealer = '$area'";
$query          = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
$data_service    = mysqli_fetch_all($query, MYSQLI_ASSOC);
?>
<table border="1px">
    <thead style="background-color: #a8d0f3" class="font-weight-bold text-center">
        <tr>
            <td>No</td>
            <td>Kode Dealer</td>
            <td>Nama Dealer</td>
            <td>Nomor Rangka</td>
            <td>Nomor Polisi</td>
            <td>Nama Konsumen</td>
            <td>No. HP</td>
            <td>No. KTP</td>
            <td>Tipe Service</td>
            <td>Tanggal Terakhir Service</td>
        </tr>
    </thead>
    <tbody class="text-center">
        <?php foreach ($data_service as $key => $value): ?>
            <tr>
                <td style="vertical-align: middle"><?= ($key + 1) ?></td>
                <td style="vertical-align: middle"><?= $value['kode_dealer'] ?></td>
                <td style="vertical-align: middle"><?= $value['nama_dealer'] ?></td>
                <td style="vertical-align: middle"><?= $value['nomor_rangka'] ?></td>
                <td style="vertical-align: middle"><?= $value['nopol'] ?></td>
                <td style="vertical-align: middle"><?= $value['nama_konsumen'] ?></td>
                <td style="vertical-align: middle"><?= $value['no_hp'] ?></td>
                <td style="vertical-align: middle"><?= $value['no_ktp'] ?></td>
                <td style="vertical-align: middle"><?= $value['tipe_service'] ?></td>
                <td style="vertical-align: middle"><?= date("d M Y", strtotime($value['tanggal_terakhir_service'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>