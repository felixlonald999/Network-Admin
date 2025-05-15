<?php
require("autoload.php");

set_time_limit(0); // Hilangkan batas waktu
ini_set('memory_limit', '3072M'); // Tingkatkan memori

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
    tanggal_lahir DATE,
    kabupaten VARCHAR(255),
    no_hp VARCHAR(255),
    pekerjaan VARCHAR(100),
    pendidikan VARCHAR(100),
    ro_sales INT DEFAULT 0,
    ro_service INT DEFAULT 0,
    tanggal_terakhir_beli_motor DATE,
    kode_dealer_beli_terakhir VARCHAR(100),
    nama_dealer_beli_terakhir VARCHAR(100),
    area_dealer_beli_terakhir VARCHAR(100),
    tipe_pembelian_motor VARCHAR(100),
    tenor_kredit VARCHAR(100),
    tanggal_terakhir_service DATE,
    kode_dealer_service_terakhir VARCHAR(100),
    nama_dealer_service_terakhir VARCHAR(100),
    total_omzet_service INT DEFAULT 0,
    id_faktur_terakhir INT,
    PRIMARY KEY (no_ktp, nama_konsumen)
)");

// 3. Proses data faktur ke temporary table
$query_faktur = "INSERT INTO combined_customers
SELECT 
    ranked.no_ktp,
    ranked.nama_konsumen,
    ranked.tanggal_lahir,
    ranked.kabupaten,
    ranked.no_hp,
    ranked.pekerjaan,
    ranked.pendidikan,
    ranked.ro_sales,
    0 AS ro_service,
    ranked.tanggal_beli_motor AS tanggal_terakhir_beli_motor,
    ranked.kode_dealer AS kode_dealer_beli_terakhir,
    ranked.nama_dealer AS nama_dealer_beli_terakhir,
    ranked.area_dealer AS area_dealer_beli_terakhir,
    ranked.tipe_pembelian AS tipe_pembelian_motor,
    ranked.tenor_kredit,
    NULL AS tanggal_terakhir_service,
    NULL AS kode_dealer_service_terakhir,
    NULL AS nama_dealer_service_terakhir,
    0 AS total_omzet_service,
    ranked.id AS id_faktur_terakhir
FROM (
    SELECT 
        f.*,
        COUNT(f.id) OVER (PARTITION BY f.no_ktp, f.nama_konsumen) AS ro_sales,
        ROW_NUMBER() OVER (
            PARTITION BY f.no_ktp, f.nama_konsumen
            ORDER BY f.tanggal_beli_motor DESC, f.id DESC
        ) AS rn
    FROM faktur f
) ranked
WHERE ranked.rn = 1
ON DUPLICATE KEY UPDATE
    tanggal_lahir = VALUES(tanggal_lahir),
    kabupaten = VALUES(kabupaten),
    no_hp = VALUES(no_hp),
    pekerjaan = VALUES(pekerjaan),
    pendidikan = VALUES(pendidikan),
    ro_sales = VALUES(ro_sales),
    tanggal_terakhir_beli_motor = VALUES(tanggal_terakhir_beli_motor),
    kode_dealer_beli_terakhir = VALUES(kode_dealer_beli_terakhir),
    nama_dealer_beli_terakhir = VALUES(nama_dealer_beli_terakhir),
    area_dealer_beli_terakhir = VALUES(area_dealer_beli_terakhir),
    tipe_pembelian_motor = VALUES(tipe_pembelian_motor),
    tenor_kredit = VALUES(tenor_kredit),
    id_faktur_terakhir = VALUES(id_faktur_terakhir);
";

$conn->query($query_faktur);

// 4. Proses data service ke temporary table
$query_service = "INSERT INTO combined_customers
SELECT 
    ranked.no_ktp,
    ranked.nama_konsumen,
    NULL AS tanggal_lahir,
    NULL AS kabupaten,
    ranked.no_hp,
    NULL AS pekerjaan,
    NULL AS pendidikan,
    0 AS ro_sales,
    ranked.ro_service,
    NULL AS tanggal_terakhir_beli_motor,
    NULL AS kode_dealer_beli_terakhir,
    NULL AS nama_dealer_beli_terakhir,
    NULL AS area_dealer_beli_terakhir,
    NULL AS tipe_pembelian_motor,
    NULL AS tenor_kredit,
    ranked.tanggal_service AS tanggal_terakhir_service,
    ranked.kode_dealer AS kode_dealer_service_terakhir,
    ranked.nama_dealer AS nama_dealer_service_terakhir,
    ranked.total_omzet AS total_omzet_service,
    NULL AS id_faktur_terakhir
