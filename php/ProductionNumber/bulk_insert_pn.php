<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

$rows = json_decode(file_get_contents("php://input"), true);
if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$sql = "INSERT IGNORE INTO m_production_numbers
    (production_number, billet_material_id, production_length, cross_section_area, created_at)
    VALUES (?, ?, ?, ?, NOW())";

$stmt = $pdo->prepare($sql);
$inserted = 0;

foreach ($rows as $row) {
    $pn    = trim($row["production_number"] ?? "");
    if (!$pn) continue;

    $matId = (int)($row["billet_material_id"] ?? 0) ?: null;
    $len   = ($row["production_length"]  ?? "") !== "" ? $row["production_length"]  : null;
    $area  = ($row["cross_section_area"] ?? "") !== "" ? $row["cross_section_area"] : null;

    $stmt->execute([$pn, $matId, $len, $area]);
    $inserted += $stmt->rowCount();
}

echo json_encode(["status" => "ok", "inserted" => $inserted]);
