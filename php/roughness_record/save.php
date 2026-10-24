<?php
require_once __DIR__ . "/../sample_record/common.php";

// 粗さ測定記録：保存
//   POST JSON: {
//     press_id,
//     rows: [{ id: 既存の行の id | null（新しい行）, position: "1H", values: [rz1, die_mark_1, rz2, die_mark_2, rz3, die_mark_3]（空欄は ""）, note }],
//     delete_ids: [削除する行の id],
//     check_date: "YYYY-MM-DD" | null（t_press.measurement_check_date）,
//     staff_id: m_staff.id | null（t_press.measurement_staff）
//   }
//   ex0.11 は「最初の保存で全部の行を INSERT、そのあとはセルを変えるたびにその行を UPDATE、測定日・担当者は選んだときに保存」だったのを、1回の保存にまとめた
//   測定値が数字かどうかは画面で確かめる（ex0.11 の古い記録には "2.0,2.5" のような値があり、触っていない行まで止めないため）

const RR_COLS = ["rz1", "die_mark_1", "rz2", "die_mark_2", "rz3", "die_mark_3"];

$body      = srJsonBody();
$pressId   = (int)($body["press_id"] ?? 0);
$rows      = $body["rows"] ?? [];
$deleteIds = array_map("intval", $body["delete_ids"] ?? []);
$checkDate = $body["check_date"] ?? null;
$staffId   = isset($body["staff_id"]) ? (int)$body["staff_id"] : null;

if ($pressId <= 0) srFail(400, "press_id が不正です");
if (!is_array($rows)) srFail(400, "rows が不正です");

$clean = [];
foreach ($rows as $r) {
    $pos = trim((string)($r["position"] ?? ""));
    if ($pos === "" || mb_strlen($pos) > 11) srFail(400, "位置は1〜11文字で入力してください");
    $values = array_values($r["values"] ?? []);
    if (count($values) !== count(RR_COLS)) srFail(400, "測定値の数が不正です");
    foreach ($values as $i => $v) {
        $v = trim((string)$v);
        if (mb_strlen($v) > 11) srFail(400, "測定値が長すぎます（{$pos}）");
        $values[$i] = $v;
    }
    $note = trim((string)($r["note"] ?? ""));
    if (mb_strlen($note) > 200) srFail(400, "備考は200文字までです（{$pos}）");
    $clean[] = ["id" => isset($r["id"]) ? (int)$r["id"] : null, "position" => $pos, "values" => $values, "note" => $note];
}

$cols = implode(", ", RR_COLS);
$sets = implode(", ", array_map(fn($c) => "{$c} = :{$c}", RR_COLS));
$phs  = implode(", ", array_map(fn($c) => ":{$c}", RR_COLS));

try {
    if (!srPressInfo($pdo, $pressId)) srFail(404, "押出が見つかりません");
    srCheckMeasureDate($pdo, $checkDate);
    if ($staffId !== null) {
        $stmt = $pdo->prepare("SELECT 1 FROM m_staff WHERE id = :id");
        $stmt->execute([":id" => $staffId]);
        if (!$stmt->fetchColumn()) srFail(400, "担当者が見つかりません");
    }
    $pdo->beginTransaction();

    if ($deleteIds) {
        $stmt = $pdo->prepare("DELETE FROM t_measurement WHERE id = :id AND press_id = :press_id");
        foreach ($deleteIds as $did) $stmt->execute([":id" => $did, ":press_id" => $pressId]);
    }

    $upd = $pdo->prepare("UPDATE t_measurement SET position = :pos, {$sets}, note = :note WHERE id = :id AND press_id = :press_id");
    $ins = $pdo->prepare("INSERT INTO t_measurement (press_id, position, {$cols}, note) VALUES (:press_id, :pos, {$phs}, :note)");
    foreach ($clean as $r) {
        $params = [":press_id" => $pressId, ":pos" => $r["position"], ":note" => $r["note"]];
        foreach (RR_COLS as $i => $c) $params[":{$c}"] = $r["values"][$i];
        if ($r["id"]) {
            $upd->execute($params + [":id" => $r["id"]]);
        } else {
            $ins->execute($params);
        }
    }

    $stmt = $pdo->prepare("UPDATE t_press SET measurement_check_date = :d, measurement_staff = :s WHERE id = :id");
    $stmt->bindValue(":d", $checkDate, $checkDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":s", $staffId, $staffId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(":id", $pressId, PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    srFail(500, $e->getMessage());
}