FROM (
    SELECT 
        hs.*,
        SUM(hs.omzet) OVER (PARTITION BY hs.no_ktp, hs.nama_konsumen) AS total_omzet,
        COUNT(hs.id) OVER (PARTITION BY hs.no_ktp, hs.nama_konsumen) AS ro_service,
        ROW_NUMBER() OVER (
            PARTITION BY hs.no_ktp, hs.nama_konsumen
            ORDER BY hs.tanggal_service DESC, hs.id DESC
        ) AS rn
    FROM history_service hs
) ranked
WHERE ranked.rn = 1
ON DUPLICATE KEY UPDATE
    no_hp = VALUES(no_hp),
    ro_service = VALUES(ro_service),
    tanggal_terakhir_service = VALUES(tanggal_terakhir_service),
    kode_dealer_service_terakhir = VALUES(kode_dealer_service_terakhir),
    nama_dealer_service_terakhir = VALUES(nama_dealer_service_terakhir),
	 total_omzet_service = VALUES(total_omzet_service);
";

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

    // Ambil data dari temporary table dan masukkan ke data_customer
    // Menggunakan ON DUPLICATE KEY UPDATE untuk menghindari duplikasi dan memperbarui data yang ada
    $insert_query = "INSERT INTO data_customer (
        ktp_customer, nama_customer, tanggal_lahir, kabupaten, nohp1_customer, pekerjaan, pendidikan,
        ro_sales, ro_service, tanggal_terakhir_beli_motor, kode_dealer_beli_terakhir, nama_dealer_beli_terakhir,
        area_dealer_beli_terakhir, tipe_pembelian_motor, tenor_kredit, tanggal_terakhir_service, kode_dealer_service_terakhir,
        nama_dealer_service_terakhir, total_omzet_service, id_faktur_terakhir, created_at)
        SELECT
            no_ktp, nama_konsumen, tanggal_lahir, kabupaten, no_hp, pekerjaan, pendidikan, ro_sales, ro_service,
            tanggal_terakhir_beli_motor, kode_dealer_beli_terakhir, nama_dealer_beli_terakhir, area_dealer_beli_terakhir,
            tipe_pembelian_motor, tenor_kredit, tanggal_terakhir_service, kode_dealer_service_terakhir, nama_dealer_service_terakhir,
            total_omzet_service, id_faktur_terakhir, NOW()
        FROM combined_customers
        LIMIT $batch_size OFFSET $offset
        ON DUPLICATE KEY UPDATE
            tanggal_lahir = VALUES(tanggal_lahir),
            kabupaten = VALUES(kabupaten),
            nohp1_customer = VALUES(nohp1_customer),
            pekerjaan = VALUES(pekerjaan),
            pendidikan = VALUES(pendidikan),
            ro_sales = VALUES(ro_sales),
            ro_service = VALUES(ro_service), 
            tanggal_terakhir_beli_motor = VALUES(tanggal_terakhir_beli_motor),
            kode_dealer_beli_terakhir = VALUES(kode_dealer_beli_terakhir),
            nama_dealer_beli_terakhir = VALUES(nama_dealer_beli_terakhir),
            area_dealer_beli_terakhir = VALUES(area_dealer_beli_terakhir),
            tipe_pembelian_motor = VALUES(tipe_pembelian_motor),
            tenor_kredit = VALUES(tenor_kredit),
            tanggal_terakhir_service = VALUES(tanggal_terakhir_service),
            kode_dealer_service_terakhir = VALUES(kode_dealer_service_terakhir),
            nama_dealer_service_terakhir = VALUES(nama_dealer_service_terakhir),
            total_omzet_service = VALUES(total_omzet_service),
            id_faktur_terakhir = VALUES(id_faktur_terakhir);
    ";

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
