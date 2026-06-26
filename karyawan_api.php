<?php
require("autoload.php");
require("layout/header.php");
require("layout/navbar.php");

if (!isset($_SESSION['auth'])) {
    header('location: login.php');
}

$area   = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';

$url_api = "http://10.10.10.2/Network-Admin/api/get_data_karyawan.php?area=" . urlencode($area);

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
$data_karyawan = array();

if ($response) {
    $result = json_decode($response, true);
    if (isset($result['data_area'])) {
        $data_area = $result['data_area'];
    }
    if (isset($result['data_karyawan'])) {
        $data_karyawan = $result['data_karyawan'];
    }
} else {
    $_SESSION['alert_message'] = "Gagal mengambil data dari Server B!";
}
?>

<body>
    <div class="container-fluid px-5">
        <h1 class="text-center text-primary">DATA KARYAWAN</h1>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>List Data Karyawan</span>
                    </div>
                    <div class="card-body">
                        <form action="karyawan.php" method="GET">
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
                                        <td>ID Karyawan</td>
                                        <td>NIP</td>
                                        <td>Nama Karyawan</td>
                                        <td>Jabatan</td>
                                        <td>Divisi</td>
                                        <td>Subdivisi</td>
                                        <td>Status Karyawan</td>
                                        <td>Area</td>
                                        <td>Kode Dealer</td>
                                        <td>No. Telp</td>
                                        <td>Email</td>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($data_karyawan as $key => $value): ?>
                                        <tr>
                                            <td style="vertical-align: middle">
                                                <button class="btn btn-sm btn-toggle-status btn-outline-primary" data-id="<?= $value['id'] ?>" data-status="<?= $value['status'] ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            </td>
                                            <td style="vertical-align: middle"><?= isset($value['status']) ? $value['status'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['id']) ? $value['id'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['nip']) ? $value['nip'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= $value['nama'] ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['jabatan']) ? $value['jabatan'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['divisi']) ? $value['divisi'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['subdivisi']) ? $value['subdivisi'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['status_karyawan']) ? $value['status_karyawan'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['area']) ? $value['area'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['kode_dealer']) ? $value['kode_dealer'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['phone']) ? $value['phone'] : '-' ?></td>
                                            <td style="vertical-align: middle"><?= isset($value['email']) ? $value['email'] : '-' ?></td>
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
    <script>
        $(document).on('click', '.btn-toggle-status', function() {
            let btn = $(this);
            let id = btn.data('id');
            let status = btn.data('status').toString().toUpperCase();

            let newStatus = (status === 'AKTIF') ? 'TIDAK AKTIF' : 'AKTIF';

            $.ajax({
                url: 'proses/ubah_status_karyawan_api.php',
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
