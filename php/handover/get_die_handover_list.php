<?php
require_once "./../db.php";

$die_id = $_GET["die_id"] ?? "";
$start  = $_GET["start"] ?? "";
$end    = $_GET["end"] ?? "";
$date_col = $_GET["date_col"] ?? "arrived_at"; // デフォルト arrived_at

// ▼ 期間対象カラムのホワイトリスト
$allowed_cols = [
    "arrived_at",
    "capitalization_date",
    "press_condition_document_completion_at",
    "qa_dimension_inspection_completed_at",
    "dimension_inspection_sample_sent_at"
];

// ▼ 不正な値が来た場合は arrived_at にフォールバック
if (!in_array($date_col, $allowed_cols, true)) {
    $date_col = "arrived_at";
}

$where = [];

// ▼ 金型フィルター
if ($die_id !== "") {
    $where[] = "h.die_id = " . intval($die_id);
}

// ▼ 期間フィルター（対象カラムを選択可能）
if ($start !== "") {
    $where[] = "h.$date_col >= " . $pdo->quote($start);
}

if ($end !== "") {
    $where[] = "h.$date_col <= " . $pdo->quote($end);
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ▼ メイン SQL
$sql = "
    SELECT
        h.id,
        h.die_id,
        d.die_number,
        h.pn as original_pn,
        p.production_number as database_pn,
        h.press_condition_document_completion_at,
        h.qa_dimension_inspection_completed_at,
        h.qa_dimension_inspection_document_number,
        h.dimension_inspection_sample_sent_at,
        h.arrived_at,
        h.capitalization_date,
        h.memo
    FROM t_die_handover h
    JOIN m_dies d ON h.die_id = d.id
    JOIN m_production_numbers p ON d.production_number_id = p.id
    $whereSql
    ORDER BY h.id DESC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
