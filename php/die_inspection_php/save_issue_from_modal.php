<?php
require_once __DIR__ . "./../db.php";

$die_id     = $_POST["die_id"];
$title      = $_POST["issue_title"];
$desc       = $_POST["issue_description"];
$priority   = $_POST["priority"];

// reported_by は仮で 1（ログイン機能が無いので）
$reported_by = 1;

$sql = "INSERT INTO t_die_issue 
        (die_id, issue_title, issue_description, reported_by, priority, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$die_id, $title, $desc, $reported_by, $priority]);

if ($ok) {
    echo json_encode([
        "status" => "ok",
        "issue_id" => $pdo->lastInsertId()
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "DB insert failed"
    ]);
}
