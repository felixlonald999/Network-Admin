<?php
require("autoload.php");

$ro_sales_konsumen = [];

$query_faktur = "SELECT * FROM faktur";
// $query_faktur = "SELECT f.no_ktp, f.nama_konsumen, f.no_hp, f.tanggal_lahir, COUNT(DISTINCT f.nomor_rangka) AS ro_sales,  
//             MAX(f.tanggal_beli_motor) AS tanggal_terakhir_beli
//             FROM faktur AS f
//             GROUP BY f.no_ktp, f.nama_konsumen";
$query = MYSQLI_query($conn, $query_faktur);
while ($row = MYSQLI_fetch_assoc($query)) {
    $ro_sales_konsumen[$row['no_ktp']] = $row;
    $ro_sales_konsumen[$row['no_hp']] = $row;
} 
// $result = $conn->query($query_faktur);
// while ($row = $result->fetch_assoc()) {
//     $ro_sales_konsumen[$row['no_ktp']] = $row;
//     $ro_sales_konsumen[$row['no_hp']] = $row;
// }
dd($ro_sales_konsumen);

// $query_service = "SELECT f.no_ktp, COUNT(hs.id) AS ro_service, MAX(hs.tanggal_service) AS tanggal_terakhir_service 
//                     FROM history_service AS hs INNER JOIN `faktur` as f ON f.nomor_rangka = hs.nomor_rangka
//                     WHERE f.no_ktp = hs.no_ktp
//                     GROUP BY no_ktp";
$query_service = "SELECT hs.no_ktp, COUNT(hs.id) AS ro_service, MAX(hs.tanggal_service) AS tanggal_terakhir_service 
                    FROM history_service AS hs
                    GROUP BY no_ktp, nama_konsumen";
$start_time = microtime(true);
$result_service = $conn->query($query_service);
$end_time = microtime(true);
$query_time = $end_time - $start_time;
echo "Waktu eksekusi query: " . number_format($query_time, 6) . " detik";
while ($row = $result_service->fetch_assoc()) { 
    $service_data[$row['no_ktp']] = $row;
}

// dd($service_data);


// Ambil data customer dari faktur


// $query_faktur = "SELECT no_ktp, nama_konsumen, no_hp, tanggal_lahir, COUNT(DISTINCT nomor_rangka) AS ro_sales,  
//             MAX(tanggal_beli_motor) AS tanggal_terakhir_beli
//             FROM faktur
//             GROUP BY no_ktp, nama_konsumen"; 
// $query_faktur = "SELECT f.no_ktp, f.nama_konsumen, f2.area_dealer FROM faktur as f INNER JOIN faktur as f2 on f2.no_ktp = f.no_ktp AND f2.tanggal_beli_motor = f.tanggal_beli_motor
//             GROUP BY f.no_ktp, f.nama_konsumen";
$start_time = microtime(true);
$result = $conn->query($query_faktur);
$end_time = microtime(true);
$query_time = $end_time - $start_time;
echo "Waktu eksekusi query: " . number_format($query_time, 6) . " detik";
while ($row = $result->fetch_assoc()) {
    $faktur[$row['no_ktp']] = $row;
}

dd($faktur);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ktp = $row['no_ktp'];
        $nama = $row['nama_konsumen'];
        $nohp = $row['no_hp'];
        $tanggal_lahir = date("Y-m-d", strtotime($row['tanggal_lahir']));
        $ro_sales = $row['ro_sales']; // Dihitung dari nomor rangka yang berbeda berdasar ktp
        $tanggal_terakhir_beli = date("Y-m-d", strtotime($row['tanggal_terakhir_beli'])); // Tanggal pembelian terakhir
        $area_dealer = $row['area_dealer'];

        if (check_data_customer($conn, $ktp)) {
            // Jika sudah ada, update data
            // $update = $conn->prepare("UPDATE data_customer SET nama_customer = ?, nohp2_customer = ?, 
            //                             tanggal_lahir = ?, ro_sales= ?, ro_service = ?, 
            //                             tanggal_terakhir_beli_motor = ?, tanggal_terakhir_service = ?, area_dealer = ?, update_nohp_customer_terakhir = ?
            //                             WHERE ktp_customer = ?");
            // $update->bind_param("sssiisssss", $nama, $nohp, $tanggal_lahir, $ro_sales, $ro_service, $tanggal_terakhir_beli, 
            //                         $tanggal_terakhir_service, $area_dealer, $update_nohp_customer_terakhir, $ktp);
            // $update->execute();
        } else {
            if ($service_data[$ktp]) {
                $ro_service = $service_data[$ktp]['ro_service'];
                $tanggal_terakhir_service = $service_data[$ktp]['tanggal_terakhir_service'];

                $insert = $conn->prepare("INSERT INTO data_customer (ktp_customer, nama_customer, nohp1_customer, 
                                        tanggal_lahir, ro_sales, ro_service, tanggal_terakhir_beli_motor, tanggal_terakhir_service, area_dealer, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $insert->bind_param("sssssssss", $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, $ro_service, $tanggal_terakhir_beli, $tanggal_terakhir_service, $area_dealer);
                $insert->execute();
            } else {
                $insert = $conn->prepare("INSERT INTO data_customer (ktp_customer, nama_customer, nohp1_customer, 
                                        tanggal_lahir, ro_sales, tanggal_terakhir_beli_motor, area_dealer, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $insert->bind_param("sssssss", $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, $tanggal_terakhir_beli, $area_dealer);
                $insert->execute();
            }
            
        }
    }
}

$query = "SELECT * FROM history_service";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $history_service[$row['nomor_rangka']] = $row;
}



function check_data_customer($conn, $ktp) {
    $query_check = "SELECT ktp_customer FROM data_customer WHERE ktp_customer = ?";
    $check = $conn->prepare($query_check);
    $check->bind_param("s", $ktp);
    $check->execute();
    $check->store_result();
    return $check->num_rows > 0;
}

?>