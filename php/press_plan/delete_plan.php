<?php
// 計画1行の削除（パスコードが必要）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "plan_common.php";

$body = json_decode(file_get_contents("php://input"), true) ?? [];
$id = (int)($body["id"] ?? 0);
if ((string)($body["passcode"] ?? "") !== PLAN_DELETE_PASSCODE) {
    echo json_encode(["ok" => false, "code" => "passcode"]);
    exit;
}
if ($id <= 0) {
    echo json_encode(["ok" => false, "code" => "id"]);
    exit;
}
$stmt = $pdo->prepare("DELETE FROM t_press_plan WHERE id = :id");
$stmt->execute([":id" => $id]);
echo json_encode(["ok" => $stmt->rowCount() === 1]);
