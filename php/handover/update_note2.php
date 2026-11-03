<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

// 備考（note2）だけを更新する（一覧のインライン編集用）
//   update_die_handover.php は全項目を上書きするので、1項目だけ直すときはこちらを使う
$body = json_decode(file_get_contents("php://input"), true);
$id   = (int)($body["id"] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "invalid id"]);
    exit;
}
$note2 = trim((string)($body["note2"] ?? ""));

$stmt = $pdo->prepare("UPDATE t_die_handover SET note2 = ? WHERE id = ?");
$stmt->execute([$note2 === "" ? null : $note2, $id]);

$stmt = $pdo->prepare("SELECT note2, updated_at FROM t_die_handover WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["status" => "ok", "note2" => $row["note2"] ?? null, "updated_at" => $row["updated_at"] ?? null], JSON_UNESCAPED_UNICODE);
