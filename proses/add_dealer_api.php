<?php
session_start();

// Data yang diterima dari form di dealer.php
$data = array(
    'kode_dealer' => isset($_POST['kode_dealer']) ? $_POST['kode_dealer'] : '',
    'kode_yimm' => isset($_POST['kode_yimm']) ? $_POST['kode_yimm'] : '',
    'nama_dealer' => isset($_POST['nama_dealer']) ? $_POST['nama_dealer'] : '',
    'nama_alias' => isset($_POST['nama_alias']) ? $_POST['nama_alias'] : '',
    'area' => isset($_POST['area']) ? $_POST['area'] : '',
    'alamat' => isset($_POST['alamat']) ? $_POST['alamat'] : '',
    'kabupaten' => isset($_POST['kabupaten']) ? $_POST['kabupaten'] : '',
    'kecamatan' => isset($_POST['kecamatan']) ? $_POST['kecamatan'] : '',
    'nama_perusahaan' => isset($_POST['nama_perusahaan']) ? $_POST['nama_perusahaan'] : '',
    'nomor_telepon' => isset($_POST['nomor_telepon']) ? $_POST['nomor_telepon'] : '',
    'status_group' => isset($_POST['status_group']) ? $_POST['status_group'] : '',
    'status_dealer' => isset($_POST['status_dealer']) ? $_POST['status_dealer'] : '',
    'tabel' => isset($_POST['tabel']) ? $_POST['tabel'] : '',
    'status_kepemilikan' => isset($_POST['status_kepemilikan']) ? $_POST['status_kepemilikan'] : ''
);

// URL API Server B untuk menerima data
// NOTE: Ubah 'localhost/Network-Admin' ke domain/IP Server B yang sesungguhnya di masa depan
$url_server_b = "http://10.10.10.2/Network-Admin/api/receive_dealer.php"; 

// Inisialisasi cURL untuk mengirim data (HTTP POST Request)
$ch = curl_init($url_server_b);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
// Tambahkan timeout agar tidak menggantung jika Server B bermasalah
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
// Eksekusi cURL dan tangkap respon dari Server B
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Evaluasi respon dari API Server B
if ($response) {
    // Decode JSON respon dari server B
    $result = json_decode($response, true);
    
    if (isset($result['status']) && $result['status'] == 'success') {
        $_SESSION['alert_message'] = "Berhasil: " . $result['message'];
    } else {
        // Bisa error / partial_success
        $msg = isset($result['message']) ? $result['message'] : 'Gagal menambahkan dealer via API';
        $_SESSION['alert_message'] = $msg;
    }
} else {
    $_SESSION['alert_message'] = "Gagal terhubung ke Server B atau Server B tidak merespon. Error: " . $curl_error;
}

// Redirect kembali ke halaman dealer
header("Location: ../dealer.php");
exit;
?>
