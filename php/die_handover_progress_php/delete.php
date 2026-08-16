<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$d = json_decode(file_get_contents("php://input"), true);
if (empty($d["id"])) {
    echo json_encode(["status" => "error", "message" => "id が必要です"]);
    exit;
}

$pdo->prepare("DELETE FROM t_die_handover_progress WHERE id = ?")->execute([$d["id"]]);
echo json_encode(["status" => "ok"]);
