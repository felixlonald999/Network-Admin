<?php
// Konfigurasi
$config = [
    'host' => 'localhost',
    'dbname' => 'yamahast_data',
    'username' => 'root',
    'password' => '',
    'total_workers' => 4,        // Jumlah proses paralel
    'batch_size' => 5000,        // Ukuran batch per worker
    'chunk_size' => 80000      // Pembagian data per worker (1 juta per worker)
];

// Fungsi untuk generate worker script
function generateWorkerScript($config, $start, $end, $workerId) {
    $filename = "worker_$workerId.php";
    
    $script = <<<EOT
<?php
// Worker #$workerId - Data range: $start to $end
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

// Koneksi ke database
try {
    \$pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']}", 
        "{$config['username']}", 
        "{$config['password']}",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
        ]
    );
    
    file_put_contents("worker_{$workerId}_log.txt", "Worker #$workerId connected to database.\n", FILE_APPEND);
} catch (PDOException \$e) {
    file_put_contents("worker_{$workerId}_log.txt", "Worker #$workerId failed to connect: " . \$e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}

// Setting batch
\$batchSize = {$config['batch_size']};
\$start = $start;
\$end = $end;
\$offset = 0;
\$totalProcessed = 0;

do {
    \$currentLimit = min(\$batchSize, \$end - (\$start + \$offset));
    
    if (\$currentLimit <= 0) {
        break;
    }
    
    \$batchStartTime = microtime(true);
    file_put_contents("worker_{$workerId}_log.txt", "Processing batch starting from " . (\$start + \$offset) . " (limit \$currentLimit)...\n", FILE_APPEND);

    // Ambil data faktur + customer
    \$stmt = \$pdo->prepare("
        SELECT 
            dc.id AS id_customer,
            f.nama_konsumen,
            f.no_ktp,
            f.no_hp,
            f.nomor_rangka,
            f.tipe_motor,
            f.tanggal_beli_motor, 
            f.pekerjaan,
            f.pendidikan,
            f.tipe_pembelian,
            f.tenor_kredit,
            f.kode_dealer,
            f.nama_dealer,
            f.area_dealer
        FROM faktur f
        LEFT JOIN data_customer dc 
            ON f.nama_konsumen = dc.nama_customer 
           AND f.no_ktp = dc.ktp_customer
        WHERE NOT EXISTS (
            SELECT 1
            FROM data_motor m
            WHERE m.nomor_rangka = f.nomor_rangka
        )
        
        LIMIT :limit OFFSET :offset
    ");
    \$stmt->bindValue(':limit', \$currentLimit, PDO::PARAM_INT);
    \$stmt->bindValue(':offset', \$start + \$offset, PDO::PARAM_INT);
    \$stmt->execute();

    \$rows = \$stmt->fetchAll();
    \$rowCount = count(\$rows);

    if (\$rowCount > 0) {
        // Build bulk insert query
        \$values = [];
        \$params = [];

        foreach (\$rows as \$row) {
            \$values[] = "(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            \$params[] = \$row['id_customer'];
            \$params[] = \$row['nama_konsumen'];
            \$params[] = \$row['no_ktp'];
            \$params[] = \$row['no_hp'];
            \$params[] = \$row['nomor_rangka'];
            \$params[] = \$row['tipe_motor'];
            \$params[] = \$row['tanggal_beli_motor'];
            \$params[] = \$row['pekerjaan'];
            \$params[] = \$row['pendidikan'];
            \$params[] = \$row['tipe_pembelian'];
            \$params[] = \$row['tenor_kredit'];
            \$params[] = \$row['kode_dealer'];
            \$params[] = \$row['nama_dealer'];
            \$params[] = \$row['area_dealer'];
        }

        \$sql = "
            INSERT INTO data_motor 
                (id_customer, nama_konsumen, ktp, no_hp, nomor_rangka, tipe_motor, tanggal_beli_motor, pekerjaan, pendidikan, tipe_pembelian, tenor_kredit, kode_dealer, nama_dealer, area_dealer)
            VALUES " . implode(',', \$values);

        try {
            \$pdo->beginTransaction();
            \$insert = \$pdo->prepare(\$sql);
            \$insert->execute(\$params);
            \$pdo->commit();
            
            \$totalProcessed += \$rowCount;
            \$batchTime = round(microtime(true) - \$batchStartTime, 2);
            
            file_put_contents("worker_{$workerId}_log.txt", "Inserted \$rowCount rows. Total processed: \$totalProcessed. Batch time: \$batchTime seconds.\n", FILE_APPEND);
        } catch (PDOException \$e) {
            \$pdo->rollBack();
            file_put_contents("worker_{$workerId}_log.txt", "Error in batch: " . \$e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents("worker_{$workerId}_log.txt", "No more data to insert.\n", FILE_APPEND);
        break;
    }

    \$offset += \$currentLimit;

} while (\$offset < (\$end - \$start));

file_put_contents("worker_{$workerId}_log.txt", "Worker #$workerId completed. Total processed: \$totalProcessed\n", FILE_APPEND);
file_put_contents("worker_{$workerId}_completed.txt", "1");
EOT;

    file_put_contents($filename, $script);
    return $filename;
}

// Main script
echo "Parallel Import Script\n";
echo "---------------------\n";

// Koneksi ke database untuk mendapatkan total data
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']}", 
        $config['username'], 
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database.\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Pastikan indeks ada
try {
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_customer_nama_ktp 
        ON data_customer(nama_customer, ktp_customer)
    ");
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_faktur_nama_ktp 
        ON faktur(nama_konsumen, no_ktp)
    ");
    echo "Indices created or already exist.\n";
} catch (PDOException $e) {
    echo "Warning: Could not create indices: " . $e->getMessage() . "\n";
}

// Hitung total data
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM faktur f
    JOIN data_customer dc 
        ON f.nama_konsumen = dc.nama_customer 
       AND f.no_ktp = dc.ktp_customer
");
$result = $stmt->fetch();
$totalRows = $result['total'];

echo "Total rows to process: $totalRows\n";

// Menentukan jumlah worker yang dibutuhkan
$totalWorkers = min($config['total_workers'], ceil($totalRows / $config['chunk_size']));
echo "Using $totalWorkers worker processes.\n";

// Chunk data untuk setiap worker
$chunkSize = ceil($totalRows / $totalWorkers);
$workerScripts = [];

// Membuat script worker
for ($i = 0; $i < $totalWorkers; $i++) {
    $start = $i * $chunkSize;
    $end = min(($i + 1) * $chunkSize, $totalRows);
    
    $scriptName = generateWorkerScript($config, $start, $end, $i + 1);
    $workerScripts[] = $scriptName;
    
    echo "Created worker script $scriptName for rows $start to $end\n";
}

// Jalankan worker scripts
echo "\nStarting worker processes...\n";
$startTime = microtime(true);

// Fungsi untuk menjalankan PHP script
function runPhpScript($scriptName) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        pclose(popen("start /B php $scriptName > NUL", "r"));
    } else {
        // Linux/Unix
        exec("php $scriptName > /dev/null 2>&1 &");
    }
}

// Jalankan semua worker
foreach ($workerScripts as $script) {
    runPhpScript($script);
    echo "Started $script\n";
}

// Tunggu semua worker selesai
echo "\nWaiting for all workers to complete...\n";
$allComplete = false;

while (!$allComplete) {
    $complete = true;
    
    for ($i = 1; $i <= $totalWorkers; $i++) {
        if (!file_exists("worker_{$i}_completed.txt")) {
            $complete = false;
            break;
        }
    }
    
    $allComplete = $complete;
    
    if (!$allComplete) {
        echo ".";
        sleep(5);
    }
}

$totalTime = round(microtime(true) - $startTime, 2);
echo "\n\nAll workers completed! Total time: $totalTime seconds\n";

// Gabungkan log files
echo "\nCombined worker logs:\n";
echo "---------------------\n";

for ($i = 1; $i <= $totalWorkers; $i++) {
    $logFile = "worker_{$i}_log.txt";
    if (file_exists($logFile)) {
        echo "\nWorker #$i Log:\n";
        echo file_get_contents($logFile);
    }
}

// Bersihkan file-file sementara
echo "\nCleaning up temporary files...\n";
foreach ($workerScripts as $script) {
    unlink($script);
}

for ($i = 1; $i <= $totalWorkers; $i++) {
    @unlink("worker_{$i}_completed.txt");
    @unlink("worker_{$i}_log.txt");
}

echo "\nDONE!\n";
?>