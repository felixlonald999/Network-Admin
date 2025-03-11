<?php
require("autoload.php");

header('Content-Type: application/json');

try {
    // Ambil parameter select (kolom yang ingin diambil)
    $selectColumns = isset($_GET['select']) ? explode(',', $_GET['select']) : ['*'];
    $selectQuery = implode(',', $selectColumns);

    // Ambil parameter where untuk kondisi
    $whereConditions = [];
    $bindParams = [];
    $bindTypes = '';

    if (isset($_GET['where']) && is_array($_GET['where'])) {
        foreach ($_GET['where'] as $column => $value) {
            if (is_array($value)) {
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
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100; // Default 100 row per request
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

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

    $countStmt = $conn->prepare($countQuery);
    if (!empty($whereConditions)) {
        $countStmt->bind_param(substr($bindTypes, 0, -2), ...array_slice($bindParams, 0, -2));
    }
    $countStmt->execute();
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
