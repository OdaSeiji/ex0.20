<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$dieId = (int)($_GET["die_id"] ?? 0);
if ($dieId === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT v.id AS variant_id, p.id AS production_number_id,
           p.production_number, p.specific_weight, p.production_length
    FROM m_die_production_number_variants v
    JOIN m_production_numbers p ON p.id = v.production_number_id
    WHERE v.die_id = :die_id
    ORDER BY p.production_number
");
$stmt->execute([":die_id" => $dieId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
