<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "press_common.php";

$body = json_decode(file_get_contents("php://input"), true);
if (!is_array($body) || !isset($body["press"]) || !is_array($body["press"]) || empty($body["id"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "不正なリクエストです"], JSON_UNESCAPED_UNICODE);
    exit;
}

$pressId = (int)$body["id"];
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

    $exists = $pdo->prepare("SELECT id FROM t_press WHERE id = :id");
    $exists->bindValue(":id", $pressId, PDO::PARAM_INT);
    $exists->execute();
    if (!$exists->fetch()) {
        throw new Exception("対象レコードが見つかりません (id: {$pressId})");
    }

    $columns = pressColumns();
    $setClause = implode(", ", array_map(fn($c) => "{$c} = :{$c}", $columns));
    $stmt = $pdo->prepare("UPDATE t_press SET {$setClause} WHERE id = :id");
    bindPressValues($stmt, $p);
    $stmt->bindValue(":id", $pressId, PDO::PARAM_INT);
    $stmt->execute();

    // サブテーブルは一旦全削除してから現在のフォーム内容で作り直す
    foreach ([
        "t_bundle" => "press_id",
        "t_using_aging_rack" => "t_press_id",
        "t_press_work_length_quantity" => "press_id",
        "t_time_press" => "press_id",
        "t_pull_press" => "press_id",
        "t_cut_press" => "press_id",
    ] as $table => $col) {
        $del = $pdo->prepare("DELETE FROM {$table} WHERE {$col} = :id");
        $del->bindValue(":id", $pressId, PDO::PARAM_INT);
        $del->execute();
    }

    saveSubTables($pdo, $pressId, $bundle, $rack, $workLen, $time, $pull, $cut, $p["press_date_at"]);

    $pdo->commit();
    echo json_encode(["success" => true, "press_id" => $pressId], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
