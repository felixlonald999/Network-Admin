<?php
require("autoload.php");

header('Content-Type: application/json');

try {
    // Pastikan metode request adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Hanya metode POST yang diizinkan.");
    }

    // Ambil parameter POST
    $selectColumns = isset($_POST['select']) ? explode(',', $_POST['select']) : ['*'];
    $selectQuery = implode(',', $selectColumns);

    // Ambil parameter where untuk kondisi
    $whereConditions = [];
    $bindParams = [];
    $bindTypes = '';

    if (isset($_POST['where']) && is_array($_POST['where'])) {
        foreach ($_POST['where'] as $column => $value) {
            if (preg_match('/^(YEAR|MONTH)\(/i', $column)) {
                // YEAR() adalah fungsi SQL, tidak boleh ada backtick
                $whereConditions[] = "$column = ?";
                $bindParams[] = $value;
                $bindTypes .= 'i';
            } else if (is_array($value)) {
                // Jika ada banyak nilai untuk satu kolom, gunakan IN (...)
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $whereConditions[] = "`$column` IN ($placeholders)";
                $bindParams = array_merge($bindParams, $value);
                $bindTypes .= str_repeat('s', count($value)); // Semua dianggap string
            } else {
                // Jika hanya satu nilai, gunakan =
                $whereConditions[] = "`$column` = ?";
                $bindParams[] = $value;
                $bindTypes .= 's';
            }
        }
    }

    // Tambahkan pagination
    $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 2000; // Default 2000 row per request
    $offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;

    // Susun query
    $query = "SELECT $selectQuery FROM `data_customer`";
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    $query .= " LIMIT ? OFFSET ?";

    // Tambahkan limit dan offset ke binding
    $bindParams[] = $limit;
    $bindParams[] = $offset;
    $bindTypes .= 'ii'; // 'ii' untuk integer (limit dan offset)

    // Eksekusi query
    $stmt = $conn->prepare($query);

    if (!empty($bindParams)) {
        $stmt->bind_param($bindTypes, ...$bindParams);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_all(MYSQLI_ASSOC);

    // Hitung total row tanpa pagination
    $countQuery = "SELECT COUNT(*) as total FROM `data_customer`";
    if (!empty($whereConditions)) {
        $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }

    // Eksekusi query untuk total data
    $countStmt = $conn->prepare($countQuery);
    
    if (!empty($whereConditions)) {
        // Hanya ambil parameter yang relevan untuk binding
        // Mengurangi dua elemen terakhir dari bindParams karena limit dan offset
        $countStmt->bind_param(substr($bindTypes, 0, -2), ...array_slice($bindParams, 0, -2));
    }
    
    $countStmt->execute();
    
    // Ambil total row
    $totalRow = $countStmt->get_result()->fetch_assoc()['total'];

    // Output JSON
    echo json_encode([
        "status" => "success",
        "total_data" => $totalRow,
        "limit" => $limit,
        "offset" => $offset,
        "data" => $row
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
