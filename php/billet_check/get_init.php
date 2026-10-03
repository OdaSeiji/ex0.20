<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "columns.php";

$sizes = $pdo->query("SELECT id, billet_size FROM m_billet_size ORDER BY billet_size")->fetchAll();
// 担当者は operator だけ
$staff = $pdo->query("SELECT id, staff_name FROM m_staff WHERE leave_at IS NULL AND role = 'operator' ORDER BY staff_name")->fetchAll();
$columns = [];
foreach (BILLET_COLUMNS as $key => [$col, $material, $length, $vn]) {
    $columns[] = ["key" => $key, "material" => $material, "length" => $length, "vn" => $vn];
}
echo json_encode(["sizes" => $sizes, "staff" => $staff, "columns" => $columns, "size9Id" => SIZE_ID_9INCH], JSON_UNESCAPED_UNICODE);
