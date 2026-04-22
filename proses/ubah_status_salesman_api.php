<?php
$data = array(
    'id' => isset($_POST['id']) ? $_POST['id'] : '',
    'status' => isset($_POST['status']) ? $_POST['status'] : ''
);

// URL API Server B untuk menerima update status
$url_server_b = "http://10.10.10.2/Network-Admin/api/receive_ubah_status_salesman.php";

$ch = curl_init($url_server_b);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
// Tambahkan User-Agent agar tidak diblokir oleh Firewall Server B
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    $result = json_decode($response, true);
    if (isset($result['status']) && $result['status'] == 'success') {
        echo "success";
    } else {
        $msg = isset($result['message']) ? $result['message'] : 'unknown error';
        echo "error: " . $msg;
    }
} else {
    echo "error: Failed to connect to Server B";
}
?>
