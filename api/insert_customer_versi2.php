<?php
require("autoload.php");

set_time_limit(0); // Hilangkan batas waktu
ini_set('memory_limit', '1024M'); // Tingkatkan memori

// 1. Tambahkan unique index jika belum ada
// $conn->query("ALTER TABLE data_customer ADD UNIQUE INDEX idx_unique_customer (ktp_customer, nama_customer)");
// Cek apakah index sudah ada
$check_index = $conn->query("
    SHOW INDEX FROM data_customer WHERE Key_name = 'idx_unique_customer'
");

if ($check_index->num_rows == 0) {
    // Tambahkan index hanya jika belum ada
    $conn->query("ALTER TABLE data_customer ADD UNIQUE INDEX idx_unique_customer (ktp_customer, nama_customer)");
}



// 2. Buat temporary table untuk staging data
$conn->query("DROP TEMPORARY TABLE IF EXISTS combined_customers");
$conn->query("CREATE TEMPORARY TABLE combined_customers (
    no_ktp VARCHAR(255),
    nama_konsumen VARCHAR(255),
    no_hp VARCHAR(255),
    tanggal_lahir DATE,
    ro_sales INT DEFAULT 0,
    ro_service INT DEFAULT 0,
    tanggal_terakhir_beli DATE,
    tanggal_terakhir_service DATE,
    area_dealer VARCHAR(255),
    id_faktur_terakhir INT,
    PRIMARY KEY (no_ktp, nama_konsumen)
)");

// 3. Proses data faktur ke temporary table
$query_faktur = "INSERT INTO combined_customers
    SELECT 
        f.no_ktp,
        f.nama_konsumen,
        MAX(f.no_hp) AS no_hp,
        MAX(f.tanggal_lahir) AS tanggal_lahir,
        COUNT(DISTINCT f.id) AS ro_sales,
        0 AS ro_service,
        MAX(f.tanggal_beli_motor) AS tanggal_terakhir_beli,
        NULL AS tanggal_terakhir_service,
        MAX(f.area_dealer) AS area_dealer,
        MAX(f.id) AS id_faktur_terakhir
    FROM faktur f
    GROUP BY f.no_ktp, f.nama_konsumen
    ON DUPLICATE KEY UPDATE
        no_hp = VALUES(no_hp),
        tanggal_lahir = VALUES(tanggal_lahir),
        ro_sales = VALUES(ro_sales),
        tanggal_terakhir_beli = VALUES(tanggal_terakhir_beli),
        area_dealer = VALUES(area_dealer),
        id_faktur_terakhir = VALUES(id_faktur_terakhir)";

$conn->query($query_faktur);

// 4. Proses data service ke temporary table
$query_service = "INSERT INTO combined_customers
    SELECT 
        hs.no_ktp,
        hs.nama_konsumen,
        MAX(hs.no_hp) AS no_hp,
        NULL AS tanggal_lahir,
        0 AS ro_sales,
        COUNT(DISTINCT hs.id) AS ro_service,
        NULL AS tanggal_terakhir_beli,
        MAX(hs.tanggal_service) AS tanggal_terakhir_service,
        NULL AS area_dealer,
        NULL AS id_faktur_terakhir
    FROM history_service hs
    GROUP BY hs.no_ktp, hs.nama_konsumen
    ON DUPLICATE KEY UPDATE
        no_hp = IF(VALUES(no_hp) IS NOT NULL, VALUES(no_hp), no_hp),
        ro_service = VALUES(ro_service),
        tanggal_terakhir_service = VALUES(tanggal_terakhir_service)";

$conn->query($query_service);

// 5. Non-aktifkan indeks sementara
$conn->query("ALTER TABLE data_customer DISABLE KEYS");

// 6. Insert data dengan batch besar menggunakan multi-query
$batch_size = 50000; // Meningkatkan ukuran batch
$total_records = $conn->query("SELECT COUNT(*) FROM combined_customers")->fetch_row()[0];
$batches = ceil($total_records / $batch_size);

$conn->autocommit(FALSE); // Matikan autocommit

for ($i = 0; $i < $batches; $i++) {
    $offset = $i * $batch_size;
    
    $insert_query = "INSERT IGNORE INTO data_customer
        (ktp_customer, nama_customer, nohp1_customer, tanggal_lahir, 
         ro_sales, ro_service, tanggal_terakhir_beli_motor, 
         tanggal_terakhir_service, area_dealer, id_faktur_terakhir, created_at)
        SELECT 
            no_ktp,
            nama_konsumen,
            no_hp,
            tanggal_lahir,
            ro_sales,
            ro_service,
            tanggal_terakhir_beli,
            tanggal_terakhir_service,
            area_dealer,
            id_faktur_terakhir,
            NOW()
        FROM combined_customers
        LIMIT $batch_size OFFSET $offset";
    
    $conn->query($insert_query);
    
    // Komit setiap batch
    $conn->commit();
    
    echo "Inserted batch " . ($i + 1) . "/$batches (" . ($offset + $batch_size) . " records)\n";
    
    // Bebaskan memori
    // $conn->free_result();
}

// 7. Aktifkan kembali indeks
$conn->query("ALTER TABLE data_customer ENABLE KEYS");
$conn->autocommit(TRUE);

echo "Total inserted: $total_records records\n";
?>