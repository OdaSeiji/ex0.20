<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$die_id   = $_GET["die_id"]   ?? "";
$start    = $_GET["start"]    ?? "";
$end      = $_GET["end"]      ?? "";
$date_col = $_GET["date_col"] ?? "die_arrived_at";

$allowed_cols = [
    "die_arrived_at",
    "instruction_created_at",
    "inspection_passed_at",
    "submitted_to_japan_at",
    "submitted_to_vietnam_at",
];

if (!in_array($date_col, $allowed_cols, true)) {
    $date_col = "die_arrived_at";
}

$where  = [];
$params = [];

if ($die_id !== "") {
    $where[]  = "h.die_id = ?";
    $params[] = intval($die_id);
}
if ($start !== "") {
    $where[]  = "h.{$date_col} >= ?";
    $params[] = $start;
}
if ($end !== "") {
    $where[]  = "h.{$date_col} <= ?";
    $params[] = $end;
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        h.id,
        h.die_id,
        COALESCE(d.die_number, h.die_model_code)              AS die_number,
        COALESCE(p.production_number, h.product_code)         AS production_number,
        h.instruction_created_at,
        h.inspection_number,
        h.inspection_passed_at,
        h.submitted_to_japan_at,
        h.submitted_to_vietnam_at,
        h.is_accessory_item_flag,
        h.invoice_number,
        h.die_arrived_at,
        h.unusable_flag,
        h.updated_at
    FROM t_die_handover h
    LEFT JOIN m_dies d ON h.die_id = d.id
    LEFT JOIN m_production_numbers p ON h.product_code = p.production_number
    $whereSql
    ORDER BY h.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
