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
    $ktp = isset($_POST['ktp']) ? $_POST['ktp'] : '';
    $nama = isset($_POST['nama']) ? $_POST['nama'] : '';
    $alamat = isset($_POST['alamat_tinggal']) ? $_POST['alamat_tinggal'] : '';
    $kota_tinggal = isset($_POST['kota_tinggal']) ? $_POST['kota_tinggal'] : '';
    $kota_lahir = isset($_POST['kota_lahir']) ? $_POST['kota_lahir'] : '';
    $tanggal_lahir = isset($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '';
    $tanggal_bergabung = isset($_POST['tanggal_bergabung']) ? $_POST['tanggal_bergabung'] : '';
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
    $no_telp = isset($_POST['telepon']) ? $_POST['telepon'] : '';
    $area = isset($_POST['area']) ? $_POST['area'] : '';
    $kode_dealer = isset($_POST['kode_dealer']) ? $_POST['kode_dealer'] : '';

    // Cek KTP apakah sudah ada di Server B (opsional untuk menghindari duplikat)
    $stmt_cek = "SELECT * FROM `salesman` WHERE `ktp` = '$ktp'";
    $query_cek = mysqli_query($conn2, $stmt_cek);

    if ($query_cek && mysqli_num_rows($query_cek) > 0) {
        $response = array('status' => 'error', 'message' => "Salesman dengan KTP $ktp sudah terdaftar di Server B!");
        echo json_encode($response);
        exit;
    }

    // Ambil nama dealer
    $stmt = "SELECT `nama_dealer` FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
    $query = mysqli_query($conn2, $stmt);
    if ($query) {
        $data_dealer = mysqli_fetch_assoc($query);
        $nama_dealer = $data_dealer ? $data_dealer['nama_dealer'] : '';
    } else {
        $nama_dealer = '';
    }

    // Insert ke tabel salesman (menggunakan koneksi conn2 seperti aslinya)
    $stmt1 = "INSERT INTO `salesman` (`ktp`, `nama`, `alamat_tinggal`, `kota_tinggal`, `kota_lahir`, `tgl_lahir`, `tgl_bergabung`, `jenis_kelamin`, `telepon`, `area`, `kode_dealer`, `nama_dealer`, `jabatan`, `status`) VALUES 
                ('$ktp', '$nama', '$alamat', '$kota_tinggal', '$kota_lahir', '$tanggal_lahir', '$tanggal_bergabung', '$jenis_kelamin', '$no_telp', '$area', '$kode_dealer', '$nama_dealer', 'SHOP MANAGER', 'AKTIF')";
    $query1 = mysqli_query($conn2, $stmt1);

    if ($query1) {
        $id = mysqli_insert_id($conn2);

        // Insert ke tabel karyawan (conn2)
        $stmt2 = "INSERT INTO `karyawan`(`id`, `user`, `area`, `nip`, `nama`, `golongan`, `jabatan`, `divisi`, `subdivisi`, `perusahaan`, `status`, `status_karyawan`, `lokasi`, `range_nilai`, `nilai`, `alias_divisi`, `kode_dealer`, `email`, `phone`, `role`, `permission`, `atasan_id`, `proposal_approval`, `urutan_approval`, `ttd`) VALUES 
                ('" . $id . "0', '123456', '" . $area . "', '0', '" . $nama . "', '4', 'HEAD STORE', 'SALES', 'SHOP MANAGER', '', 'AKTIF', 'KONTRAK', '', '', '', '', '', '', '', '', 'purchasing', '', '', '1', '')";
        $query2 = mysqli_query($conn2, $stmt2);

        // Insert ke tabel login (conn4)
        $stmt3 = "INSERT INTO `login` (`id`, `user`, `area`, `nip`, `nama`, `golongan`, `jabatan`, `divisi`, `status`) VALUES
                    ('" . $id . "0', '123456', '" . $area . "', '0', '" . $nama . "', '4', 'HEAD STORE', 'SALES', 'AKTIF')";
        $query3 = mysqli_query($conn4, $stmt3);

        if ($query2 && $query3) {
            $response = array('status' => 'success', 'message' => 'Salesman berhasil ditambahkan secara utuh di Server B');
        } else {
            $response = array('status' => 'partial_success', 'message' => 'Salesman berhasil ditambah, namun sebagian tabel (karyawan/login) gagal di Server B');
        }
    } else {
        $response = array('status' => 'error', 'message' => 'Gagal menambahkan data ke tabel salesman di Server B: ' . mysqli_error($conn2));
    }
} else {
    $response = array('status' => 'error', 'message' => 'Invalid Request Method');
}

// Kembalikan output JSON
echo json_encode($response);
?>
