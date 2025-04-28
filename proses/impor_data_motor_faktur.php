<?php
// Koneksi ke database
$pdo = new PDO("mysql:host=localhost;dbname=yamahast_data", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Setting batch
$batchSize = 1000;
$offset = 0;

do {
    echo "Processing batch starting from offset $offset...\n";

    // Ambil data faktur + customer
    $stmt = $pdo->prepare("
        SELECT 
            dc.id AS id_customer,
            f.nama_konsumen,
            f.no_ktp,
            f.no_hp,
            f.nomor_rangka,
            f.tipe_motor,
            f.tanggal_beli_motor
        FROM faktur f
        JOIN data_customer dc 
            ON f.nama_konsumen = dc.nama_customer 
           AND f.no_ktp = dc.ktp_customer
        WHERE NOT EXISTS (
            SELECT 1
            FROM data_motor m
            WHERE m.nomor_rangka = f.nomor_rangka
        )
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        // Build bulk insert query
        $values = [];
        $params = [];

        foreach ($rows as $index => $row) {
            $values[] = "(?,?,?,?,?,?,?)";

            $params[] = $row['id_customer'];
            $params[] = $row['nama_konsumen'];
            $params[] = $row['no_ktp'];
            $params[] = $row['no_hp'];
            $params[] = $row['nomor_rangka'];
            $params[] = $row['tipe_motor'];
            $params[] = $row['tanggal_beli_motor'];
        }

        $sql = "
            INSERT INTO data_motor 
                (id_customer, nama_konsumen, ktp, no_hp, nomor_rangka, tipe_motor, tanggal_beli_motor)
            VALUES " . implode(',', $values);

        $pdo->beginTransaction();
        $insert = $pdo->prepare($sql);
        $insert->execute($params);
        $pdo->commit();

        echo "Inserted " . count($rows) . " rows.\n";
    } else {
        echo "No more data to insert.\n";
    }

    $offset += $batchSize;

} while (count($rows) > 0);

echo "DONE!\n";
?>
