<?php
require("autoload.php");
require("../vendor/autoload.php");
require("../library/PHPExcel.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;


// Data dealer untuk pengecekan area
$stmt   = "SELECT * FROM `dealer`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while($row = mysqli_fetch_array($query)) {
    $dealer[$row['kode_yimm']] = $row['area'];
}

// Data faktur untuk pengecekan duplikat
$stmt   = "SELECT * FROM `faktur_temporary`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while($row = mysqli_fetch_array($query)) {
    $faktur[$row['nomor_rangka']] = $row['nomor_rangka'];
}

$_SESSION['import_errors']  = [];

// Cek apakah file diunggah
if (!isset($_FILES['filedata']) || $_FILES['filedata']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['import_errors'][] = "Tidak ada file yang diunggah atau terjadi kesalahan saat mengunggah.";

    header('location: ../import_faktur.php');
    exit;
}

$filename   = $_FILES['filedata']['name'];
$extension  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Cek apakah file adalah file Excel
if ($extension !== 'xlsx' && $extension !== 'xls') {
    $_SESSION['import_errors'][] = "Hanya file Excel (.xls atau .xlsx) yang diizinkan.";
    
    header('location: ../import_faktur.php');
    exit;
}

$target_file = "../library/excel.xlsx";

// Pindahkan file yang diunggah ke lokasi target
if (!move_uploaded_file($_FILES["filedata"]["tmp_name"], $target_file)) {
    $_SESSION['import_errors'][] = "Gagal memindahkan file yang diunggah.";

    header('location: ../import_faktur.php'); 
    exit;
}

$imported_count = 0;
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
    if ($worksheet->getCell('B1')->getValue() !== "Dealer Code" || $worksheet->getCell('G1')->getValue() !== "Frame No.") {
        throw new Exception("Format file Excel tidak valid. Pastikan file sesuai dengan template yang diberikan.");
    }

    $import_data = [];

    // Proses data Excel
    for ($row = 2; $row <= $excel_row; $row++) { // Mulai dari baris 2 (baris pertama adalah header)
        $kode_dealer = $worksheet->getCell('B' . $row)->getValue(); // Dealer Code

        if(isset($dealer[$kode_dealer])){
            $nomor_rangka       = $worksheet->getCell('G' . $row)->getValue(); // Frame No.
            $kabupaten          = $worksheet->getCell('T' . $row)->getValue(); // City
            $pekerjaan          = $worksheet->getCell('U' . $row)->getValue(); // Occupation
            $no_hp              = $worksheet->getCell('Y' . $row)->getValue(); // Phone
            $pendidikan         = $worksheet->getCell('Z' . $row)->getValue(); // Education
            $tenor_kredit       = $worksheet->getCell('AF' . $row)->getValue() ?? '-'; // Term Payment

            // Validasi data
            if (empty($nomor_rangka)) {
                $errors_summary['empty_nomor_rangka']['count']++;
                $errors_summary['empty_nomor_rangka']['rows'][] = $row;
            }
            else if(strlen($no_hp) < 11 || strlen($no_hp) > 14){
                $errors_summary['no_hp_invalid']['count']++;
                $errors_summary['no_hp_invalid']['rows'][] = $row;
            }
            else if (isset($faktur[$nomor_rangka])) {
                $errors_summary['duplicate']['count']++;
                $errors_summary['duplicate']['rows'][] = $row;
            }
            else{
                $import_data[] = [
                    $nomor_rangka, $kabupaten, $pekerjaan, $no_hp, $pendidikan, $tenor_kredit
                ];

                $imported_count++;
            }
        }
        else{
            $errors_summary['not_main_dealer']['count']++;
            $errors_summary['not_main_dealer']['rows'][] = $row;
        }
    }

    if (!empty($import_data)) {
        // Buat placeholder (?,?,?,...) untuk setiap data yang diimport
        $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?), ', count($import_data)), ', ');
        
        $sql = "INSERT INTO faktur_temporary (
                    nomor_rangka, kabupaten, pekerjaan, no_hp, pendidikan, tenor_kredit
                ) VALUES $placeholders";
    
        $stmt = $conn->prepare($sql);
        
        // Flatten array menjadi satu dimensi untuk bind_param
        $values = [];
        foreach ($import_data as $data) {
            foreach ($data as $val) {
                $values[] = $val;
            }
        }
    
        // Buat format tipe data untuk bind_param (misalnya "ssssssssssss")
        $types = str_repeat('s', 6 * count($import_data)); // Semua dianggap string ('s')
    
        // Gunakan call_user_func_array untuk bind_param
        $stmt->bind_param($types, ...$values);
    
        // Eksekusi query
        $stmt->execute();
    }

    // Buat ringkasan hasil impor
    $_SESSION['import_summary']['success'][] = $filename . " berhasil diimpor.";
    $_SESSION['import_summary']['success'][]  = "$imported_count data berhasil diimpor.";

    if ($errors_summary['no_hp_invalid']['count'] > 0) {
        $_SESSION['import_summary']['no_hp_invalid'][] = "{$errors_summary['no_hp_invalid']['count']} data tidak diimpor karena No. HP tidak sesuai standar pada baris: " . implode(', ', $errors_summary['no_hp_invalid']['rows']);
    }
    if ($errors_summary['duplicate']['count'] > 0) {
        $_SESSION['import_summary']['duplicate'][] = "{$errors_summary['duplicate']['count']} data tidak diimpor karena duplikat nomor rangka pada baris: " . implode(', ', $errors_summary['duplicate']['rows']);
    }
    if ($errors_summary['empty_nomor_rangka']['count'] > 0) {
        $_SESSION['import_summary']['empty_nomor_rangka'][] = "{$errors_summary['empty_nomor_rangka']['count']} data tidak diimpor karena nomor rangka kosong pada baris: " . implode(', ', $errors_summary['empty_nomor_rangka']['rows']);
    }
    if ($errors_summary['not_main_dealer']['count'] > 0) {
        $_SESSION['import_summary']['not_main_dealer'][] = "{$errors_summary['not_main_dealer']['count']} data tidak diimpor karena bukan Main Dealer pada baris: " . implode(', ', $errors_summary['not_main_dealer']['rows']);
    }
} 
catch (Exception $e) {
    $_SESSION['import_errors'][] = $e->getMessage();
}

header('location: ../import_faktur.php');
exit;
?>