<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "press_common.php";

$body = json_decode(file_get_contents("php://input"), true);
if (!is_array($body) || !isset($body["press"]) || !is_array($body["press"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "不正なリクエストです"], JSON_UNESCAPED_UNICODE);
    exit;
}

$p       = $body["press"];
$bundle  = is_array($body["bundle"] ?? null) ? $body["bundle"] : [];
$rack    = is_array($body["rack"] ?? null) ? $body["rack"] : [];
$workLen = is_array($body["workLength"] ?? null) ? $body["workLength"] : [];
$time    = is_array($body["time"] ?? null) ? $body["time"] : [];
$pull    = is_array($body["pull"] ?? null) ? $body["pull"] : [];
$cut     = is_array($body["cut"] ?? null) ? $body["cut"] : [];

foreach (PRESS_REQUIRED_KEYS as $key) {
    if (!isset($p[$key]) || $p[$key] === "") {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "必須項目が不足しています: {$key}"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $columns = pressColumns();
    $columns[] = "created_at";
    $placeholders = array_map(fn($c) => ":{$c}", $columns);
    $sql = "INSERT INTO t_press (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    bindPressValues($stmt, $p);
    $stmt->bindValue(":created_at", date("Y-m-d"), PDO::PARAM_STR);
    $stmt->execute();
    $pressId = (int)$pdo->lastInsertId();

    saveSubTables($pdo, $pressId, $bundle, $rack, $workLen, $time, $pull, $cut, $p["press_date_at"]);

    $pdo->commit();
    echo json_encode(["success" => true, "press_id" => $pressId], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
