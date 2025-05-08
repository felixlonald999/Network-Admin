<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motor Last Purchase</title>
</head>
<body>
    <h1>Cari Motor Terakhir dan Sebelumnya</h1>
    
    <!-- Form untuk memilih tipe motor -->
    <form method="POST" action="filter_data.php">
        <label for="motor_type">Pilih Tipe Motor:</label>
        <input type="text" id="motor_type" name="motor_type" placeholder="Contoh: NMAX" required>
        <button type="submit">Cari</button>
    </form>

    <?php
    // Cek apakah form sudah disubmit
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $motor_type = $_POST['motor_type'];

        // Koneksi ke database
        $servername = "localhost"; // Ganti dengan server DB Anda
        $username = "root"; // Ganti dengan username DB Anda
        $password = ""; // Ganti dengan password DB Anda
        $dbname = "yamahast_data"; // Ganti dengan nama database Anda

        // Membuat koneksi
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Cek koneksi
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

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
            AND tipe_motor LIKE '%" . $conn->real_escape_string($motor_type) . "%'
        ) dm1
        LEFT JOIN (
            SELECT id_customer, tanggal_beli_motor, tipe_motor, ktp, nomor_rangka,
                   ROW_NUMBER() OVER (PARTITION BY id_customer ORDER BY tanggal_beli_motor DESC) AS rn
            FROM data_motor
            WHERE tanggal_beli_motor IS NOT NULL
            AND LENGTH(ktp) >= 16
            AND tipe_motor LIKE '%" . $conn->real_escape_string($motor_type) . "%'
        ) dm2 ON dm1.id_customer = dm2.id_customer AND dm2.rn = dm1.rn + 1
        WHERE dm1.rn = 1
        AND dm2.rn IS NOT NULL
        ORDER BY dm1.id_customer ASC;
        ";

        // Menjalankan query dan menampilkan hasil
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Menampilkan data hasil query
            echo "<table border='1'>
                    <tr>
                        <th>ID Customer</th>
                        <th>Tanggal Beli Terakhir</th>
                        <th>Tipe Motor Terakhir</th>
                        <th>Tanggal Beli Sebelumnya</th>
                        <th>Tipe Motor Sebelumnya</th>
                        <th>KTP</th>
                    </tr>";
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['id_customer'] . "</td>
                        <td>" . $row['beli_terakhir'] . "</td>
                        <td>" . $row['tipe_motor_terakhir'] . "</td>
                        <td>" . $row['beli_sebelumnya'] . "</td>
                        <td>" . $row['tipe_motor_sebelumnya'] . "</td>
                        <td>" . $row['ktp'] . "</td>
                    </tr>";
            }
            echo "</table>";
        } else {
            echo "Tidak ada data yang ditemukan.";
        }

        // Menutup koneksi
        $conn->close();
    }
    ?>
</body>
</html>
