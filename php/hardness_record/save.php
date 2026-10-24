<?php
require_once __DIR__ . "/../sample_record/common.php";

// 硬さ測定記録：保存
//   POST JSON: {
//     press_id,
//     rows: [{ id: 既存の行の id | null（新しい行）, position: "1H", values: [10個の測定値（空欄は ""）] }],
//     delete_ids: [削除する行の id],
//     check_date: "YYYY-MM-DD" | null（t_press.hardness_check_date）,
//     finish: 0 | 1（t_press_sub.hardness_finish）
//   }
//   ex0.11 は「最初の保存で全部の行を INSERT、そのあとはセルを変えるたびにその行を UPDATE」だったのを、1回の保存にまとめた

$body      = srJsonBody();
$pressId   = (int)($body["press_id"] ?? 0);
$rows      = $body["rows"] ?? [];
$deleteIds = array_map("intval", $body["delete_ids"] ?? []);
$checkDate = $body["check_date"] ?? null;
$finish    = (int)($body["finish"] ?? 0);

if ($pressId <= 0) srFail(400, "press_id が不正です");
if (!is_array($rows)) srFail(400, "rows が不正です");
if (!in_array($finish, [0, 1], true)) srFail(400, "完了の値が不正です");

$clean = [];
foreach ($rows as $r) {
    $pos = trim((string)($r["position"] ?? ""));
    if ($pos === "" || mb_strlen($pos) > 5) srFail(400, "位置は1〜5文字で入力してください");
    $values = array_values($r["values"] ?? []);
    if (count($values) !== 10) srFail(400, "測定値の数が不正です");
    foreach ($values as $i => $v) {
        $v = trim((string)$v);
        if ($v !== "" && (!is_numeric($v) || strlen($v) > 11)) srFail(400, "測定値は数字で入力してください（{$pos}）");
        $values[$i] = $v;
    }
    $clean[] = ["id" => isset($r["id"]) ? (int)$r["id"] : null, "position" => $pos, "values" => $values];
}

$cols = implode(", ", array_map(fn($i) => "`{$i}hn`", range(1, 10)));
$sets = implode(", ", array_map(fn($i) => "`{$i}hn` = :v{$i}", range(1, 10)));
$phs  = implode(", ", array_map(fn($i) => ":v{$i}", range(1, 10)));

try {
    if (!srPressInfo($pdo, $pressId)) srFail(404, "押出が見つかりません");
    srCheckMeasureDate($pdo, $checkDate);
    $pdo->beginTransaction();

    if ($deleteIds) {
        $stmt = $pdo->prepare("DELETE FROM t_hardness WHERE id = :id AND press_id = :press_id");
        foreach ($deleteIds as $did) $stmt->execute([":id" => $did, ":press_id" => $pressId]);
    }

    $upd = $pdo->prepare("UPDATE t_hardness SET position = :pos, {$sets} WHERE id = :id AND press_id = :press_id");
    $ins = $pdo->prepare("INSERT INTO t_hardness (press_id, position, {$cols}) VALUES (:press_id, :pos, {$phs})");
    foreach ($clean as $r) {
        $params = [":press_id" => $pressId, ":pos" => $r["position"]];
        foreach ($r["values"] as $i => $v) $params[":v" . ($i + 1)] = $v;
        if ($r["id"]) {
            $upd->execute($params + [":id" => $r["id"]]);
        } else {
            $ins->execute($params);
        }
    }

    $stmt = $pdo->prepare("UPDATE t_press SET hardness_check_date = :d WHERE id = :id");
    $stmt->bindValue(":d", $checkDate, $checkDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":id", $pressId, PDO::PARAM_INT);
    $stmt->execute();

    srSetPressSub($pdo, $pressId, "hardness_finish", $finish);

    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    srFail(500, $e->getMessage());
}
