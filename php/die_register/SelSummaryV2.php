<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$dieName = $_POST['dieName'] ?? '%';

$stmt = $pdo->prepare("
    SELECT
        m_dies.id,
        m_dies.die_number,
        m_dies_diamater.die_diamater,
        IFNULL(m_bolster.bolster_name, '') AS bolster_name,
        m_production_numbers.production_number,
        IF(m_dies.arrival_at IS NULL OR m_dies.arrival_at = '0000-00-00', '', DATE_FORMAT(m_dies.arrival_at, '%y-%m-%d')) AS arrival_at,
        IF(m_dies.updated_at IS NULL OR m_dies.updated_at = '0000-00-00', '', DATE_FORMAT(m_dies.updated_at, '%y-%m-%d')) AS updated_at,
        m_dies.hole,
        die_postition
    FROM m_dies
    LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
    LEFT JOIN m_dies_diamater ON m_dies.die_diamater_id = m_dies_diamater.id
    LEFT JOIN m_bolster ON m_dies.bolstar_id = m_bolster.id
    WHERE m_dies.die_number LIKE :dieName
    ORDER BY die_number
");
$stmt->execute([':dieName' => $dieName]);
echo json_encode($stmt->fetchAll());
