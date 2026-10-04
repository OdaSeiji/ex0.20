<?php
// 計画1行の修正
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "plan_common.php";

$body = json_decode(file_get_contents("php://input"), true) ?? [];
$id = (int)($body["id"] ?? 0);
$err = $id > 0 ? checkDateMachine($body["plan_date"] ?? "", $body["press_machine"] ?? 0) : "計画を選んでください";
$n = $err ? null : normalizePlanRow($body);
if (!$err && is_string($n)) $err = $n;
if ($err) {
    echo json_encode(["ok" => false, "message" => $err], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE t_press_plan SET
        dies_id = :dies_id, production_number_id = :production_number_id, shift_id = :shift_id, ordinal = :ordinal,
        quantity = :quantity, nitride_use = :nitride_use, note = :note, plan_date = :plan_date, press_machine = :press_machine
    WHERE id = :id
");
$stmt->execute($n + [":plan_date" => $body["plan_date"], ":press_machine" => (int)$body["press_machine"], ":id" => $id]);
echo json_encode(["ok" => true]);
