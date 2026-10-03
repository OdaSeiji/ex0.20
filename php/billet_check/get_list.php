<?php
// 太さごとの棚卸しの記録（新しい順に60件）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "columns.php";

$sizeId = (int)($_GET["size_id"] ?? 0);
$select = [];
foreach (BILLET_COLUMNS as $key => [$col]) {
    $select[] = "c.`{$col}` AS `{$key}`";
}
$stmt = $pdo->prepare("
    SELECT c.id, c.check_at, c.staff_id, s.staff_name, " . implode(", ", $select) . "
    FROM t_checkbillet c
    LEFT JOIN m_staff s ON s.id = c.staff_id
    WHERE c.billet_size_id = :size_id
    ORDER BY c.check_at DESC, c.id DESC
    LIMIT 60
");
$stmt->execute([":size_id" => $sizeId]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
