<?php
require("autoload.php");

$query_service = "SELECT hs.no_ktp, hs.nama_konsumen, hs.no_hp, COUNT(hs.id) AS ro_service, MAX(hs.tanggal_service) AS tanggal_terakhir_service 
                    FROM history_service AS hs
                    GROUP BY no_ktp, nama_konsumen";
$result_service = $conn->query($query_service);
$service_data = [];
while ($row = $result_service->fetch_assoc()) { 
    $service_data[$row['no_ktp']] = $row;
}

// Ambil data customer dari faktur
$query_faktur = "SELECT f.id, f.no_ktp, f.nama_konsumen, f.no_hp, f.tanggal_lahir, COUNT(DISTINCT f.nomor_rangka) AS ro_sales,  
                    MAX(f.tanggal_beli_motor) AS tanggal_terakhir_beli, f2.area_dealer
                    FROM faktur AS f 
                    INNER JOIN faktur AS f2 
                    ON f.no_ktp = f2.no_ktp AND f.tanggal_beli_motor = f2.tanggal_beli_motor
                    GROUP BY f.no_ktp, f.nama_konsumen";
$start_time = microtime(true);
$result = $conn->query($query_faktur);
$end_time = microtime(true);
$query_time = $end_time - $start_time;
echo "Waktu eksekusi query: " . number_format($query_time, 6) . " detik";
// while ($row = $result->fetch_assoc()) {
//     $faktur[$row['no_ktp']] = $row;
// }


$insert_data = [];
$counter = 0;
$batch_size = 1000;

while ($row = $result->fetch_assoc()) {
    $id_faktur_terakhir = $row['id'];
    $ktp = $row['no_ktp'];
    $nama = $row['nama_konsumen'];
    $nohp = $row['no_hp'];
    $tanggal_lahir = date("Y-m-d", strtotime($row['tanggal_lahir']));
    $ro_sales = $row['ro_sales']; // Dihitung dari nomor rangka yang berbeda berdasar ktp
    $tanggal_terakhir_beli = date("Y-m-d", strtotime($row['tanggal_terakhir_beli'])); // Tanggal pembelian terakhir
    $area_dealer = $row['area_dealer'];

    if (!check_data_customer($conn, $ktp, $nama)) {
        if (isset($service_data[$ktp])) {
            $ro_service = $service_data[$ktp]['ro_service'];
            $tanggal_terakhir_service = date("Y-m-d", strtotime($service_data[$ktp]['tanggal_terakhir_service']));

            $insert_data[] = [
                $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, $ro_service, $tanggal_terakhir_beli, 
                $tanggal_terakhir_service, $area_dealer, $id_faktur_terakhir
            ];

        } else {
            $insert_data[] = [
                    $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, 0, $tanggal_terakhir_beli,
                   null, $area_dealer, $id_faktur_terakhir
            ];
        }

        $counter++;
        if ($counter % $batch_size == 0) {
            insert_batch($conn, $insert_data);
            $insert_data = [];
            $counter = 0;
        }
    } 
}

if (!empty($insert_data)) {
    insert_batch($conn, $insert_data);
}

$count = 0;
$service_customer = [];

foreach ($service_data as $ktp => $data) {
    if (!check_data_customer($conn, $ktp, $data['nama_konsumen'])) {
        $ktp_customer = $data['no_ktp'];
        $nama = $data['nama_konsumen'];
        $nohp = $data['no_hp'];
        $ro_service = $data['ro_service'];
        $tanggal_terakhir_service = date("Y-m-d", strtotime($data['tanggal_terakhir_service']));

        $service_customer[] = [
            $ktp, $nama, $nohp, null, 0, $ro_service, null, 
            $tanggal_terakhir_service, null, null
        ];

        $count++;
        if ($count % $batch_size == 0) {
            insert_batch($conn, $service_customer);
            $service_customer = [];
            $count = 0;
        }
    }
}

if (!empty($service_customer)) {
    insert_batch($conn, $service_customer);
}


function insert_batch($conn, $insert_data) {
    $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()), ', count($insert_data)), ', ');

    $query = "INSERT INTO data_customer (ktp_customer, nama_customer, nohp1_customer, 
                            tanggal_lahir, ro_sales, ro_service, tanggal_terakhir_beli_motor, 
                            tanggal_terakhir_service, area_dealer, id_faktur_terakhir, created_at) 
                            VALUES $placeholders";
    $insert = $conn->prepare($query);

    $values = [];
    foreach ($insert_data as $data) {
        foreach ($data as $val) {
            $values[] = $val;
        }
    }

    $types = str_repeat('s', count($values));
    $insert->bind_param($types, ...$values);
    if ($insert->execute()) {
        echo "Batch insert berhasil!";
    } else {
        echo "Error: " . $stmt->error;
    }
}

function check_data_customer($conn, $ktp, $nama_konsumen) {
    $query_check = "SELECT 1 FROM data_customer WHERE ktp_customer = ? AND nama_customer = ? LIMIT 1";
    $check = $conn->prepare($query_check);
    $check->bind_param("ss", $ktp, $nama_konsumen);
    $check->execute();
    $check->store_result();
    return $check->num_rows > 0;
}

?>