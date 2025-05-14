<?php
require("autoload.php");
require("layout/header.php");
require("layout/navbar.php");

if(!isset($_SESSION['auth'])){
    header('location: login.php');
    exit;
}

// Membuat id unik untuk tabel berdasarkan waktu
$tableId = "motorTable_" . time();

// Fungsi untuk mengekstrak nama dasar motor
function extractBaseMotorName($motor_type) {
    // List pola pengelompokan motor
    $patterns = [
        // NMAX dan variannya
        '/ALL NEW NMAX 155.*|NMAX 155.*|NMAX.*/' => 'NMAX',
        
        // MIO dan variannya
        '/MIO M3.*|MIO S.*|MIO Z.*|MIO.*/' => 'MIO',
        
        // AEROX dan variannya
        '/ALL NEW AEROX 155.*|AEROX 155.*|AEROX.*/' => 'AEROX',
        
        // XMAX dan variannya
        '/XMAX 250.*|XMAX.*/' => 'XMAX',
        
        // LEXI dan variannya
        '/LEXI 125.*|LEXI S.*|LEXI.*/' => 'LEXI',
        
        // JUPITER dan variannya
        '/JUPITER MX.*|JUPITER Z.*|JUPITER.*/' => 'JUPITER',
        
        // VIXION dan variannya
        '/VIXION R.*|VIXION.*/' => 'VIXION',
        
        // R Series
        '/R25.*|R15.*|R1.*/' => 'R SERIES',
        
        // XRIDE
        '/XRIDE 125.*|XRIDE.*/' => 'XRIDE',
        
        // FINO
        '/FINO GRANDE.*|FINO PREMIUM.*|FINO.*/' => 'FINO',
        
        // GEAR
        '/GEAR 125.*|GEAR.*/' => 'GEAR',
        
        // FREEGO
        '/FREEGO S.*|FREEGO.*/' => 'FREEGO',
        
        // GRAND FILANO
        '/GRAND FILANO.*/' => 'GRAND FILANO',
        
        // MT Series
        '/MT25.*|MT15.*|MT09.*|MT07.*/' => 'MT SERIES',
        
        // NVX
        '/NVX 155.*|NVX.*/' => 'NVX',
        
        // SOUL GT
        '/SOUL GT 125.*|SOUL GT.*/' => 'SOUL GT',
        
        // MX KING
        '/MX KING 150.*|MX KING.*/' => 'MX KING',
        
        // WR
        '/WR155.*|WR 155.*/' => 'WR'
    ];
    
    // Periksa motor terhadap pola
    foreach ($patterns as $pattern => $base_name) {
        if (preg_match($pattern, $motor_type)) {
            return $base_name;
        }
    }
    
    // Jika tidak ada pola yang cocok, kembalikan tipe aslinya
    return $motor_type;
}



// Mendapatkan tahun saat ini untuk dropdown
$currentYear = date("Y");
$years = array();
for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
    $years[] = $i;
}

// Mendapatkan array bulan untuk dropdown
$months = array(
    "" => "- Semua Bulan -",
    "1" => "Januari",
    "2" => "Februari",
    "3" => "Maret",
    "4" => "April",
    "5" => "Mei",
    "6" => "Juni",
    "7" => "Juli",
    "8" => "Agustus",
    "9" => "September",
    "10" => "Oktober",
    "11" => "November",
    "12" => "Desember"
);

