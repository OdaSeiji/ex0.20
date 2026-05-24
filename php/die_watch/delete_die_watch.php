<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input = json_decode(file_get_contents("php://input"), true);
$id    = $input["id"] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID required"]);
    exit;
}

/* 紐づく issue が存在する場合は削除不可 */
$check = $pdo->prepare("SELECT COUNT(*) FROM t_die_issue WHERE die_watch_id = ?");
$check->execute([$id]);
if ($check->fetchColumn() > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "紐づくIssueが存在するため削除できません",
    ]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM t_die_watch WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "ok"]);
