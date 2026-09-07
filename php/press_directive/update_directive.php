<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "press_directive_common.php";

$body = json_decode(file_get_contents("php://input"), true);
if (!$body || empty($body["id"]) || empty($body["dies_id"])) {
    echo json_encode(["status" => "error", "message" => "id and dies_id are required"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $cols = directiveColumns();
    $setList = implode(", ", array_map(fn($c) => "{$c} = :{$c}", $cols));

    $stmt = $pdo->prepare("UPDATE t_press_directive SET {$setList} WHERE id = :id");
    bindDirectiveValues($stmt, $body);
    $stmt->bindValue(":id", (int)$body["id"], PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();
    echo json_encode(["status" => "ok"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
