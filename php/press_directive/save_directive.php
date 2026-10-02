<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "press_directive_common.php";

$body = json_decode(file_get_contents("php://input"), true);
if (!$body || empty($body["dies_id"])) {
    echo json_encode(["status" => "error", "message" => "dies_id is required"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $cols = directiveColumns();
    $placeholders = implode(", ", array_map(fn($c) => ":{$c}", $cols));
    $colList = implode(", ", $cols);

    // entry_source：入力元 1 = ex0.20 プレス指示作成（旧画面 ex0.11 は列の初期値 0）。作った画面の記録なので更新では変えない
    $stmt = $pdo->prepare("
        INSERT INTO t_press_directive ({$colList}, created_at, entry_source)
        VALUES ({$placeholders}, CURDATE(), 1)
    ");
    bindDirectiveValues($stmt, $body);
    $stmt->execute();

    $newId = $pdo->lastInsertId();

    $pdo->commit();
    echo json_encode(["status" => "ok", "id" => $newId]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
