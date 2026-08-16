<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$production_number = $_POST['production_number'] ?? '%';

$stmt = $pdo->prepare("
    SELECT
        m_production_numbers.id,
        m_production_numbers_category1.name_jp AS category1,
        m_production_numbers_category2.name_jp AS category2,
        m_production_numbers.production_number,
        t1.count AS linked_dies
    FROM m_production_numbers
    LEFT JOIN m_production_numbers_category2
        ON m_production_numbers.production_category2_id = m_production_numbers_category2.id
    LEFT JOIN m_production_numbers_category1
        ON m_production_numbers_category2.category1_id = m_production_numbers_category1.id
    LEFT JOIN (
        SELECT production_number_id, COUNT(id) AS count
        FROM m_dies
        GROUP BY production_number_id
    ) AS t1 ON m_production_numbers.id = t1.production_number_id
    WHERE m_production_numbers.production_number LIKE :production_number
    ORDER BY m_production_numbers.production_number
");
$stmt->execute([':production_number' => $production_number]);
echo json_encode($stmt->fetchAll());
