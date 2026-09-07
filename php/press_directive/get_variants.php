<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$productionNumberId = (int)($_GET["production_number_id"] ?? 0);
if ($productionNumberId === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, production_number, length, is_default
    FROM m_production_number_variants
    WHERE production_number_id = :id
    ORDER BY is_default DESC, length
");
$stmt->execute([":id" => $productionNumberId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
