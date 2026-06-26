<?php
require("autoload.php");
require("layout/header.php");
require("layout/navbar.php");

if (!isset($_SESSION['auth'])) {
    header('location: login.php');
}

$area   = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';

$url_api = "http://10.10.10.2/Network-Admin/api/get_data_salesman.php?area=" . urlencode($area);

// Menggunakan cURL karena lebih aman
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
$data_salesman = array();
$data_dealer_modal = array();

if ($response) {
    $result = json_decode($response, true);
    if (isset($result['data_area'])) {
        $data_area = $result['data_area'];
    }
    if (isset($result['data_salesman'])) {
        $data_salesman = $result['data_salesman'];
    }
    if (isset($result['data_dealer_modal'])) {
        $data_dealer_modal = $result['data_dealer_modal'];
    }
} else {
    $_SESSION['alert_message'] = "Gagal mengambil data dari Server B!";
}
?>

<body>
    <div class="container-fluid px-5">
        <h1 class="text-center text-primary">DATA SALESMAN</h1>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>List Data Salesman</span>

                        <button class="btn btn-light btn-sm font-weight-bold" type="button" data-toggle="modal" data-target="#tambahSalesmanModal">
                            <i class="fa fa-plus pr-1"></i> Tambah Salesman
                        </button>
                    </div>
                    <div class="card-body">
                        <form action="salesman_api.php" method="GET">
                            <div class="row">
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
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-striped text-nowrap">
                                <thead style="background-color: #a8d0f3" class="font-weight-bold text-center">
                                    <tr>
                                        <td>Aksi</td>
                                        <td>Status Aktif</td>
                                        <td>KTP</td>
                                        <td>Nama Salesman</td>
                                        <td>Alamat Tinggal</td>
                                        <td>Kota Tinggal</td>
                                        <td>Kota Lahir</td>
                                        <td>Tanggal Lahir</td>
                                        <td>Tanggal Bergabung</td>
                                        <td>Jenis Kelamin</td>
                                        <td>No. Telp</td>
                                        <td>Area</td>
                                        <td>Nama Dealer</td>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($data_salesman as $key => $value): ?>
                                        <tr>
                                            <td style="vertical-align: middle">
                                                <button class="btn btn-sm btn-toggle-status btn-outline-primary" data-id="<?= $value['id'] ?>" data-status="<?= $value['status'] ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#mutasiSalesmanModal" data-id="<?= $value['id'] ?>">
                                                    <i class="fa fa-exchange-alt"></i>
                                                </button>
                                            </td>
                                            <td style="vertical-align: middle"><?= isset($value['status']) ? $value['status'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['ktp']) ? $value['ktp'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= $value['nama'] ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['alamat_tinggal']) ? $value['alamat_tinggal'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['kota_tinggal']) ? $value['kota_tinggal'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['kota_lahir']) ? $value['kota_lahir'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= (!empty($value['tgl_lahir']) && $value['tgl_lahir'] != '0000-00-00') ? $value['tgl_lahir'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= (!empty($value['tgl_bergabung']) && $value['tgl_bergabung'] != '0000-00-00') ? $value['tgl_bergabung'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['jenis_kelamin']) ? $value['jenis_kelamin'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['telepon']) ? $value['telepon'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['area']) ? $value['area'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['nama_dealer']) ? $value['nama_dealer'] : '-' ?></td>
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

    <div class="modal fade" id="mutasiSalesmanModal" tabindex="-1" role="dialog" aria-labelledby="mutasiSalesmanModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="mutasiSalesmanModalLabel">Mutasi Salesman</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="proses/mutasi_salesman_api.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="mutasiSalesmanId">
                        <div class="form-group">
                            <div class="form-group col-md-6">
                                <label for="area">Area</label>
                                <select name="area" id="area" class="form-control">
                                    <?php foreach ($data_area as $key => $value): ?>
                                        <option value="<?= $value['area'] ?>"><?= $value['area'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="dealer">Nama Dealer</label>
                                <select name="kode_dealer" id="kode_dealer" class="form-control select2" required>
                                    <option value="">Pilih Dealer</option>
                                    <?php foreach ($data_area as $key => $value): ?>
                                        <optgroup label="<?= $value['area'] ?>">
                                            <?php 
                                            $data_dealer = isset($data_dealer_modal[$value['area']]) ? $data_dealer_modal[$value['area']] : array();
                                            ?>
                                            <?php foreach ($data_dealer as $key => $value_dealer): ?>
                                                <option value="<?= $value_dealer['kode_dealer'] ?>">
                                                    <?= $value_dealer['kode_dealer'] ?> - <?= $value_dealer['nama_dealer'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <div class="modal fade" id="tambahSalesmanModal" tabindex="-1" role="dialog" aria-labelledby="tambahSalesmanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tambahSalesmanModalLabel">Tambah Salesman</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="proses/add_salesman_api.php" method="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="ktp">No. KTP</label>
                                <input type="number" class="form-control" id="ktp" name="ktp" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nama">Nama Salesman</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="alamat_tinggal">Alamat Tinggal</label>
                                <input type="text" class="form-control" id="alamat_tinggal" name="alamat_tinggal">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="kota_tinggal">Kota Tinggal</label>
                                <input type="text" class="form-control" id="kota_tinggal" name="kota_tinggal">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="kota_lahir">Kota Lahir</label>
                                <input type="text" class="form-control" id="kota_lahir" name="kota_lahir">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tanggal_lahir">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="tanggal_bergabung">Tanggal Bergabung</label>
                                <input type="date" class="form-control" id="tanggal_bergabung" name="tanggal_bergabung">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="jenis_kelamin">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-control">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="telepon">No. Telp</label>
                                <input type="text" class="form-control" id="telepon" name="telepon">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="area">Area</label>
                                <select name="area" id="area" class="form-control">
                                    <?php foreach ($data_area as $key => $value): ?>
                                        <option value="<?= $value['area'] ?>"><?= $value['area'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="kode_dealer">Dealer</label>
                            <select name="kode_dealer" id="kode_dealers" class="form-control select2" required>
                                <option value="">Pilih Dealer</option>
                                <?php foreach ($data_area as $key => $value): ?>
                                    <optgroup label="<?= $value['area'] ?>">
                                        <?php 
                                        $data_dealer = isset($data_dealer_modal[$value['area']]) ? $data_dealer_modal[$value['area']] : array();
                                        ?>
                                        <?php foreach ($data_dealer as $key => $value_dealer): ?>
                                            <option value="<?= $value_dealer['kode_dealer'] ?>">
                                                <?= $value_dealer['nama_dealer'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <?php require_once 'layout/footer.php' ?>
    <script>
        $(document).on('show.bs.modal', '#mutasiSalesmanModal', function(e) {
            let button = $(e.relatedTarget);
            let id = button.data('id');

            $('#mutasiSalesmanId').val(id);
        });

        $(document).on('click', '.btn-toggle-status', function() {
            let btn = $(this);
            let id = btn.data('id');
            let status = btn.data('status').toString().toUpperCase();

            let newStatus = (status === 'AKTIF') ? 'TIDAK AKTIF' : 'AKTIF';

            $.ajax({
                url: 'proses/ubah_status_salesman_api.php',
                type: 'POST',
                data: {
                    id: id,
                    status: newStatus
                },
                success: function(res) {
                    console.log(res);

                    if (res.includes('success')) {
                        btn.data('status', newStatus);

                        // update tampilan tabel
                        btn.closest('tr').find('td:eq(1)').text(newStatus.toUpperCase());
                    } else {
                        alert("Gagal mengubah status: " + res);
                    }
                },
                error: function(xhr, status, error) {
                    alert("Terjadi kesalahan koneksi saat mengubah status.\nStatus: " + status + "\nError: " + error + "\nResponse: " + xhr.responseText);
                }
            });
        });
    </script>
    <?php if (isset($_SESSION['alert_message'])): ?>
        <script>
            alert("<?= $_SESSION['alert_message'] ?>");
        </script>
        <?php unset($_SESSION['alert_message']); ?>
    <?php endif; ?>
</body>

</html>
