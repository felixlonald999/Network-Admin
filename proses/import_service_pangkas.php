<?php
require("autoload.php");
require("../vendor/autoload.php");
// require("../library/PHPExcel.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

set_time_limit(0); // Hilangkan batas waktu

// Data dealer untuk pengecekan area
$stmt   = "SELECT * FROM `dealer`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while ($row = mysqli_fetch_array($query)) {
    $dealer[$row['kode_yimm']] = [
        'nama_dealer' => $row['nama_dealer'],
        'area' => $row['area'],
    ];
}

// Data service untuk pengecekan duplikat
$stmt   = "SELECT * FROM `service`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while ($row = mysqli_fetch_array($query)) {
    $service[$row['nomor_rangka']] = $row;
}

$stmt   = "SELECT * FROM `history_service`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while ($row = mysqli_fetch_array($query)) {
    $history[$row['tanggal_service']][$row['nomor_rangka']] = [
        'tanggal_service' => $row['tanggal_service'],
        'nomor_rangka' => $row['nomor_rangka'],
    ];
}

$_SESSION['import_errors']  = [];

// Cek apakah file diunggah
if (!isset($_FILES['filedata']) || $_FILES['filedata']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['import_errors'][] = "Tidak ada file yang diunggah atau terjadi kesalahan saat mengunggah.";

    header('location: ../import_service.php');
    exit;
}

$filename   = $_FILES['filedata']['name'];
$extension  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Cek apakah file adalah file Excel
if ($extension !== 'xlsx' && $extension !== 'xls') {
    $_SESSION['import_errors'][] = "Hanya file Excel (.xls atau .xlsx) yang diizinkan.";

    header('location: ../import_service.php');
    exit;
}

$target_file = "../library/excel.xlsx";

// Pindahkan file yang diunggah ke lokasi target
if (!move_uploaded_file($_FILES["filedata"]["tmp_name"], $target_file)) {
    $_SESSION['import_errors'][] = "Gagal memindahkan file yang diunggah.";

    header('location: ../import_service.php');
    exit;
}

$imported_count = 0;
$imported_history = 0;
$errors_summary = [
    'same_service_date'     => ['count' => 0, 'rows' => []],
    'no_hp_invalid'         => ['count' => 0, 'rows' => []],
    'duplicate'             => ['count' => 0, 'rows' => []],
    'empty_nomor_rangka'    => ['count' => 0, 'rows' => []],
    'not_main_dealer'       => ['count' => 0, 'rows' => []],
];

