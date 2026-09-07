<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$diesId = (int)($_GET["dies_id"] ?? 0);
if ($diesId === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        t.*,
        v.production_number AS variant_production_number,
        v.length AS variant_length,
        o.ordersheet_number
    FROM t_press_directive t
    LEFT JOIN m_production_number_variants v ON t.production_number_variant_id = v.id
    LEFT JOIN m_ordersheet o ON t.ordersheet_id = o.id
    WHERE t.dies_id = :dies_id
    ORDER BY t.plan_date_at DESC, t.id DESC
    LIMIT 10
");
$stmt->execute([":dies_id" => $diesId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
