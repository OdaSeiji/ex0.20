<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$productionNumberId = (int)($_GET["production_number_id"] ?? 0);
$keyword = isset($_GET["q"]) ? trim($_GET["q"]) : "";

if ($productionNumberId > 0) {
    $stmt = $pdo->prepare("
        SELECT m_dies.id, m_dies.die_number, m_production_numbers.production_number
        FROM m_dies
        LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
        WHERE m_dies.production_number_id = :id AND IFNULL(m_dies.is_disable, 0) = 0
        ORDER BY m_dies.die_number
    ");
    $stmt->execute([":id" => $productionNumberId]);
} else {
    $stmt = $pdo->prepare("
        SELECT m_dies.id, m_dies.die_number, m_production_numbers.production_number
        FROM m_dies
        LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
        WHERE m_dies.die_number LIKE :keyword AND IFNULL(m_dies.is_disable, 0) = 0
        ORDER BY m_dies.die_number
        LIMIT 50
    ");
    $stmt->bindValue(":keyword", "{$keyword}%", PDO::PARAM_STR);
    $stmt->execute();
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
