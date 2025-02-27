<?php
require("autoload.php");
require("layout/header.php");
require("layout/navbar.php");

if(!isset($_SESSION['auth'])){
    header('location: login.php');
}

$tahun  = isset($_GET['tahun'])? $_GET['tahun'] : date('Y');
$bulan  = isset($_GET['bulan'])? $_GET['bulan'] : date('m');
$area   = isset($_GET['area'])? $_GET['area'] : 'SURABAYA INSIDE';

$stmt       = "SELECT DISTINCT area FROM `dealer` WHERE area != '' ORDER BY area ASC";
$query      = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
$data_area  = mysqli_fetch_all($query, MYSQLI_ASSOC);

$stmt           = "SELECT * FROM `service` WHERE YEAR(tanggal_terakhir_service) = '$tahun' AND MONTH(tanggal_terakhir_service) = '$bulan' AND area_dealer = '$area'";
$query          = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
$data_service    = mysqli_fetch_all($query, MYSQLI_ASSOC);
?>
<body>
    <div class="container-fluid px-5">
        <h1 class="text-center text-primary">DAFTAR SERVICE</h1>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">List Data Service</div>
                    <div class="card-body">
                        <form action="index.php" method="GET">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fa fa-calendar pr-3"></i> Tahun
                                                </span>
                                            </div>
                                            <select name="tahun" class="form-control">
                                                <?php for ($i = 2019; $i <= date('Y'); $i++) { ?>
                                                    <option value="<?= $i ?>" <?= ($i == $tahun) ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fa fa-calendar pr-3"></i> Bulan
                                                </span>
                                            </div>
                                            <select name="bulan" class="form-control">
                                                <?php for ($i = 1; $i <= 12; $i++) { ?>
                                                    <option value="<?= $i ?>" <?= ($i == $bulan) ? 'selected' : '' ?>><?= month_indo($i) ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fa fa-city pr-3"></i> Area
                                                </span>
                                            </div>
                                            <select name="area" class="form-control">
                                                <?php foreach($data_area as $key => $value): ?>
                                                    <option value="<?= $value['area'] ?>" <?= $area == $value['area'] ? "selected" : "" ?>><?= $value['area'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <button class="btn btn-primary font-weight-bold">
                                        <i class="fa fa-filter pr-1"></i> Filter
                                    </button>
                                    <a href="proses/export/faktur.php?<?= http_build_query($_GET) ?>" class="btn btn-success font-weight-bold">
                                        <i class="fa fa-file-export pr-1"></i> Excel
                                    </a>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-striped text-nowrap">
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
                        </div>    
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once 'layout/footer.php' ?>
</body>
</html>