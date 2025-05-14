<?php

require("../autoload.php");

header('Content-Type: application/json');

// Konfigurasi Database
$host   = 'localhost';
$user   = 'root';
$pass   = '';
$db     = 'yamahast_data';

// Koneksi ke Database
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi database gagal: " . $conn->connect_error]));
}

// Ambil parameter pagination
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10000;  // Default 1000 row per request
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;   // Default mulai dari awal
$area = isset($_GET['area']) ? $_GET['area'] : ''; // Default area dealer
$kode_dealer = isset($_GET['kode_dealer']) ? $_GET['kode_dealer'] : ''; // Default kode dealer
// Query untuk mengambil data
// $query = "SELECT 
//             s.nomor_rangka, s.tanggal_terakhir_service,
//             COUNT(hs.id) AS jumlah_service
//         FROM `service` s  
//         LEFT JOIN `history_service` hs ON s.nomor_rangka = hs.nomor_rangka
//         where s.tanggal_terakhir_service <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
//         GROUP BY s.nomor_rangka
//         ORDER BY jumlah_service DESC
//         LIMIT ? OFFSET ?";
$query = "SELECT 
            s.area_dealer, s.nama_dealer, f.nama_konsumen,f.no_ktp, s.no_hp, f.tipe_motor, 
            f.tanggal_beli_motor, f.nomor_rangka, s.kode_dealer, s.tanggal_terakhir_service,
            COUNT(hs.id) AS jumlah_service
        FROM `faktur` f 
        LEFT JOIN `service` s ON f.nomor_rangka = s.nomor_rangka 
        LEFT JOIN `history_service` hs ON f.nomor_rangka = hs.nomor_rangka
        where s.tanggal_terakhir_service <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) and s.area_dealer = ? and s.kode_dealer = ? and f.no_ktp not like '%0000000000000000%' and f.no_ktp != '0' and f.no_ktp != '-'
        GROUP BY f.nomor_rangka
        ORDER BY jumlah_service DESC
        LIMIT ? OFFSET ?";


$stmt = $conn->prepare($query);
$stmt->bind_param("ssii",$area, $kode_dealer, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

// Output JSON
echo json_encode([
    "status" => "success",
    "limit" => $limit,
    "offset" => $offset,
    "data" => $data
]);

// Tutup koneksi
$conn->close();

?>
