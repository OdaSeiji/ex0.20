<?php
require_once __DIR__ . "/common.php";

// エッチング評価記録：1つのセル（ある位置の、ある回）の判定を保存する。押すたびに呼ぶ
//   POST JSON: { press_id, id: t_etching.id, round: 0〜4（Lần 0〜4）, jug, code }

$body    = srJsonBody();
$pressId = (int)($body["press_id"] ?? 0);
$rowId   = (int)($body["id"] ?? 0);
$round   = (int)($body["round"] ?? -1);
$jug     = (int)($body["jug"] ?? 0);
$code    = (int)($body["code"] ?? 0);

if ($pressId <= 0 || $rowId <= 0) srFail(400, "id が不正です");
if (!isset(ER_ROUND_COLS[$round])) srFail(400, "回が不正です");
if (!in_array($jug, ER_JUGS, true)) srFail(400, "判定が不正です");
if (!in_array($code, ER_CODES, true)) srFail(400, "不良コードが不正です");
if ($jug !== 2) $code = ER_NO_CODE;   // 不良コードは NG のときだけ

[$jugCol, $codeCol] = ER_ROUND_COLS[$round];
try {
    $stmt = $pdo->prepare("UPDATE t_etching SET {$jugCol} = :jug, {$codeCol} = :code WHERE id = :id AND press_id = :press_id");
    $stmt->execute([":jug" => $jug, ":code" => $code, ":id" => $rowId, ":press_id" => $pressId]);
    if ($stmt->rowCount() === 0) {
        $chk = $pdo->prepare("SELECT 1 FROM t_etching WHERE id = :id AND press_id = :press_id");
        $chk->execute([":id" => $rowId, ":press_id" => $pressId]);
        if (!$chk->fetchColumn()) srFail(404, "記録が見つかりません。画面を読み込み直してください");
    }
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
