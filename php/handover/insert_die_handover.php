<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$inserted = 0;
$errors   = [];

foreach ($rows as $row) {
    $die_id = intval($row["die_id"]);

    if (!$die_id) {
        $errors[] = "die_id が不正です";
        continue;
    }

    $insStmt = $pdo->prepare("INSERT INTO t_die_handover (die_id) VALUES (?)");
    $insStmt->execute([$die_id]);
    $inserted++;
}

echo json_encode([
    "status"   => "ok",
    "inserted" => $inserted,
    "errors"   => $errors,
], JSON_UNESCAPED_UNICODE);
