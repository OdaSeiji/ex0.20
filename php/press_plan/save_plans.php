<?php
// 計画の新規保存（複数行をまとめて）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "plan_common.php";

$body = json_decode(file_get_contents("php://input"), true) ?? [];
$err = checkDateMachine($body["plan_date"] ?? "", $body["press_machine"] ?? 0);
$rows = $body["rows"] ?? [];
if (!$err && !$rows) $err = "金型を選んでください";
$data = [];
foreach ($rows as $r) {
    if ($err) break;
    $n = normalizePlanRow($r);
    if (is_string($n)) $err = $n; else $data[] = $n;
}
if ($err) {
    echo json_encode(["ok" => false, "message" => $err], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO t_press_plan (dies_id, production_number_id, shift_id, ordinal, quantity, pressing_type_id, billet_origin, billet_length, nitride_use, note, plan_date, press_machine)
        VALUES (:dies_id, :production_number_id, :shift_id, :ordinal, :quantity, :pressing_type_id, :billet_origin, :billet_length, :nitride_use, :note, :plan_date, :press_machine)
    ");
    foreach ($data as $d) {
        $stmt->execute($d + [":plan_date" => $body["plan_date"], ":press_machine" => (int)$body["press_machine"]]);
    }
    $pdo->commit();
    echo json_encode(["ok" => true, "count" => count($data)]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["ok" => false, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
