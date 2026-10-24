<?php
require_once __DIR__ . "/common.php";

// エッチング評価記録：記録を始める（サンプル位置の行をまとめて作る）
//   POST JSON: { press_id, positions: ["1H", "1E", …] }
//   ex0.11 と同じく、1回目（Lần 0）は OK、2回目以降は空欄で作る。NG の位置だけあとで押して変える
//   すでに行がある押出には作らない（二重に作らないため）

$body      = srJsonBody();
$pressId   = (int)($body["press_id"] ?? 0);
$positions = $body["positions"] ?? [];

if ($pressId <= 0) srFail(400, "press_id が不正です");
if (!is_array($positions) || count($positions) === 0 || count($positions) > 1000) srFail(400, "位置が不正です");
foreach ($positions as $i => $p) {
    $p = trim((string)$p);
    if ($p === "" || mb_strlen($p) > 5) srFail(400, "位置は1〜5文字です");
    $positions[$i] = $p;
}

try {
    if (!srPressInfo($pdo, $pressId)) srFail(404, "押出が見つかりません");
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM t_etching WHERE press_id = :id FOR UPDATE");
    $stmt->execute([":id" => $pressId]);
    if ((int)$stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        srFail(409, "この押出の記録はもうあります。画面を読み込み直してください");
    }
    $cols = implode(", ", array_map(fn($c) => "{$c[0]}, {$c[1]}", ER_ROUND_COLS));
    $ins = $pdo->prepare("
        INSERT INTO t_etching (press_id, Position, {$cols})
        VALUES (:id, :pos, 1, :nc, 3, :nc, 3, :nc, 3, :nc, 3, :nc)
    ");
    foreach ($positions as $p) $ins->execute([":id" => $pressId, ":pos" => $p, ":nc" => ER_NO_CODE]);
    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    srFail(500, $e->getMessage());
}