try {
    // Muat file Excel
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $excel_obj = $reader->load($target_file);
    
    $worksheet = $excel_obj->getActiveSheet();
    $excel_row = $worksheet->getHighestRow();

    $import_service = [];
    $import_history = [];
    $batch = 3000;

    if ($worksheet->getCell('A1')->getValue() !== "dealer" || $worksheet->getCell('L1')->getValue() !== "walkin") {
        throw new Exception("Format file Excel tidak valid. Pastikan file sesuai dengan template yang diberikan.");
    }

    // Proses data Excel
    for ($row = 2; $row <= $excel_row; $row++) { // Mulai dari baris 3 (baris pertama adalah header)
        $kode_dealer = $worksheet->getCell('A' . $row)->getValue(); // dealer

        if(isset($dealer[$kode_dealer])){
            $nama_dealer        = $dealer[$kode_dealer]['nama_dealer']; // Dealer Name
            $area_dealer        = $dealer[$kode_dealer]['area']; // Area
            $nopol              = $worksheet->getCell('B' . $row)->getValue(); // plate
            $nama_konsumen      = $worksheet->getCell('C' . $row)->getValue(); // Customer Name
            $no_ktp             = $worksheet->getCell('D' . $row)->getValue(); // KTP No.
            $alamat             = $worksheet->getCell('E' . $row)->getValue(); // Address1
            $no_hp              = $worksheet->getCell('F' . $row)->getValue(); // Phone
            $tipe_motor         = $worksheet->getCell('G' . $row)->getValue(); // Model
            $nomor_rangka       = $worksheet->getCell('H' . $row)->getValue(); // Frame No.
            $kilometer          = $worksheet->getCell('I' . $row)->getValue(); // Kilometer
            $tipe_service       = $worksheet->getCell('J' . $row)->getValue(); // Service Type
            $sparepart          = $worksheet->getCell('K' . $row)->getValue(); // Sparepart
            $tanggal_service    = date("Y-m-d", strtotime($worksheet->getCell('L' . $row)->getValue())); // Service Date

            // Validasi data
            if (empty($nomor_rangka)) {
                $errors_summary['empty_nomor_rangka']['count']++;
                $errors_summary['empty_nomor_rangka']['rows'][] = $row;
            }
            else if(strlen(str_replace(' ', '', $no_hp)) < 11 || strlen(str_replace(' ', '', $no_hp)) > 14){
                $errors_summary['no_hp_invalid']['count']++;
                $errors_summary['no_hp_invalid']['rows'][] = $row;
            }
            else{
                //Checking in history_service
                $duplicate_norangka_history = array_column($import_history, 3);
                $duplicate_tanggal_service_history = array_column($import_history, 10);

                if(in_array($tanggal_service, $duplicate_tanggal_service_history) && in_array($nomor_rangka, $duplicate_norangka_history)){ //checking in array
                    $errors_summary['same_service_date']['count']++;
                    $errors_summary['same_service_date']['rows'][] = $row;
                } else if (isset($history[$tanggal_service][$nomor_rangka])){ //checking in database history_service
                    $errors_summary['same_service_date']['count']++;
                    $errors_summary['same_service_date']['rows'][] = $row;
                } else {
                    $import_history[] = [
                        $kode_dealer, $nama_dealer, $area_dealer, $nomor_rangka, $nopol, $nama_konsumen, $no_hp, $no_ktp,
                        $kilometer, $tipe_service, $tanggal_service, $sparepart
                    ];

                    $history[$tanggal_service][$nomor_rangka] = [
                        'tanggal_service' => $tanggal_service,
                        'nomor_rangka' => $nomor_rangka
                    ];

                    if (count($import_history) >= $batch) {
                        history_insert($conn, $import_history);
                        $import_history = []; // Reset array setelah insert
                    }

                    $imported_history++;
                }

                //Check nomor rangka di service
                $duplicate_nomor_rangka = array_column($import_service, 3);

                if (in_array($nomor_rangka, $duplicate_nomor_rangka)) { //checking in array
                    $index = array_search($nomor_rangka, $duplicate_nomor_rangka);
                    $existing_data = $import_service[$index];

                    $check_tanggal_service = date("Y-m-d", strtotime($existing_data[12]));

                    if ($tanggal_service > $check_tanggal_service) {
                        $import_service[$index] = [
                            $kode_dealer, $nama_dealer, $area_dealer, $nomor_rangka, $nopol, $tipe_motor,
                            $nama_konsumen, $alamat, $no_hp, $no_ktp, $kilometer, $tipe_service, $tanggal_service
                        ];
                    }

                    $errors_summary['duplicate']['count']++;
                    $errors_summary['duplicate']['rows'][] = $row;

                } else if(isset($service[$nomor_rangka])){ //checking in database service

                    $errors_summary['duplicate']['count']++;
                    $errors_summary['duplicate']['rows'][] = $row;

                } else {
                    $import_service[] = [
                        $kode_dealer, $nama_dealer, $area_dealer, $nomor_rangka, $nopol, $tipe_motor,
                        $nama_konsumen, $alamat, $no_hp, $no_ktp, $kilometer, $tipe_service, $tanggal_service
                    ];

                    $service[$nomor_rangka] = [
                        'kode_dealer' => $kode_dealer,
                        'nama_dealer' => $nama_dealer,
                        'area_dealer' => $area_dealer,
                        'nomor_rangka' => $nomor_rangka,
                        'nopol' => $nopol,
                        'tipe_motor' => $tipe_motor,
                        'nama_konsumen' => $nama_konsumen,
                        'alamat' => $alamat,
                        'no_hp' => $no_hp,
                        'no_ktp' => $no_ktp,
                        'kilometer' => $kilometer,
                        'tipe_service' => $tipe_service,
                        'tanggal_terakhir_service' => $tanggal_service
                    ];

                    if (count($import_service) >= $batch) {
                        insert_batch($conn, $import_service);
                        $import_service = []; // Reset array setelah insert
                    }

                    $imported_count++;
                }


            }
        } else{
            $errors_summary['not_main_dealer']['count']++;
            $errors_summary['not_main_dealer']['rows'][] = $row;
        }
    }

    if (!empty($import_service)) {
        insert_batch($conn, $import_service);
    }

    if (!empty($import_history)) {
        history_insert($conn, $import_history);
    }

    // Buat ringkasan hasil impor
    $_SESSION['import_summary']['success']  = "$imported_count data service berhasil diimpor.";
    // $_SESSION['import_errors'][] = "Waktu eksekusi: $execution_time detik";

    if ($errors_summary['same_service_date']['count'] > 0) {
        $_SESSION['import_summary']['same_service_date'] = "{$errors_summary['same_service_date']['count']} data tidak diimpor karena Tanggal Service di hari yang sama pada baris: " . implode(', ', $errors_summary['same_service_date']['rows']);
    }
    if ($errors_summary['no_hp_invalid']['count'] > 0) {
        $_SESSION['import_summary']['no_hp_invalid'] = "{$errors_summary['no_hp_invalid']['count']} data tidak diimpor karena No. HP tidak sesuai standar pada baris: " . implode(', ', $errors_summary['no_hp_invalid']['rows']);
    }
    if ($errors_summary['duplicate']['count'] > 0) {
        $_SESSION['import_summary']['duplicate'] = "{$errors_summary['duplicate']['count']} data tidak diimpor karena duplikat nomor rangka pada baris: " . implode(', ', $errors_summary['duplicate']['rows']);
    }
    if ($errors_summary['empty_nomor_rangka']['count'] > 0) {
        $_SESSION['import_summary']['empty_nomor_rangka'] = "{$errors_summary['empty_nomor_rangka']['count']} data tidak diimpor karena nomor rangka kosong pada baris: " . implode(', ', $errors_summary['empty_nomor_rangka']['rows']);
    }
    if ($errors_summary['not_main_dealer']['count'] > 0) {
        $_SESSION['import_summary']['not_main_dealer'] = "{$errors_summary['not_main_dealer']['count']} data tidak diimpor karena bukan Main Dealer pada baris: " . implode(', ', $errors_summary['not_main_dealer']['rows']);
    }
} catch (Exception $e) {
    $_SESSION['import_errors'][] = $e->getMessage();
}


