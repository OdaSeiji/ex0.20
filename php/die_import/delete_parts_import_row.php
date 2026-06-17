<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input = json_decode(file_get_contents("php://input"), true);
$id    = $input["id"] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "id が指定されていません"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM t_parts_import_tmp WHERE id = ? AND import_flag = 0");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "error", "message" => "削除対象が見つかりません（転送済の行は削除できません）"]);
}
