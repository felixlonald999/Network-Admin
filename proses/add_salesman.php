<?php
require("autoload.php");

$ktp = $_POST['ktp'];
$nama = $_POST['nama'];
$alamat = $_POST['alamat_tinggal'];
$kota_tinggal = $_POST['kota_tinggal'];
$kota_lahir = $_POST['kota_lahir'];
$tanggal_lahir = $_POST['tanggal_lahir'];
$tanggal_bergabung = $_POST['tanggal_bergabung'];
$jenis_kelamin = $_POST['jenis_kelamin'];
$no_telp = $_POST['telepon'];
$area = $_POST['area'];
$kode_dealer = $_POST['kode_dealer'];

$stmt = "SELECT `nama_dealer` FROM `dealer` WHERE `kode_dealer` = '$kode_dealer'";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));
$data_dealer = mysqli_fetch_assoc($query);

$nama_dealer = $data_dealer['nama_dealer'];

$stmt = "INSERT INTO `salesman` (`ktp`, `nama`, `alamat_tinggal`, `kota_tinggal`, `kota_lahir`, `tgl_lahir`, `tgl_bergabung`, `jenis_kelamin`, `telepon`, `area`, `kode_dealer`, `nama_dealer`, `jabatan`, `status`) VALUES 
            ('$ktp', '$nama', '$alamat', '$kota_tinggal', '$kota_lahir', '$tanggal_lahir', '$tanggal_bergabung', '$jenis_kelamin', '$no_telp', '$area', '$kode_dealer', '$nama_dealer', 'SHOP MANAGER', 'AKTIF')";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));

$id = mysqli_insert_id($conn2);

$stmt = "INSERT INTO `karyawan`(`id`, `user`, `area`, `nip`, `nama`, `golongan`, `jabatan`, `divisi`, `subdivisi`, `perusahaan`, `status`, `status_karyawan`, `lokasi`, `range_nilai`, `nilai`, `alias_divisi`, `kode_dealer`, `email`, `phone`, `role`, `permission`, `atasan_id`, `proposal_approval`, `urutan_approval`, `ttd`) VALUES 
        ('" . $id . "0', '123456', '" . $area . "', '0', '" . $nama . "', '4', 'HEAD STORE', 'SALES', 'SHOP MANAGER', '', 'AKTIF', 'KONTRAK', '', '', '', '', '', '', '', '', 'purchasing', '', '', '1', '')";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));

$stmt = "INSERT INTO `login` (`id`, `user`, `area`, `nip`, `nama`, `golongan`, `jabatan`, `divisi`, `status`) VALUES
            ('" . $id . "0', '123456', '" . $area . "', '0', '" . $nama . "', '4', 'HEAD STORE', 'SALES', 'AKTIF')";
$query = mysqli_query($conn4, $stmt) or die(mysqli_error($conn4));

if ($query) {
    header("location: ../salesman.php?area=" . urlencode($area));
} else {
    header("location: ../salesman.php?area=" . urlencode($area) . "&error=gagal");
}