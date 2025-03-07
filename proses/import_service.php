<?php
require("autoload.php");
require("../vendor/autoload.php");
// require("../library/PHPExcel.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;


// Data dealer untuk pengecekan area
$stmt   = "SELECT * FROM `dealer`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while($row = mysqli_fetch_array($query)) {
    $dealer[$row['kode_yimm']] = [
        'nama_dealer' => $row['nama_dealer'],
        'area' => $row['area'],
    ];
}

// Data service untuk pengecekan duplikat
$stmt   = "SELECT * FROM `service`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while($row = mysqli_fetch_array($query)) {
    $service[$row['nomor_rangka']] = $row;
}

$stmt   = "SELECT * FROM `history_service`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while($row = mysqli_fetch_array($query)) {
    $history[$row['tanggal_service']] = [
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
    'no_hp_invalid'         => ['count' => 0, 'rows' => []],
    'duplicate'             => ['count' => 0, 'rows' => []],
    'empty_nomor_rangka'    => ['count' => 0, 'rows' => []],
    'not_main_dealer'       => ['count' => 0, 'rows' => []],
];

try {
    // Muat file Excel
    $excel_obj = IOFactory::load($target_file);
    $worksheet = $excel_obj->getActiveSheet();
    $excel_row = $worksheet->getHighestRow();

    // Cek apakah file Excel memiliki header yang benar
    if ($worksheet->getCell('C1')->getValue() !== "dealer" || $worksheet->getCell('V1')->getValue() !== "no_rangka") {
        throw new Exception("Format file Excel tidak valid. Pastikan file sesuai dengan template yang diberikan.");
    }

    $import_service = [];
    $import_history = [];
    $batch = 1000;

    // Proses data Excel
    for ($row = 3; $row <= $excel_row; $row++) { // Mulai dari baris 3 (baris pertama adalah header)
        $kode_dealer = $worksheet->getCell('C' . $row)->getValue(); // dealer

        if(isset($dealer[$kode_dealer])){
            $nama_dealer        = $dealer[$kode_dealer]['nama_dealer']; // Dealer Name
            $area_dealer        = $dealer[$kode_dealer]['area']; // Area
            $nopol              = $worksheet->getCell('G' . $row)->getValue(); // plate
            $nama_konsumen      = $worksheet->getCell('M' . $row)->getValue(); // Customer Name
            $no_ktp             = $worksheet->getCell('N' . $row)->getValue(); // KTP No.
            $alamat             = $worksheet->getCell('Q' . $row)->getValue(); // Address1
            $no_hp              = $worksheet->getCell('R' . $row)->getValue(); // Phone
            $tipe_motor         = $worksheet->getCell('U' . $row)->getValue(); // Model
            $nomor_rangka       = $worksheet->getCell('V' . $row)->getValue(); // Frame No.
            $kilometer          = $worksheet->getCell('X' . $row)->getValue(); // Kilometer
            $tipe_service       = $worksheet->getCell('AL' . $row)->getValue(); // Service Type
            $sparepart          = $worksheet->getCell('AO' . $row)->getValue(); // Sparepart
            $tanggal_service    = date("Y-m-d", strtotime($worksheet->getCell('BJ' . $row)->getValue())); // Service Date

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
                if(isset($history[$tanggal_service]) && isset($history[$nomor_rangka])) {
                    $errors_summary['same_service_date']['count']++;
                    $errors_summary['same_service_date']['rows'][] = $row;
                } else {
                    $import_history[] = [
                        $kode_dealer, $nama_dealer, $area_dealer, $nomor_rangka, $nopol, $nama_konsumen, $no_hp, $no_ktp,
                        $kilometer, $tipe_service, $tanggal_service, $sparepart
                    ];

                    if (count($import_history) >= $batch) {
                        history_insert($conn, $import_history);
                        $import_history = []; // Reset array setelah insert
                    }
                }

                //Check nomor rangka ada di service
                if(isset($service[$nomor_rangka])){
                    $db_kode_dealer = $service[$nomor_rangka]['kode_dealer'];
                    $db_nama_dealer = $service[$nomor_rangka]['nama_dealer'];
                    $db_area_dealer = $service[$nomor_rangka]['area_dealer'];
                    $db_nopol = $service[$nomor_rangka]['nopol'];
                    $db_nama = $service[$nomor_rangka]['nama_konsumen'];
                    $db_alamat = $service[$nomor_rangka]['alamat'];
                    $db_no_hp = $service[$nomor_rangka]['no_hp'];
                    $db_no_ktp = $service[$nomor_rangka]['no_ktp'];
                    $db_kilometer = $service[$nomor_rangka]['kilometer'];
                    $db_tipe_service = $service[$nomor_rangka]['tipe_service'];
                    $db_tanggal_service = date("d-m-Y", strtotime($service[$nomor_rangka]['tanggal_terakhir_service']));
                    $tanggal_terakhir_service = date("d-m-Y",strtotime($tanggal_service));

                    if ($tanggal_terakhir_service > $db_tanggal_service) { // Jika tanggal baru lebih dari yang ada di database, update
                        $query = "UPDATE service 
                                 SET tanggal_terakhir_service = ?
                                 WHERE nomor_rangka = ?";
                        $stmt_update = $conn->prepare($query);
                        $stmt_update->bind_param("ss", $tanggal_service, $nomor_rangka);
                        $stmt_update->execute();

                        if ($db_kode_dealer != $kode_dealer || $db_nama_dealer != $nama_dealer || $db_area_dealer != $area_dealer 
                                || $db_nama_dealer != $nama_dealer || $db_area_dealer != $area_dealer || $db_nopol != $nopol 
                                || $db_nama != $nama_konsumen || $db_alamat != $alamat || $db_no_hp != $no_hp || $db_no_ktp != $no_ktp
                                || $db_kilometer != $kilometer || $db_tipe_service != $tipe_service) {
                            $update_query = "UPDATE service 
                                            SET kode_dealer = ?, nama_dealer = ?, area_dealer = ?, nopol = ?, nama_konsumen = ?, alamat = ?, no_ktp = ?, no_hp = ?, kilometer = ?, tipe_service = ?
                                            WHERE nomor_rangka = ?";
                            $stmt_update = $conn->prepare($update_query);
                            $stmt_update->bind_param("sssssssssss", $kode_dealer, $nama_dealer, $area_dealer, $nopol, $nama_konsumen, $alamat, 
                                                        $no_ktp, $no_hp, $kilometer, $tipe_service, $nomor_rangka);
                            $stmt_update->execute();
                        }
                        $errors_summary['updated_data']['count']++;
                        $errors_summary['updated_data']['rows'][] = $row;
                    } else {
                        $errors_summary['duplicate']['count']++;
                        $errors_summary['duplicate']['rows'][] = $row;
                    }
                } else {
                    $import_service[] = [
                        $kode_dealer, $nama_dealer, $area_dealer, $nomor_rangka, $nopol, $tipe_motor,
                        $nama_konsumen, $alamat, $no_hp, $no_ktp, $kilometer, $tipe_service, $tanggal_service
                    ];
                    
                    if (count($import_service) >= $batch) {
                        insert_batch($conn, $import_service);
                        $import_service = []; // Reset array setelah insert
                    }
    
                    $imported_count++;
                }

            }
        }
        else{
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
    $_SESSION['import_summary']['success']  = "$imported_count data berhasil diimpor.";

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
} 
catch (Exception $e) {
    $_SESSION['import_errors'][] = $e->getMessage();
}


function history_insert($conn, $import_data){
    $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()), ', count($import_data)), ', ');

    // Menyiapkan SQL
    $sql = "INSERT INTO history_service (
                kode_dealer, nama_dealer, area_dealer, nomor_rangka, nopol, nama_konsumen, 
                no_hp, no_ktp, kilometer, tipe_service, tanggal_service, sparepart, created_at
            ) VALUES $placeholders";

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

function insert_batch($conn, $import_data) {
    $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()), ', count($import_data)), ', ');

    // Menyiapkan SQL
    $sql = "INSERT INTO service (
                kode_dealer, nama_dealer, area_dealer, nomor_rangka, nopol, tipe_motor, 
                nama_konsumen, alamat, no_hp, no_ktp, kilometer, tipe_service, tanggal_terakhir_service, created_at
            ) VALUES $placeholders";

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

// echo $countt;

header('location: ../import_service.php');
exit;
?>