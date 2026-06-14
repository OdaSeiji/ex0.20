<?php
require_once "./../db.php";

$id    = $_POST["id"]    ?? null;
$field = $_POST["field"] ?? "issue_detail";
$value = $_POST["value"] ?? null;

$allowed = ["issue_detail", "issue_title_jp", "issue_title_vn", "priority"];

if (!$id || !in_array($field, $allowed)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$sql = "UPDATE t_die_issue SET {$field} = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$value, $id]);

echo json_encode(["status" => "success"]);
