<?php
require("autoload.php");

$id = $_POST['id'];
$status = $_POST['status'];

// Ambil nama karyawan terlebih dahulu berdasarkan ID untuk disinkronisasi ke tabel lainnya
$nama = '';
$stmt_get_name = "SELECT `nama` FROM `karyawan` WHERE `id` = '$id'";
$query_get_name = mysqli_query($conn2, $stmt_get_name);
if ($query_get_name && mysqli_num_rows($query_get_name) > 0) {
    $row = mysqli_fetch_assoc($query_get_name);
    $nama = $row['nama'];
}

// 1. Update status pada tabel karyawan itu sendiri (conn2)
$stmt = "UPDATE `karyawan` SET `status` = '$status' WHERE `id` = '$id'";
$query = mysqli_query($conn2, $stmt) or die(mysqli_error($conn2));

if ($query) {
    if (!empty($nama)) {
        // Escape nama untuk pengamanan SQL Injection
        $nama_escaped_conn2 = mysqli_real_escape_string($conn2, $nama);
        $nama_escaped_conn4 = mysqli_real_escape_string($conn4, $nama);

        // 2. Update status pada tabel salesman di DB yamahast_crm (conn2)
        $stmt_salesman = "UPDATE `salesman` SET `status` = '$status' WHERE `nama` = '$nama_escaped_conn2'";
        mysqli_query($conn2, $stmt_salesman);

        // 3. Update status pada tabel login di DB yamahast_sigaplegal (conn4)
        $stmt_login = "UPDATE `login` SET `status` = '$status' WHERE `nama` = '$nama_escaped_conn4'";
        mysqli_query($conn4, $stmt_login);
    }
    echo "success";
} else {
    echo "error: " . mysqli_error($conn2);
}
?>
