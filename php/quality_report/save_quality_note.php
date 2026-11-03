<?php
require_once __DIR__ . "/common.php";

// 押出ごとの品質メモ（t_press.quality_note）を保存する。空にすると NULL
//   POST JSON: { press_id, note }

$body    = qrJsonBody();
$pressId = (int)($body["press_id"] ?? 0);
$note    = trim((string)($body["note"] ?? ""));

if (mb_strlen($note) > 1000) qrFail(400, "品質メモは1000文字以内で入力してください");

try {
    if (!qrPressInWindow($pdo, $pressId)) qrFail(409, "1か月より前の押出は、旧画面で修正してください");
    $stmt = $pdo->prepare("UPDATE t_press SET quality_note = :note WHERE id = :id");
    $stmt->bindValue(":note", $note === "" ? null : $note, $note === "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":id", $pressId, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
