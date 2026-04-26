<?php
require_once __DIR__ . "/../db.php";

$die_id = $_GET["die_id"];

// die_id が無い場合はエラー
if (!$die_id) {
    echo json_encode([
        "status" => "error",
        "message" => "die_id is required"
    ]);
    exit;
}

// 未完了の Issue を検索
$sql = "SELECT id, completed_at
        FROM t_die_issue
        WHERE die_id = ?
        ORDER BY id DESC
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$die_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

// 未完了 Issue がある場合
if ($issue && $issue["completed_at"] === null) {
    echo json_encode([
        "status" => "ok",
        "need_new_issue" => false,
        "issue_id" => $issue["id"]
    ]);
    exit;
}

// 未完了 Issue が無い場合
echo json_encode([
    "status" => "ok",
    "need_new_issue" => true
]);
