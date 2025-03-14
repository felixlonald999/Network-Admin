<?php
require("autoload.php");

// Set batas waktu eksekusi dan batas memori jika diperlukan
set_time_limit(300); // 5 menit
ini_set('memory_limit', '512M');

// Ukuran batch untuk insert
$batch_size = 1000;

// 1. Optimalkan query - tambahkan indeks ke tabel jika belum ada
// CREATE INDEX idx_history_service_no_ktp ON history_service(no_ktp);
// CREATE INDEX idx_faktur_no_ktp ON faktur(no_ktp);

// 2. Optimalkan query service dengan SELECT yang lebih spesifik dan indeks
$query_service = "SELECT hs.no_ktp, hs.nama_konsumen, hs.no_hp, 
                   COUNT(hs.id) AS ro_service, MAX(hs.tanggal_service) AS tanggal_terakhir_service 
                   FROM history_service AS hs
                   GROUP BY hs.no_ktp, hs.nama_konsumen";

// 3. Ambil dan simpan data customer dari service untuk digunakan nanti
$start_time = microtime(true);
$result_service = $conn->query($query_service);
$service_data = [];
if ($result_service) {
    while ($row = $result_service->fetch_assoc()) { 
        $service_data[$row['no_ktp']] = $row;
    }
    $result_service->free();
}
$end_time = microtime(true);
echo "Waktu eksekusi query service: " . number_format($end_time - $start_time, 6) . " detik<br>";

// 4. Optimalkan query faktur - hindari self-join yang tidak perlu
$query_faktur = "SELECT f.id, f.no_ktp, f.nama_konsumen, f.no_hp, f.tanggal_lahir, 
                  COUNT(DISTINCT f.nomor_rangka) AS ro_sales,  
                  MAX(f.tanggal_beli_motor) AS tanggal_terakhir_beli, 
                  f.area_dealer
                  FROM faktur AS f 
                  GROUP BY f.no_ktp, f.nama_konsumen";

// 5. Ambil data KTP yang sudah ada di tabel customer untuk menghindari pengecekan satu per satu
$existing_customers = [];
$query_existing = "SELECT ktp_customer, nama_customer FROM data_customer";
$result_existing = $conn->query($query_existing);
if ($result_existing) {
    while ($row = $result_existing->fetch_assoc()) {
        $key = $row['ktp_customer'] . '|' . $row['nama_customer'];
        $existing_customers[$key] = true;
    }
    $result_existing->free();
}

// 6. Proses data faktur - dengan menggunakan prepared statement yang diinisialisasi sekali
$start_time = microtime(true);
$result_faktur = $conn->query($query_faktur);
$end_time = microtime(true);
echo "Waktu eksekusi query faktur: " . number_format($end_time - $start_time, 6) . " detik<br>";

// 7. Siapkan prepared statement untuk batch insert
$insert_stmt = $conn->prepare("INSERT INTO data_customer (ktp_customer, nama_customer, nohp1_customer, 
                              tanggal_lahir, ro_sales, ro_service, tanggal_terakhir_beli_motor, 
                              tanggal_terakhir_service, area_dealer, id_faktur_terakhir, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

// 8. Proses data faktur
$insert_count = 0;
$total_inserts = 0;

if ($result_faktur) {
    while ($row = $result_faktur->fetch_assoc()) {
        $ktp = $row['no_ktp'];
        $nama = $row['nama_konsumen'];
        $key = $ktp . '|' . $nama;
        
        // Periksa apakah data sudah ada menggunakan array (lebih cepat daripada query)
        if (!isset($existing_customers[$key])) {
            $id_faktur_terakhir = $row['id'];
            $nohp = $row['no_hp'];
            $tanggal_lahir = $row['tanggal_lahir'] ? date("Y-m-d", strtotime($row['tanggal_lahir'])) : null;
            $ro_sales = $row['ro_sales'];
            $tanggal_terakhir_beli = $row['tanggal_terakhir_beli'] ? date("Y-m-d", strtotime($row['tanggal_terakhir_beli'])) : null;
            $area_dealer = $row['area_dealer'];
            
            // Set nilai ro_service dan tanggal_terakhir_service jika ada
            $ro_service = 0;
            $tanggal_terakhir_service = null;
            
            if (isset($service_data[$ktp])) {
                $ro_service = $service_data[$ktp]['ro_service'];
                $tanggal_terakhir_service = $service_data[$ktp]['tanggal_terakhir_service'] ? 
                    date("Y-m-d", strtotime($service_data[$ktp]['tanggal_terakhir_service'])) : null;
            }
            
            // Insert data satu per satu untuk menghindari masalah dengan prepared statement multi
            $insert_stmt->bind_param("sssssissss", 
                $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, $ro_service, 
                $tanggal_terakhir_beli, $tanggal_terakhir_service, $area_dealer, $id_faktur_terakhir
            );
            
            if ($insert_stmt->execute()) {
                $insert_count++;
                $total_inserts++;
                
                // Simpan data yang baru dimasukkan ke array existing untuk mencegah duplikasi
                $existing_customers[$key] = true;
                
                // Tampilkan progress setiap batch_size inserts
                if ($insert_count >= $batch_size) {
                    echo "Inserted $insert_count records from faktur data. Total: $total_inserts<br>";
                    $insert_count = 0;
                }
            } else {
                echo "Error inserting faktur data: " . $insert_stmt->error . "<br>";
            }
        }
    }
    $result_faktur->free();
}

// 9. Tampilkan sisa progress
if ($insert_count > 0) {
    echo "Inserted $insert_count records from faktur data. Total: $total_inserts<br>";
}

// 10. Proses data service yang tidak ada di faktur
$service_inserts = 0;
foreach ($service_data as $ktp => $data) {
    $nama = $data['nama_konsumen'];
    $key = $ktp . '|' . $nama;
    
    // Periksa apakah data sudah ada di database
    if (!isset($existing_customers[$key])) {
        $nohp = $data['no_hp'];
        $ro_service = $data['ro_service'];
        $tanggal_terakhir_service = $data['tanggal_terakhir_service'] ? 
            date("Y-m-d", strtotime($data['tanggal_terakhir_service'])) : null;
        
        // Nilai default untuk data yang tidak ada
        $tanggal_lahir = null;
        $ro_sales = 0;
        $tanggal_terakhir_beli = null;
        $area_dealer = null;
        $id_faktur_terakhir = null;
        
        // Insert data service
        $insert_stmt->bind_param("sssssissss", 
            $ktp, $nama, $nohp, $tanggal_lahir, $ro_sales, $ro_service, 
            $tanggal_terakhir_beli, $tanggal_terakhir_service, $area_dealer, $id_faktur_terakhir
        );
        
        if ($insert_stmt->execute()) {
            $service_inserts++;
            $total_inserts++;
            
            // Simpan data yang baru dimasukkan ke array existing
            $existing_customers[$key] = true;
        } else {
            echo "Error inserting service data: " . $insert_stmt->error . "<br>";
        }
    }
}

echo "Inserted $service_inserts records from service data. Total overall: $total_inserts<br>";

// Tutup statement
$insert_stmt->close();


?>