function history_insert($conn, $import_data){
    $conn->query("ALTER TABLE history_service DISABLE KEYS;");
    $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()), ', count($import_data)), ', ');

    // Menyiapkan SQL
    $sql = "INSERT INTO history_service (
                kode_dealer, nama_dealer, area_dealer, nomor_rangka, nopol, nama_konsumen, 
                no_hp, no_ktp, kilometer, tipe_service, tanggal_service, sparepart, created_at
            ) VALUES $placeholders;";

    // Menyiapkan statement
    $stmt = $conn->prepare($sql);

    // Flatten array menjadi satu dimensi
    $values = [];
    foreach ($import_data as $data) {
        foreach ($data as $val) {
            $values[] = $val;
        }
    }

    // Menghitung jumlah tipe data (misalnya semua data bertipe string)
    $types = str_repeat('s', count($values)); // Semua data dianggap string 
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        echo "Batch insert berhasil!";
        $conn->query("ALTER TABLE history_service ENABLE KEYS;");
    } else {
        echo "Error: " . $stmt->error;
    }
}

function insert_batch($conn, $import_data){
    $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()), ', count($import_data)), ', ');

    // Menyiapkan SQL
    $sql = "INSERT INTO service (
                kode_dealer, nama_dealer, area_dealer, nomor_rangka, nopol, tipe_motor, 
                nama_konsumen, alamat, no_hp, no_ktp, kilometer, tipe_service, tanggal_terakhir_service, created_at
            ) VALUES $placeholders
            ON DUPLICATE KEY UPDATE 
                tanggal_terakhir_service = IF(VALUES(tanggal_terakhir_service) > tanggal_terakhir_service, VALUES(tanggal_terakhir_service), tanggal_terakhir_service),
                kode_dealer = VALUES(kode_dealer),
                nama_dealer = VALUES(nama_dealer),
                area_dealer = VALUES(area_dealer),
                nopol = VALUES(nopol),
                tipe_motor = VALUES(tipe_motor),
                nama_konsumen = VALUES(nama_konsumen),
                alamat = VALUES(alamat),
                no_hp = VALUES(no_hp),
                no_ktp = VALUES(no_ktp),
                kilometer = VALUES(kilometer),
                tipe_service = VALUES(tipe_service);";

    // Menyiapkan statement
    $stmt = $conn->prepare($sql);

    // Flatten array menjadi satu dimensi
    $values = [];
    foreach ($import_data as $data) {
        foreach ($data as $val) {
            $values[] = $val;
        }
    }

    // Menghitung jumlah tipe data (misalnya semua data bertipe string)
    $types = str_repeat('s', count($values)); // Semua data dianggap string 
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        echo "Batch insert berhasil!";
    } else {
        echo "Error: " . $stmt->error;
    }
}

header(header: 'location: ../import_service.php');
exit;

?>