<?php
require_once "../db.php";
header("Content-Type: application/json; charset=UTF-8");

$dieId       = (int)($_POST['die_id'] ?? 0);
$conditionId = $_POST['condition_id'] ?? '';
$conditionId = $conditionId === '' ? null : (int)$conditionId;

if (!$dieId) {
    echo json_encode(["status" => "error", "message" => "die_id is required"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE m_dies SET die_condition_id = ? WHERE id = ?");
    $stmt->execute([$conditionId, $dieId]);

    $stmt = $pdo->prepare("
        INSERT INTO t_die_condition_history (die_id, condition_id, changed_at, staff_id, memo)
        VALUES (?, ?, NOW(), 9999, ?)
    ");
    $stmt->execute([$dieId, $conditionId, "手動変更(die_condition_manage.html)"]);

    $pdo->commit();
    echo json_encode(["status" => "ok"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
