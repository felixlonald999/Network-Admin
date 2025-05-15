<?php
require("autoload.php");
require("../vendor/autoload.php");
require("../library/PHPExcel.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Data dealer untuk pengecekan area
$stmt   = "SELECT * FROM `dealer`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while ($row = mysqli_fetch_array($query)) {
    $dealer[$row['kode_yimm']] = [
        'nama_dealer' => $row['nama_dealer'],
        'area' => $row['area'],
    ];
}

// Data faktur untuk pengecekan duplikat
$stmt   = "SELECT * FROM `faktur`";
$query  = mysqli_query($conn, $stmt) or die(mysqli_error($conn));
while ($row = mysqli_fetch_array($query)) {
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
    'tanggal_invalid'       => ['count' => 0, 'rows' => []],
];

try {
    // Muat file Excel
    $excel_obj = IOFactory::load($target_file);
    $worksheet = $excel_obj->getActiveSheet();
    $excel_row = $worksheet->getHighestRow();

    // Cek apakah file Excel memiliki header yang benar
    if ($worksheet->getCell('F1')->getFormattedValue() !== "Frame #" || $worksheet->getCell('AL1')->getFormattedValue() !== "Req Dealer") {
        throw new Exception("Format file Excel tidak valid. Pastikan file sesuai dengan template yang diberikan.");
    }

    $import_data = [];

    // Proses data Excel
    for ($row = 2; $row <= $excel_row; $row++) { // Mulai dari baris 2 (baris pertama adalah header)
        $kode_dealer = $worksheet->getCell('AL' . $row)->getFormattedValue(); // Dealer Code

        if (isset($dealer[$kode_dealer])) {
            $nama_dealer        = $dealer[$kode_dealer]['nama_dealer']; // Dealer Name
            $area_dealer        = $dealer[$kode_dealer]['area']; // Area
            $tipe_motor         = $worksheet->getCell('D' . $row)->getFormattedValue(); // Model
            $nomor_rangka       = $worksheet->getCell('F' . $row)->getFormattedValue(); // Frame No.
            $warna_motor        = $worksheet->getCell('E' . $row)->getFormattedValue(); // Faktur Color
            $nama_konsumen      = $worksheet->getCell('J' . $row)->getFormattedValue(); // Customer Name
            $alamat             = $worksheet->getCell('M' . $row)->getFormattedValue(); // Address1
            $kabupaten          = $worksheet->getCell('R' . $row)->getFormattedValue(); // City
            $pekerjaan          = $worksheet->getCell('X' . $row)->getFormattedValue() ?? '-'; // Occupation
            $tgl_lahir          = $worksheet->getCell('W' . $row)->getFormattedValue(); // Birth Date
            // $tanggal_lahir      = empty($tgl_lahir) ? null : date("Y-m-d", strtotime($tgl_lahir)); // Birth Date
            $no_hp              = $worksheet->getCell('T' . $row)->getFormattedValue(); // Phone
            $pendidikan         = $worksheet->getCell('AM' . $row)->getFormattedValue() ?? '-'; // Education
            $raw_ktp            = $worksheet->getCell('K' . $row)->getFormattedValue(); // KTP No.
            $tipe_pembelian     = $worksheet->getCell('AG' . $row)->getFormattedValue(); // Payment Type
            $tenor_kredit       = $worksheet->getCell('AI' . $row)->getFormattedValue() ?? '-'; // Term Payment
            $tanggal_beli_motor = $worksheet->getCell('AJ' . $row)->getFormattedValue(); // Purchase Date

            //menyesuaikan format no hp
            // dd($no_hp);
            $no_hp = str_replace(',', '.', $no_hp); // Ubah koma jadi titik biar aman dari Excel lokal
            if (is_numeric($no_hp) && preg_match('/E\+?/i', $no_hp)) {
                $no_hp = number_format((float)$no_hp, 0, '', ''); // Convert dari scientific ke angka full
            } else {
                $no_hp = (int)$no_hp;
            }
            // dd($no_hp);

            $no_hp = preg_replace('/\D/', '', $no_hp); // Bersihin semua karakter non-angka
            if (substr($no_hp, 0, 1) === '0') {
                $no_hp = '62' . substr($no_hp, 1); // Ganti 0 jadi 62
            } elseif (substr($no_hp, 0, 2) !== '62') {
                $no_hp = '62' . $no_hp; // Kalau bukan 62 di depan, tambahin
            }
            // dd($no_hp);

            //convert dan cek ktp
            if (is_numeric($raw_ktp) && preg_match('/E\+?/i', $raw_ktp)) {
                // Kalau ke-convert scientific, ubah jadi string angka utuh
                $no_ktp = number_format((float)$raw_ktp, 0, '', '');
            } else {
                $no_ktp = (string)$raw_ktp;
            }

            // Ubah tanggal lahir ke format Y-m-d
            if (is_numeric($tgl_lahir)) {
                $timestamp = Date::excelToTimestamp($tgl_lahir); // Ubah serial ke timestamp
                $tgl_lahir = date("Y-m-d", $timestamp);     // Format jadi Y-m-d
            } else if (empty($tgl_lahir)) {
                $tgl_lahir = null; // Jika kosong, set null
            } else {
                $tgl_lahir = date("Y-m-d", strtotime($tgl_lahir)); // Kalau bukan serial, coba parse biasa
            }

            // Ubah tanggal beli motor ke format Y-m-d
            if (is_numeric($tanggal_beli_motor)) {
                $timestamp = Date::excelToTimestamp($tanggal_beli_motor); // Ubah serial ke timestamp
                $tanggal_beli_motor = date("Y-m-d", $timestamp);     // Format jadi Y-m-d
            } else {
                $tanggal_beli_motor = date("Y-m-d", strtotime($tanggal_beli_motor)); // Kalau bukan serial, coba parse biasa
            }

            // Validasi data
            if (empty($nomor_rangka)) {
                $errors_summary['empty_nomor_rangka']['count']++;
                $errors_summary['empty_nomor_rangka']['rows'][] = $row;
            } else if (strlen($no_hp) < 11 || strlen($no_hp) > 14) {
                $errors_summary['no_hp_invalid']['count']++;
                $errors_summary['no_hp_invalid']['rows'][] = $row;
            } else if (isset($faktur[$nomor_rangka])) {
                $errors_summary['duplicate']['count']++;
                $errors_summary['duplicate']['rows'][] = $row;
            } else if ($tgl_lahir == "1900-01-01" || $tanggal_beli_motor == "1900-01-01" || $tanggal_beli_motor == "1970-01-01") {
                $errors_summary['tanggal_invalid']['count']++;
                $errors_summary['tanggal_invalid']['rows'][] = $row;
            } else {
                $import_data[] = [
                    $kode_dealer,
                    $nama_dealer,
                    $area_dealer,
                    $tipe_motor,
                    $warna_motor,
                    $nomor_rangka,
                    $nama_konsumen,
                    $alamat,
                    $kabupaten,
                    $pekerjaan,
                    $tgl_lahir,
                    $no_hp,
                    $pendidikan,
                    $no_ktp,
                    $tipe_pembelian,
                    $tenor_kredit,
                    $tanggal_beli_motor
                ];

                $imported_count++;
            }
        } else {
            $errors_summary['not_main_dealer']['count']++;
            $errors_summary['not_main_dealer']['rows'][] = $row;
        }
    }

    if (!empty($import_data)) {
        $batch_size = 2000;
        $batch_data = array_chunk($import_data, $batch_size);
        foreach ($batch_data as $batch) {
            // Buat placeholder (?,?,?,...) untuk setiap data yang diimport
            $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()), ', count($batch)), ', ');

            $sql = "INSERT INTO faktur (
                    kode_dealer, nama_dealer, area_dealer, tipe_motor, warna_motor, nomor_rangka,
                    nama_konsumen, alamat, kabupaten, pekerjaan, tanggal_lahir, no_hp, pendidikan, 
                    no_ktp, tipe_pembelian, tenor_kredit, tanggal_beli_motor, created_at
                ) VALUES $placeholders";

            $stmt = $conn->prepare($sql);

            // Flatten array menjadi satu dimensi untuk bind_param
            $values = [];
            foreach ($batch as $data) {
                foreach ($data as $val) {
                    $values[] = $val;
                }
            }

            // Buat format tipe data untuk bind_param (misalnya "ssssssssssss")
            $types = str_repeat('s', 17 * count($batch)); // Semua dianggap string ('s')

            // Gunakan call_user_func_array untuk bind_param
            $stmt->bind_param($types, ...$values);

            // Eksekusi query
            $stmt->execute();
        }
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
    if ($errors_summary['tanggal_invalid']['count'] > 0) {
        $_SESSION['import_summary']['tanggal_invalid'][] = "{$errors_summary['tanggal_invalid']['count']} data tidak diimpor karena tanggal lahir dan/atau tanggal beli motor tidak sesuai pada baris: " . implode(', ', $errors_summary['tanggal_invalid']['rows']);
    }
} catch (Exception $e) {
    $_SESSION['import_errors'][] = $e->getMessage();
}

header('location: ../import_faktur.php');
exit;
