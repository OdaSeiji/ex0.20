<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$keyword = isset($_GET["keyword"]) ? trim($_GET["keyword"]) : "";

if ($keyword !== "") {
    $stmt = $pdo->prepare("
        SELECT id, production_number
        FROM m_production_numbers
        WHERE production_number LIKE ?
        ORDER BY production_number
        LIMIT 50
    ");
    $stmt->execute(["%{$keyword}%"]);
} else {
    $stmt = $pdo->query("
        SELECT id, production_number
        FROM m_production_numbers
        ORDER BY production_number
        LIMIT 50
    ");
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
