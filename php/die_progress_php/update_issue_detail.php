<?php
require_once "./../db.php";

$id = $_POST["id"] ?? null;
$detail = $_POST["issue_detail"] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID がありません"]);
    exit;
}

$sql = "UPDATE t_die_issue SET issue_detail = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$detail, $id]);

echo json_encode(["status" => "success"]);
