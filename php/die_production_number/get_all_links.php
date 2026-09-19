<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// GROUP_CONCATの長さ上限に依存しないよう、フラットな行で取得してPHP側でグルーピングする
$stmt = $pdo->query("
    SELECT d.id AS die_id, d.die_number, p.production_number
    FROM m_die_production_number_variants v
    JOIN m_dies d ON d.id = v.die_id
    JOIN m_production_numbers p ON p.id = v.production_number_id
    ORDER BY d.die_number, p.production_number
");

$grouped = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $key = $row["die_id"];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            "die_id" => (int)$row["die_id"],
            "die_number" => $row["die_number"],
            "production_numbers" => [],
        ];
    }
    $grouped[$key]["production_numbers"][] = $row["production_number"];
}

$result = array_values($grouped);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
