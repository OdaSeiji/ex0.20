<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$keyword = isset($_GET["q"]) ? trim($_GET["q"]) : "";

$prepare = $pdo->prepare("
    SELECT
        m_dies.id,
        m_dies.die_number,
        m_production_numbers.production_number,
        m_dies.die_postition
    FROM m_dies
    LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
    WHERE m_dies.die_number LIKE :keyword
    ORDER BY m_dies.die_number
    LIMIT 50
");
$prepare->bindValue(":keyword", "{$keyword}%", PDO::PARAM_STR);
$prepare->execute();

echo json_encode($prepare->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