$areas = array(
    "" => "- Semua Area -",
    "SURABAYA INSIDE" => "SURABAYA INSIDE",
    "SURABAYA OUTSIDE" => "SURABAYA OUTSIDE",
    "MALANG" => "MALANG",
    "JEMBER" => "JEMBER",
    "NTT" => "NTT",
    "NTB" => "NTB",
    "KALIMANTAN TIMUR" => "KALIMANTAN TIMUR",
    "KALIMANTAN TENGAH SELATAN" => "KALIMANTAN TENGAH SELATAN",
);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS jika belum ada di header -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    
    <style>
        .search-box {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .btn-search {
            background-color: #003399;
            color: white;
            border-radius: 5px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .btn-search:hover {
            background-color: #002266;
            transform: translateY(-2px);
        }
        .btn-reset {
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        .page-header {
            padding: 20px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #0062cc, #1e88e5);
            color: white;
            border-radius: 0 0 20px 20px;
        }
        .yamaha-icon {
            max-width: 50px;
            margin-right: 15px;
        }
        .summary-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .filter-section {
            background-color: #f0f4f8;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .filter-section h5 {
            color: #003399;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        .form-label {
            font-weight: 500;
        }
        /* Style untuk area summary */
        .area-summary-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .area-summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .area-summary-header {
            background-color: #0d6efd;
            color: white;
            padding: 12px 15px;
        }
        .area-count {
            font-size: 1.2rem;
            font-weight: 600;
        }
        .progress-bar-area {
            height: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row mt-5 page-header">
            <div class="col-12 text-center">
                <h1>Dashboard RO Sales per Tipe</h1>
                <p class="lead">Temukan data ringkasan riwayat pembelian motor dari pelanggan</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="search-box">
                    <form method="POST" action="" id="searchForm" class="row g-3">
                        <!-- Motor Type Section -->
                        <div class="col-12 filter-section">
                            <h5><i class="fas fa-motorcycle me-2"></i>Tipe Motor</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="motor_type" class="form-label">Pilih Tipe Motor Terakhir Dibeli:</label>
                                    <input type="text" class="form-control" id="motor_type" name="motor_type" 
                                           placeholder="Contoh: NMAX, Jupiter, Mio" 
                                           value="<?php echo isset($_POST['motor_type']) ? htmlspecialchars($_POST['motor_type']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Area Filter Section -->
                        <div class="col-12 filter-section">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Area Dealer</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="area_dealer" class="form-label">Pilih Area Dealer:</label>
                                    <select name="area_dealer" id="area_dealer" class="form-select">
                                        <?php foreach($areas as $key => $area): ?>
                                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['area_dealer']) && $_POST['area_dealer'] == $key) ? 'selected' : ''; ?>><?php echo $area; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date Filter Section -->
                        <div class="col-12 filter-section">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Periode Pembelian</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="year" class="form-label">Tahun:</label>
                                    <select name="year" id="year" class="form-select">
                                        <option value="">- Semua Tahun -</option>
                                        <?php foreach($years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo (isset($_POST['year']) && $_POST['year'] == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="month" class="form-label">Bulan:</label>
                                    <select name="month" id="month" class="form-select">
                                        <?php foreach($months as $key => $month): ?>
                                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['month']) && $_POST['month'] == $key) ? 'selected' : ''; ?>><?php echo $month; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="col-12 d-flex justify-content-end mt-3">
                            <button type="reset" class="btn btn-reset me-2">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-search">
                                <i class="fas fa-search me-2"></i>Cari Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="result-container">
        <?php
        // Cek apakah form sudah disubmit
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Mengambil nilai filter dari form
            $motor_type = $_POST['motor_type'] ?? '';
            $area_dealer = $_POST['area_dealer'] ?? '';
            $year = $_POST['year'] ?? '';
            $month = $_POST['month'] ?? '';

            // Koneksi ke database
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "yamahast_data";

            // Membuat koneksi
            $conn = new mysqli($servername, $username, $password, $dbname);

            // Cek koneksi
            if ($conn->connect_error) {
                echo '<div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>Koneksi database gagal: ' . $conn->connect_error . '
                      </div>';
            } else {
                // Membangun query SQL dengan filter tambahan
                $where_conditions = [];
                $where_conditions2 = [];
                
                // Filter untuk tipe motor
                if (!empty($motor_type)) {
                    $where_conditions[] = "latest.tipe_motor LIKE '%" . $conn->real_escape_string($motor_type) . "%'";
                    $where_conditions2[] = "tipe_motor LIKE '%" . $conn->real_escape_string($motor_type) . "%'";
                }
                
                // Filter untuk area dealer
                if (!empty($area_dealer)) {
                    $where_conditions[] = "latest.area_dealer = '" . $conn->real_escape_string($area_dealer) . "'";
                    $where_conditions2[] = "area_dealer = '" . $conn->real_escape_string($area_dealer) . "'";
                }
                
                // Filter untuk tahun dan bulan
                if (!empty($year)) {
                    $where_conditions[] = "YEAR(latest.tanggal_beli_motor) = " . $conn->real_escape_string($year);
                    $where_conditions2[] = "YEAR(tanggal_beli_motor) = " . $conn->real_escape_string($year);
                }
                
                if (!empty($month)) {
                    $where_conditions[] = "MONTH(latest.tanggal_beli_motor) = " . $conn->real_escape_string($month);
                    $where_conditions2[] = "MONTH(tanggal_beli_motor) = " . $conn->real_escape_string($month);
                }
                
                // Menggabungkan kondisi WHERE
                $where_clause = "latest.rn = 1";
                if (!empty($where_conditions)) {
                    $where_clause .= " AND " . implode(" AND ", $where_conditions);
                }
                $where_clause2 = 'id_customer is not null';
                if (!empty($where_conditions2)) {
                    $where_clause2 .= " AND " . implode(" AND ", $where_conditions2);
                }
                
                // Query SQL untuk mengambil data motor terakhir dan ALL previous motorcycles
                // Termasuk customer yang baru pertama kali membeli
                $sql = "
                WITH RankedMotors AS (
                    SELECT 
                        id_customer, 
                        tanggal_beli_motor, 
                        tipe_motor,
                        area_dealer,
                        ROW_NUMBER() OVER (PARTITION BY id_customer ORDER BY tanggal_beli_motor DESC) AS rn,
                        COUNT(*) OVER (PARTITION BY id_customer) AS total_motors
                    FROM data_motor
                    where id_customer IS NOT NULL
                )
                SELECT 
                    latest.id_customer,
                    latest.tanggal_beli_motor AS tanggal_beli_terakhir,
                    latest.tipe_motor AS tipe_motor_terakhir,
                    latest.area_dealer AS area_dealer,
                    latest.total_motors AS jumlah_motor,
                    previous.tanggal_beli_motor AS tanggal_beli_sebelumnya,
                    previous.tipe_motor AS tipe_motor_sebelumnya,
                    previous.rn AS urutan_sebelumnya
                FROM 
                    RankedMotors latest
                LEFT JOIN 
                    RankedMotors previous ON latest.id_customer = previous.id_customer AND previous.rn > 1
                WHERE 
                    $where_clause
                ORDER BY 
                    latest.id_customer ASC, 
                    CASE WHEN previous.rn IS NULL THEN 0 ELSE previous.rn END ASC
                ";

                // Menjalankan query
                $result = $conn->query($sql);

                $sql2 = "SELECT area_dealer, COUNT(*) as jumlah_motor FROM data_motor WHERE $where_clause2 GROUP BY area_dealer";

                $result2 = $conn->query($sql2);

                if ($result && $result->num_rows > 0) {
                    // Array untuk menyimpan semua tipe motor sebelumnya
                    $previous_types = array();
                    $previous_types_detail = array();
                    $first_time_buyers = 0;
                    $customer_count = 0;
                    $unique_customers = array();
                    $area_counts = array(); // Array untuk menghitung jumlah motor per area

                    // Menghitung jumlah pelanggan unik dan mengumpulkan data motor sebelumnya
                    while($row = $result->fetch_assoc()) {
                        if (!isset($unique_customers[$row['id_customer']])) {
                            $unique_customers[$row['id_customer']] = true;
                            $customer_count++;
                            
                            // Cek jika ini pembelian pertama (jumlah_motor = 1)
                            if (isset($row['jumlah_motor']) && $row['jumlah_motor'] == 1) {
                                $first_time_buyers++;
                            }
                            
                            // Menghitung jumlah motor per area menggunakan total_motors
                            $area = $row['area_dealer'];
                            if (!isset($area_counts[$area])) {
                                $area_counts[$area] = 1; // Menggunakan total_motors
                            } else {
                                $area_counts[$area] ++; // Menambahkan total_motors
                            }
                        }
                        
                        // Hanya menambahkan ke statistik jika ada tipe motor sebelumnya
                        if (!empty($row['tipe_motor_sebelumnya'])) {
                            $prev_type = $row['tipe_motor_sebelumnya'];
                            
                            // Ekstrak nama dasar motor (tanpa tipe/varian)
                            $base_motor_name = extractBaseMotorName($prev_type);
                            
                            if (!isset($previous_types[$base_motor_name])) {
                                $previous_types[$base_motor_name] = 1;
                                $previous_types_detail[$base_motor_name][$prev_type] = 1;
                            } else {
                                $previous_types[$base_motor_name]++;
                                
                                if (!isset($previous_types_detail[$base_motor_name][$prev_type])) {
                                    $previous_types_detail[$base_motor_name][$prev_type] = 1;
                                } else {
                                    $previous_types_detail[$base_motor_name][$prev_type]++;
                                }
                            }
                        }
                    }

                    // Membuat deskripsi filter yang digunakan
                    $filter_description = "motor terakhir";
                    if (!empty($motor_type)) {
                        $filter_description .= ' tipe "' . htmlspecialchars($motor_type) . '"';
                    } else {
                        $filter_description .= ' (semua tipe)';
                    }
                    
                    if (!empty($area_dealer)) {
                        $filter_description .= ' di area "' . htmlspecialchars($area_dealer) . '"';
                    }
                    
                    if (!empty($year) || !empty($month)) {
                        $filter_description .= ' pada periode ';
                        if (!empty($month)) {
                            $filter_description .= $months[$month] . ' ';
                        }
                        if (!empty($year)) {
                            $filter_description .= $year;
                        }
                    }

                    // Menghitung total motor dari semua area
                    $total_motors = array_sum($area_counts);

                    // Menampilkan info jumlah data
                    echo '<div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>Ditemukan ' . $customer_count . ' pelanggan dengan  motor terakhir ' . $filter_description . '
                                </div>
                            </div>
                          </div>';
                    
                    // Menampilkan informasi pembeli pertama kali
                    echo '<div class="row">
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>Dari total ' . $customer_count . ' pelanggan, ' . $first_time_buyers . ' (' . round(($first_time_buyers/$customer_count*100), 1) . '%) adalah pembeli pertama kali
                                </div>
                            </div>
                          </div>';

                    // Menampilkan ringkasan per area
                    echo '<div class="row mt-4">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5><i class="fas fa-map-marked-alt me-2"></i>Ringkasan Penjualan Per Area</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">';
                    
                    // Menggunakan data dari result2 untuk menghitung total motor
                    $area_counts = array();
                    $total_motors = 0;
                    
                    if ($result2 && $result2->num_rows > 0) {
                        while($row2 = $result2->fetch_assoc()) {
                            $area_counts[$row2['area_dealer']] = $row2['jumlah_motor'];
                            $total_motors += $row2['jumlah_motor'];
                        }
                    }
                    
                    // Sort area_counts secara descending berdasarkan jumlah
                    arsort($area_counts);
                    
                    // Loop untuk setiap area
                    foreach($area_counts as $area => $count) {
                        $percentage = round(($count / $total_motors) * 100, 1);
                        
                        // Color coding
                        $area_bg_color = 'bg-primary';
                        if ($percentage > 25) {
                            $area_bg_color = 'bg-success';
                        } else if ($percentage > 15) {
                            $area_bg_color = 'bg-info';
                        } else if ($percentage < 5) {
                            $area_bg_color = 'bg-secondary';
                        }
                        
                        echo '<div class="col-md-4 mb-3">
                                <div class="card area-summary-card">
                                    <div class="card-header area-summary-header '.$area_bg_color.'">
                                        <h5 class="mb-0">'.htmlspecialchars($area).'</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-motorcycle me-2"></i><strong>'.$count.'</strong> unit</span>
                                            <span class="badge '.$area_bg_color.'">'.$percentage.'%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar '.$area_bg_color.' progress-bar-area" role="progressbar" 
                                                 style="width: '.$percentage.'%" 
                                                 aria-valuenow="'.$percentage.'" aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                              </div>';
                    }
                    
                    echo '</div>
                        </div>
                    </div>
                </div>';
                    
                    // Menampilkan ringkasan data riwayat pembelian
                    echo '<div class="row mt-4">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5><i class="fas fa-chart-pie me-2"></i>Ringkasan Riwayat Pembelian Motor</h5>
                                    </div>
                                    <div class="card-body">';
                    
                    // Tambahkan card untuk pembeli pertama kali
                    echo '<div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card summary-card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Pembeli Pertama Kali</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-users me-2"></i><strong>'.$first_time_buyers.'</strong> pelanggan</span>
                                            <span class="badge bg-warning text-dark">'.round(($first_time_buyers/$customer_count*100), 1).'%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                style="width: '.round(($first_time_buyers/$customer_count*100), 1).'%" 
                                                aria-valuenow="'.round(($first_time_buyers/$customer_count*100), 1).'" aria-valuemin="0" 
                                                aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        
                    if (count($previous_types) > 0) {
                        echo '<h6>Motor Yang Dimiliki Sebelum nya:</h6>
                            <div class="row">';
                    
                    // Sort by count in descending order
                    arsort($previous_types);
                    
                    // Tampilkan setiap tipe dengan jumlah dan persentase
                    $total_previous_motors = array_sum($previous_types);
                    foreach($previous_types as $type => $count) {
                        $percentage = round(($count / $total_previous_motors) * 100, 1);
                        $bg_color = $percentage > 30 ? 'bg-success' : ($percentage > 15 ? 'bg-info' : 'bg-primary');
                        
                        echo '<div class="col-md-4 mb-3">
                                <div class="card summary-card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">'.htmlspecialchars($type).'</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-users me-2"></i><strong>'.$count.'</strong> unit</span>
                                            <span class="badge '.$bg_color.'">'.$percentage.'%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar '.$bg_color.'" role="progressbar" 
                                                 style="width: '.$percentage.'%" 
                                                 aria-valuenow="'.$percentage.'" aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                              </div>';
                    }
                    
                    if (count($previous_types) > 0) {
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-secondary mt-3">
                                <i class="fas fa-info-circle me-2"></i>Tidak ada data riwayat pembelian sebelumnya selain pembeli pertama kali
                              </div>';
                    }
                    
                    echo '</div>
                    </div>
                </div>
            </div>';
                    
                } else {
                    // Filter description for no results message
                    $filter_txt = [];
                    if (!empty($motor_type)) $filter_txt[] = "tipe motor \"" . htmlspecialchars($motor_type) . "\"";
                    if (!empty($area_dealer)) $filter_txt[] = "area \"" . htmlspecialchars($area_dealer) . "\"";
                    if (!empty($year)) $filter_txt[] = "tahun " . htmlspecialchars($year);
                    if (!empty($month)) $filter_txt[] = "bulan " . $months[$month];
                    
                    $filter_str = implode(", ", $filter_txt);
                    
                    echo '<div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>Tidak ada data yang ditemukan untuk filter: ' . $filter_str . '
                                </div>
                            </div>
                          </div>';
                }

                // Menutup koneksi
                $conn->close();
            }
        }
    }
        ?>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Menangani form submission secara AJAX
        $(document).ready(function() {
            $('#searchForm').on('submit', function(e) {
                // Mencegah form submission normal
                e.preventDefault();
                
                // Mengirim data form ke server via AJAX
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Mendapatkan container hasil saja dari response
                        var $response = $(response);
                        var resultHtml = $response.find('#result-container').html();
                        
                        // Update hanya bagian hasil di halaman
                        $('#result-container').html(resultHtml);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error: " + error);
                    }
                });
            });
        });
        
    </script>

    <?php require_once 'layout/footer.php' ?>
</body>
</html