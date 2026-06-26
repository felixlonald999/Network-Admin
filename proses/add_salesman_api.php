<?php
session_start();

// Data yang diterima dari form di salesman.php
$data = array(
    'ktp' => isset($_POST['ktp']) ? $_POST['ktp'] : '',
    'nama' => isset($_POST['nama']) ? $_POST['nama'] : '',
    'alamat_tinggal' => isset($_POST['alamat_tinggal']) ? $_POST['alamat_tinggal'] : '',
    'kota_tinggal' => isset($_POST['kota_tinggal']) ? $_POST['kota_tinggal'] : '',
    'kota_lahir' => isset($_POST['kota_lahir']) ? $_POST['kota_lahir'] : '',
    'tanggal_lahir' => isset($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '',
    'tanggal_bergabung' => isset($_POST['tanggal_bergabung']) ? $_POST['tanggal_bergabung'] : '',
    'jenis_kelamin' => isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '',
    'telepon' => isset($_POST['telepon']) ? $_POST['telepon'] : '',
    'area' => isset($_POST['area']) ? $_POST['area'] : '',
    'kode_dealer' => isset($_POST['kode_dealer']) ? $_POST['kode_dealer'] : ''
);

// URL API Server B untuk menerima data salesman
// NOTE: Ubah 'localhost/Network-Admin' ke domain/IP Server B yang sesungguhnya di masa depan
$url_server_b = "http://10.10.10.2/Network-Admin/api/receive_salesman.php"; 

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
        header("Location: ../salesman.php?area=" . urlencode($data['area']));
    } else {
        // Bisa error / partial_success
        $msg = isset($result['message']) ? $result['message'] : 'Gagal menambahkan salesman via API';
        $_SESSION['alert_message'] = $msg;
        header("Location: ../salesman.php?area=" . urlencode($data['area']) . "&error=gagal");
    }
} else {
    $_SESSION['alert_message'] = "Gagal terhubung ke Server B atau Server B tidak merespon. Error: " . $curl_error;
    header("Location: ../salesman.php?area=" . urlencode($data['area']) . "&error=gagal");
}

exit;
?>
