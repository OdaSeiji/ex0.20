<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

// 品番の一括登録（CSV・Excel の取り込み）。単重（specific_weight）と時効処理（aging_type_id）も入れる
$rows = json_decode(file_get_contents("php://input"), true);
if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$sql = "INSERT IGNORE INTO m_production_numbers
    (production_number, billet_material_id, production_length, cross_section_area, specific_weight, aging_type_id, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $pdo->prepare($sql);
$inserted = 0;
$num = fn($v) => (($v ?? "") !== "" && is_numeric($v)) ? $v : null;

foreach ($rows as $row) {
    $pn    = trim($row["production_number"] ?? "");
    if (!$pn) continue;

    $matId   = (int)($row["billet_material_id"] ?? 0) ?: null;
    $len     = $num($row["production_length"] ?? null);
    $area    = $num($row["cross_section_area"] ?? null);
    $weight  = $num($row["specific_weight"] ?? null);
    $agingId = in_array((int)($row["aging_type_id"] ?? 0), [1, 2, 3], true) ? (int)$row["aging_type_id"] : null;

    $stmt->execute([$pn, $matId, $len, $area, $weight, $agingId]);
    if ($stmt->rowCount() > 0) {
        $inserted++;
    }
}

echo json_encode(["status" => "ok", "inserted" => $inserted]);
