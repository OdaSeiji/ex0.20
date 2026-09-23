<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 日別の 使用ラック数 / 解放ラック数
//   使用 = t_using_aging_rack の1行を1ラック使用とし、押出日（t_press.press_date_at）で計上
//   解放 = そのラックが最後に梱包された日（t_packing_box 経由の MAX(t_packing.packing_date)）で計上
//   ※ t_using_aging_rack.t_packing_id は未使用（全行NULL）のため、梱包との紐づけは t_packing_box を使う
//   ※ 梱包記録が1件もないラックは解放に計上されない

$isDate = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

$to   = $_GET["to"]   ?? "";
$from = $_GET["from"] ?? "";
if (!$isDate($to))   $to   = date("Y-m-d");
if (!$isDate($from)) $from = date("Y-m-d", strtotime($to . " -29 days"));
if ($from > $to) [$from, $to] = [$to, $from];

$usedSql = "
    SELECT DATE_FORMAT(p.press_date_at, '%Y-%m-%d') AS d, COUNT(*) AS cnt
    FROM t_using_aging_rack r
    JOIN t_press p ON p.id = r.t_press_id
    WHERE p.press_date_at BETWEEN :from AND :to
    GROUP BY p.press_date_at
";
$stmt = $pdo->prepare($usedSql);
$stmt->execute([":from" => $from, ":to" => $to]);
$used = [];
foreach ($stmt as $row) $used[$row["d"]] = (int)$row["cnt"];

$releasedSql = "
    SELECT DATE_FORMAT(x.released_at, '%Y-%m-%d') AS d, COUNT(*) AS cnt
    FROM (
        SELECT pb.using_aging_rack_id, MAX(k.packing_date) AS released_at
        FROM t_packing_box pb
        JOIN t_packing k ON k.id = pb.packing_id
        WHERE pb.using_aging_rack_id IS NOT NULL
        GROUP BY pb.using_aging_rack_id
    ) x
    WHERE x.released_at BETWEEN :from AND :to
    GROUP BY x.released_at
";
$stmt = $pdo->prepare($releasedSql);
$stmt->execute([":from" => $from, ":to" => $to]);
$released = [];
foreach ($stmt as $row) $released[$row["d"]] = (int)$row["cnt"];

// 期間内の全日付を0埋めで返す
$days = [];
for ($d = $from; $d <= $to; $d = date("Y-m-d", strtotime($d . " +1 day"))) {
    $days[] = [
        "date"     => $d,
        "used"     => $used[$d] ?? 0,
        "released" => $released[$d] ?? 0,
    ];
}

echo json_encode(["from" => $from, "to" => $to, "days" => $days], JSON_UNESCAPED_UNICODE);
