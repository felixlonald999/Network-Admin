<?php
require("autoload.php");

ini_set('memory_limit', '1024M');

$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : null;
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : null;
$tahun_akhir = isset($_GET['tahun_akhir']) ? $_GET['tahun_akhir'] : null;
$bulan_akhir = isset($_GET['bulan_akhir']) ? $_GET['bulan_akhir'] : null;
$area = isset($_GET['area']) ? $_GET['area'] : null;

// Mulai membangun query SQL
$stmt = "SELECT * FROM `history_service`";
$params = [];
$types = '';

// Buat array untuk menampung kondisi WHERE
$conditions = [];

// Logika untuk rentang tanggal
if ($tahun && $bulan && $bulan_akhir) {
    $tanggal_awal = sprintf('%s-%s-01', $tahun, $bulan);
    $tanggal_akhir = date('Y-m-t', strtotime(sprintf('%s-%s-01', $tahun, $bulan_akhir)));
    $conditions[] = "`tanggal_service` BETWEEN ? AND ?";
    $params[] = $tanggal_awal;
    $params[] = $tanggal_akhir;
    $types .= 'ss';
} else if ($tahun && $bulan) {
    $conditions[] = "YEAR(`tanggal_service`) = ? AND MONTH(`tanggal_service`) = ?";
    $params[] = $tahun;
    $params[] = $bulan;
    $types .= 'ii';
} else if ($tahun) {
    $conditions[] = "YEAR(`tanggal_service`) = ?";
    $params[] = $tahun;
    $types .= 'i';
} else if ($bulan) {
    $conditions[] = "MONTH(`tanggal_service`) = ?";
    $params[] = $bulan;
    $types .= 'i';
} else if ($bulan && $bulan_akhir) {
    $conditions[] = "MONTH(`tanggal_service`) BETWEEN ? AND ?";
    $params[] = $bulan;
    $params[] = $bulan_akhir;
    $types .= 'ii';
}

// Tambahkan kondisi area jika ada
if ($area) {
    $conditions[] = "`area_dealer` = ?";
    $params[] = $area;
    $types .= 's';
}

// Gabungkan semua kondisi dengan WHERE
if (count($conditions) > 0) {
    $stmt .= " WHERE " . implode(" AND ", $conditions);
}

// Siapkan dan jalankan query
$query = $conn->prepare($stmt);
if (!empty($params)) {
    $query->bind_param($types, ...$params);
}
$query->execute();
$res = $query->get_result();
$row = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode($row);
?>