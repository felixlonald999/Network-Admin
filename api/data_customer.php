<?php
require("autoload.php");

$query_service = "SELECT no_ktp, nomor_rangka, COUNT(id) AS ro_service, MAX(tanggal_service) AS tanggal_terakhir_service 
                    FROM history_service 
                    GROUP BY no_ktp, nomor_rangka";
$result_service = $conn->query($query_service);
while ($row = $result_service->fetch_assoc()) { //masih perlu dibenerin
    $service_data[$row['nomor_rangka']] = [
        'ro_service' => $row['ro_service'],
        'tanggal_terakhir_service' => $row['tanggal_terakhir_service']
    ];
}

$query_nomor_rangka = "SELECT no_ktp, nomor_rangka FROM faktur";
$result_nomor_rangka = $conn->query($query_nomor_rangka);
while ($row = $result_nomor_rangka->fetch_assoc()) {
    $faktur_data[$row['no_ktp']] = $row['nomor_rangka'];
}

// Ambil data customer dari faktur
$query_faktur = "SELECT f.no_ktp, f.nama_konsumen, f.no_hp, f.tanggal_lahir, COUNT(DISTINCT f.nomor_rangka) AS ro_sales,  
            MAX(f.tanggal_beli_motor) AS tanggal_terakhir_beli, 
            (SELECT area_dealer FROM faktur AS f2 
            WHERE f2.no_ktp = f.no_ktp AND f2.tanggal_beli_motor = f.tanggal_beli_motor LIMIT 1) AS area_dealer
        FROM faktur AS f
        GROUP BY f.no_ktp, f.nama_konsumen"; 

$result = $conn->query($query_faktur);
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

        $total_service = 0;
        $tanggal_terakhir_service = null;

        
        // Cek apakah customer sudah ada di data_customer
        $check = $conn->prepare("SELECT ktp_customer FROM data_customer WHERE ktp_customer = ?");
        $check->bind_param("s", $ktp);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Jika sudah ada, update data
            $update = $conn->prepare("UPDATE data_customer SET nama_customer = ?, nohp2_customer = ?, 
                                        tanggal_lahir = ?, ro_sales= ?, ro_service = ?, 
                                        tanggal_terakhir_beli_motor = ?, tanggal_terakhir_service = ?, area_dealer = ?, update_nohp_customer_terakhir = ?
                                        WHERE ktp_customer = ?");
            $update->bind_param("sssiisssss", $nama, $nohp, $tanggal_lahir, $ro_sales, $ro_service, $tanggal_terakhir_beli, 
                                    $tanggal_terakhir_service, $area_dealer, $update_nohp_customer_terakhir, $ktp);
            $update->execute();
        } else {
            // Jika belum ada, insert data baru
            $insert = $conn->prepare("INSERT INTO data_customer (ktp_customer, nama_customer, nohp1_customer, 
                                        tanggal_lahir, ro_sales, tanggal_terakhir_beli_motor, area_dealer, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $insert->bind_param("sssssss", $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, $tanggal_terakhir_beli, $area_dealer);
            $insert->execute();
        }
    }
}

?>