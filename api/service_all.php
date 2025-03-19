

<?php
require("autoload.php");

// Ambil parameter GET
$tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? $_GET['tahun'] : null;
$bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? $_GET['bulan'] : null;
$area = isset($_GET['area']) ? $_GET['area'] : 'SURABAYA INSIDE';
$dealer = isset($_GET['dealer']) && $_GET['dealer'] !== '' ? $_GET['dealer'] : null;
$dateRange = isset($_GET['dateRange']) && $_GET['dateRange'] !== '' ? $_GET['dateRange'] : null;
// $ro_service = isset($_GET['ro_service']) && $_GET['ro_service'] !== '' ? $_GET['ro_service'] : null;
$ro_service = isset($_GET['ro_service']) && $_GET['ro_service'] !== '' ? urldecode($_GET['ro_service']) : null;

// Bangun query
$stmt = "SELECT 
            f.area_dealer, f.nama_dealer, f.nama_konsumen, f.no_hp, f.tipe_motor, 
            f.tanggal_beli_motor, f.nomor_rangka, s.tanggal_terakhir_service,
            COUNT(hs.id) AS jumlah_service
        FROM `faktur` f 
        LEFT JOIN `service` s ON f.nomor_rangka = s.nomor_rangka 
        LEFT JOIN `history_service` hs ON f.nomor_rangka = hs.nomor_rangka  
        WHERE f.area_dealer = ? 
        ";

$types = "s"; // Start dengan string (ss) untuk area & dealer
$params = [$area];

// Filter tahun & bulan

if ($dealer !== null) {
    $stmt .= " AND f.nama_dealer = ?";
    $types .= "s";
    $params[] = $dealer;
    # code...
}

if ($tahun !== null) {
    $stmt .= " AND YEAR(f.tanggal_beli_motor) = ?";
    $types .= "i";
    $params[] = $tahun;
}
if ($bulan !== null) {
    $stmt .= " AND MONTH(f.tanggal_beli_motor) = ?";
    $types .= "i";
    $params[] = $bulan;
}

// Filter berdasarkan range tanggal
if ($dateRange !== null) {
    list($startDate, $endDate) = explode(' - ', $dateRange);
    $stmt .= " AND s.tanggal_terakhir_service BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $startDate;
    $params[] = $endDate;
}

$stmt .= " GROUP BY f.nomor_rangka";

if ($ro_service !== null) {
    // Gunakan HAVING dengan jumlah_service (alias dari COUNT(hs.id))
    $stmt .= " HAVING jumlah_service ";
    switch ($ro_service) {
        case "0":
            $stmt .= "= 0";
            break;
        case "1":
            $stmt .= "BETWEEN 1 AND 5";
            break;
        case "6":
            $stmt .= "BETWEEN 6 AND 10";
            break;
        case "10":
            $stmt .= "> 10";
            break;
        default:
            break;
    }
}

// Eksekusi query
$query = $conn->prepare($stmt);
$query->bind_param($types, ...$params);
$query->execute();
$res = $query->get_result();
$row = $res->fetch_all(MYSQLI_ASSOC);

// Tampilkan hasil dalam JSON
echo json_encode($row);

// Tutup koneksi

?>
