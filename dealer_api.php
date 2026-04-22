<?php
require("autoload.php");
require("layout/header.php");
require("layout/navbar.php");

if (!isset($_SESSION['auth'])) {
    header('location: login.php');
}

$area   = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';
$tabel  = isset($_GET['tabel']) ? $_GET['tabel'] : 'crm';

$url_api = "http://10.10.10.2/Network-Admin/api/get_data_dealer.php?area=" . urlencode($area) . "&tabel=" . urlencode($tabel);

// Menggunakan cURL karena lebih aman dan biasanya allow_url_fopen di PHP 5 dimatikan
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// Tambahkan User-Agent agar tidak diblokir oleh Firewall Server B
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
$response = curl_exec($ch);
if(curl_errno($ch)){
    $_SESSION['alert_message'] = "Curl error: " . curl_error($ch);
}
curl_close($ch);

$data_area = array();
$data_dealer = array();

if ($response) {
    $result = json_decode($response, true);
    if (isset($result['data_area'])) {
        $data_area = $result['data_area'];
    }
    if (isset($result['data_dealer'])) {
        $data_dealer = $result['data_dealer'];
    }
} else {
    $_SESSION['alert_message'] = "Gagal mengambil data dari Server B!";
}
?>

<body>
    <div class="container-fluid px-5">
        <h1 class="text-center text-primary">DATA DEALER</h1>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>List Data Dealer</span>

                        <button class="btn btn-light btn-sm font-weight-bold" type="button" data-toggle="modal" data-target="#tambahDealerModal">
                            <i class="fa fa-plus pr-1"></i> Tambah Dealer
                        </button>
                    </div>
                    <div class="card-body">
                        <form action="dealer_api.php" method="GET">
                            <div class="row align-items-center">

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fa fa-city pr-3"></i> Area
                                                </span>
                                            </div>
                                            <select name="area" class="form-control">
                                                <?php foreach ($data_area as $key => $value): ?>
                                                    <option value="<?= $value['area'] ?>" <?= $area == $value['area'] ? "selected" : "" ?>>
                                                        <?= $value['area'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Radio Button -->
                                <div class="col-md-5">
                                    <div class="form-group mb-3">
                                        <div class="d-flex align-items-center">

                                            <label class="mr-3 mb-0 font-weight-bold">Type:</label>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="tabel" value="crm"
                                                    <?= $tabel == 'crm' ? 'checked' : '' ?>>
                                                <label class="form-check-label">CRM</label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="tabel" value="dealer"
                                                    <?= $tabel == 'dealer' ? 'checked' : '' ?>>
                                                <label class="form-check-label">Dealer</label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="tabel" value="sigap_legal"
                                                    <?= $tabel == 'sigap_legal' ? 'checked' : '' ?>>
                                                <label class="form-check-label">Sigap Legal</label>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Button -->
                                <div class="col-md text-right">
                                    <button class="btn btn-primary font-weight-bold">
                                        <i class="fa fa-filter pr-1"></i> Filter
                                    </button>
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
                                        <td>Kabupaten</td>
                                        <td>Kecamatan</td>
                                        <td>Alamat</td>
                                        <td>No. Telp</td>
                                        <td>Nama Perusahaan</td>
                                        <td>Nama Pemilik</td>
                                        <td>Tipe Dealer</td>
                                        <td>Status Dealer</td>
                                        <td>Status Kepemilikan</td>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($data_dealer as $key => $value): ?>
                                        <tr>
                                            <td style="vertical-align: middle"><?= ($key + 1) ?></td>
                                            <td style="vertical-align: middle"><?= $value['kode_dealer'] ?></td>
                                            <td style="vertical-align: middle"><?= $value['nama_dealer'] ?></td>
                                            <td style="vertical-align: middle"><?= $value['kabupaten'] ?></td>
                                            <td style="vertical-align: middle"><?= $value['kecamatan'] ?></td>
                                            <td style="vertical-align: middle"><?= $value['alamat_dealer'] ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['telepon']) ? $value['telepon'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['nama_perusahaan']) ? $value['nama_perusahaan'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['nama_pemilik']) ? $value['nama_pemilik'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['tipe_dealer']) ? $value['tipe_dealer'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['status_dealer']) ? $value['status_dealer'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['status_kepemilikan']) ? $value['status_kepemilikan'] : '-' ?></td>
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

    <div class="modal fade" id="tambahDealerModal" tabindex="-1" role="dialog" aria-labelledby="tambahDealerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tambahDealerModalLabel">Tambah Data Dealer</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="proses/add_dealer_api.php" method="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="kode_dealer">Kode Dealer</label>
                                <input type="text" class="form-control" id="kode_dealer" name="kode_dealer" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="kode_yimm">Kode YIMM</label>
                                <input type="text" class="form-control" id="kode_yimm" name="kode_yimm" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nama_dealer">Nama Dealer</label>
                                <input type="text" class="form-control" id="nama_dealer" name="nama_dealer" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nama_alias">Nama Alias</label>
                                <input type="text" class="form-control" id="nama_alias" name="nama_alias" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="area">Area</label>
                                <select name="area" id="area" class="form-control">
                                    <option value="SURABAYA INSIDE">SURABAYA INSIDE</option>
                                    <option value="SURABAYA OUTSIDE">SURABAYA OUTSIDE</option>
                                    <option value="MALANG">MALANG</option>
                                    <option value="JEMBER">JEMBER</option>
                                    <option value="NTB">NTB</option>
                                    <option value="NTT">NTT</option>
                                    <option value="KALIMANTAN TENGAH SELATAN">KALIMANTAN TENGAH SELATAN</option>
                                    <option value="KALIMANTAN TIMUR">KALIMANTAN TIMUR</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="alamat">Alamat</label>
                                <input type="text" class="form-control" id="alamat" name="alamat" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="kabupaten">Kabupaten</label>
                                <input type="text" class="form-control" id="kabupaten" name="kabupaten">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="kecamatan">Kecamatan</label>
                                <input type="text" class="form-control" id="kecamatan" name="kecamatan">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nama_perusahaan">Nama Perusahaan</label>
                                <input type="text" class="form-control" id="nama_perusahaan" name="nama_perusahaan">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nomor_telepon">Nomor Telepon</label>
                                <input type="number" class="form-control" id="nomor_telepon" name="nomor_telepon">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="status_group">Status Group</label>
                                <input type="text" class="form-control" id="status_group" name="status_group" value="M/D">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="status_dealer">Status Dealer</label>
                                <select name="status_dealer" id="status_dealer" class="form-control">
                                    <option value="1S">1S</option>
                                    <option value="2S">2S</option>
                                    <option value="3S">3S</option>
                                    <option value="1.5S">1.5S</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="status_kepemilikan">Status Kepemilikan</label>
                                <select name="status_kepemilikan" id="status_kepemilikan" class="form-control">
                                    <option value="HAK MILIK">HAK MILIK</option>
                                    <option value="KONTRAK">KONTRAK</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="tabel">Tabel yang mau ditambahkan dealer</label>
                                <select name="tabel" id="tabel" class="form-control">
                                    <option value="yamahast_crm">yamahast_crm</option>
                                    <option value="yamahast_dealer">yamahast_dealer</option>
                                    <option value="yamahast_sigaplegal">yamahast_sigaplegal</option>
                                    <option value="ketiganya">Ketiganya</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php require_once 'layout/footer.php' ?>
    
    <?php if (isset($_SESSION['alert_message'])): ?>
        <script>
            alert("<?= $_SESSION['alert_message'] ?>");
        </script>
        <?php unset($_SESSION['alert_message']); ?>
    <?php endif; ?>
</body>

</html>
