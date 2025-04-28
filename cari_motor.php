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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
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
        .result-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .page-header {
            padding: 20px 0;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #0062cc, #1e88e5);
            color: white;
            border-radius: 0 0 20px 20px;
        }
        .yamaha-icon {
            max-width: 50px;
            margin-right: 15px;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
            border: 1px solid #ddd;
        }
        table.dataTable thead th {
            background-color: #003399;
            color: white;
        }
        table.dataTable tbody tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row page-header">
            <div class="col-12 text-center">
                <h1><img src="assets/img/yamaha-logo.png" alt="Yamaha Logo" class="yamaha-icon">Cari Riwayat Pembelian Motor</h1>
                <p class="lead">Temukan data motor terakhir dan sebelumnya dari pelanggan</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="search-box">
                    <form method="POST" action="" id="searchForm" class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label for="motor_type" class="form-label"><i class="fas fa-motorcycle me-2"></i>Pilih Tipe Motor:</label>
                            <input type="text" class="form-control form-control-lg" id="motor_type" name="motor_type" 
                                   placeholder="Contoh: NMAX, Jupiter, Mio" 
                                   value="<?php echo isset($_POST['motor_type']) ? htmlspecialchars($_POST['motor_type']) : ''; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-search btn-lg w-100">
                                <i class="fas fa-search me-2"></i>Cari
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
            $motor_type = $_POST['motor_type'];

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
                // Query SQL untuk mengambil data motor terakhir dan sebelumnya
                $sql = "
                SELECT dm1.id_customer,
                       dm1.tanggal_beli_motor AS beli_terakhir,
                       dm1.tipe_motor AS tipe_motor_terakhir,
                       dm2.tanggal_beli_motor AS beli_sebelumnya,
                       dm2.tipe_motor AS tipe_motor_sebelumnya,
                       dm1.ktp
                FROM (
                    SELECT id_customer, tanggal_beli_motor, tipe_motor, ktp, nomor_rangka,
                           ROW_NUMBER() OVER (PARTITION BY id_customer ORDER BY tanggal_beli_motor DESC) AS rn
                    FROM data_motor
                    WHERE tanggal_beli_motor IS NOT NULL
                    AND LENGTH(ktp) >= 16
                    
                ) dm1
                LEFT JOIN (
                    SELECT id_customer, tanggal_beli_motor, tipe_motor, ktp, nomor_rangka,
                           ROW_NUMBER() OVER (PARTITION BY id_customer ORDER BY tanggal_beli_motor DESC) AS rn
                    FROM data_motor
                    WHERE tanggal_beli_motor IS NOT NULL
                    AND LENGTH(ktp) >= 16
                    
                ) dm2 ON dm1.id_customer = dm2.id_customer AND dm2.rn = dm1.rn + 1
                WHERE dm1.rn = 1
                AND dm2.rn IS NOT NULL
                AND dm1.tipe_motor LIKE '%" . $conn->real_escape_string($motor_type) . "%'
                ORDER BY dm1.id_customer ASC;
                ";

                // Menjalankan query dan menampilkan hasil
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // Menampilkan info jumlah data
                    echo '<div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>Ditemukan ' . $result->num_rows . ' data pelanggan untuk tipe motor "' . htmlspecialchars($motor_type) . '"
                                </div>
                            </div>
                          </div>';
                    
                    // Container untuk tabel dengan ID dinamis
                    echo '<div class="row">
                            <div class="col-12">
                                <div class="card result-table">
                                    <div class="card-header bg-primary text-white">
                                        <h5><i class="fas fa-table me-2"></i>Data Riwayat Pembelian Motor</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="' . $tableId . '" class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID Customer</th>
                                                        <th>Tanggal Beli Terakhir</th>
                                                        <th>Tipe Motor Terakhir</th>
                                                        <th>Tanggal Beli Sebelumnya</th>
                                                        <th>Tipe Motor Sebelumnya</th>
                                                        <th>KTP</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                    
                    while($row = $result->fetch_assoc()) {
                        // Format tanggal jika tersedia
                        $tanggal_terakhir = !empty($row['beli_terakhir']) ? date('d F Y', strtotime($row['beli_terakhir'])) : '-';
                        $tanggal_sebelumnya = !empty($row['beli_sebelumnya']) ? date('d F Y', strtotime($row['beli_sebelumnya'])) : '-';
                        
                        echo "<tr>
                                <td>" . htmlspecialchars($row['id_customer']) . "</td>
                                <td>" . $tanggal_terakhir . "</td>
                                <td>" . htmlspecialchars($row['tipe_motor_terakhir']) . "</td>
                                <td>" . $tanggal_sebelumnya . "</td>
                                <td>" . htmlspecialchars($row['tipe_motor_sebelumnya']) . "</td>
                                <td>" . htmlspecialchars($row['ktp']) . "</td>
                            </tr>";
                    }
                    
                    echo '</tbody>
                        </table>
                    </div>
                    </div>
                    </div>
                    </div>
                    </div>';
                    
                    // Menyimpan ID tabel untuk digunakan oleh script
                    echo '<script>var currentTableId = "' . $tableId . '";</script>';
                    
                } else {
                    echo '<div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>Tidak ada data yang ditemukan untuk tipe motor "' . htmlspecialchars($motor_type) . '"
                                </div>
                            </div>
                          </div>';
                }

                // Menutup koneksi
                $conn->close();
            }
        }
        ?>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables hanya jika variable currentTableId telah didefinisikan
            if (typeof currentTableId !== 'undefined') {
                $('#' + currentTableId).DataTable({
                    responsive: true,
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ entri",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                        infoFiltered: "(disaring dari _MAX_ total entri)",
                        zeroRecords: "Tidak ada data yang cocok",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });
            }
        });
        
        // Menangani form submission secara AJAX untuk mencegah inisialisasi DataTable ganda
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
                        
                        // Inisialisasi DataTable pada tabel yang baru
                        if (typeof currentTableId !== 'undefined') {
                            $('#' + currentTableId).DataTable({
                                responsive: true,
                                language: {
                                    search: "Cari:",
                                    lengthMenu: "Tampilkan _MENU_ entri",
                                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                                    infoFiltered: "(disaring dari _MAX_ total entri)",
                                    zeroRecords: "Tidak ada data yang cocok",
                                    paginate: {
                                        first: "Pertama",
                                        last: "Terakhir",
                                        next: "Selanjutnya",
                                        previous: "Sebelumnya"
                                    }
                                }
                            });
                        }
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
</html>