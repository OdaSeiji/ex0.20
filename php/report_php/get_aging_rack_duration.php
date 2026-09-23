<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 時効ラックの使用期間（日数） = 最終梱包日 − 押出日
//   解放の記録（t_packing_box 経由の梱包）があるラックのみが対象
//   集計対象は「解放日が期間内」のラック（上部の日別「解放」と同じ母集団）
//   梱包日が押出日より前のもの（入力ミスと思われる）は除外し、件数だけ返す
//   月別推移は、期間の終了月から遡って12か月分（解放月ベース）

$isDate = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

$to   = $_GET["to"]   ?? "";
$from = $_GET["from"] ?? "";
if (!$isDate($to))   $to   = date("Y-m-d");
if (!$isDate($from)) $from = date("Y-m-d", strtotime($to . " -29 days"));
if ($from > $to) [$from, $to] = [$to, $from];

$monthlyFrom = date("Y-m-01", strtotime(substr($to, 0, 7) . "-01 -11 months"));
$queryFrom   = min($from, $monthlyFrom);

$sql = "
    SELECT
        DATE_FORMAT(x.released_at, '%Y-%m-%d')      AS released_at,
        DATEDIFF(x.released_at, p.press_date_at)    AS days
    FROM (
        SELECT pb.using_aging_rack_id, MAX(k.packing_date) AS released_at
        FROM t_packing_box pb
        JOIN t_packing k ON k.id = pb.packing_id
        WHERE pb.using_aging_rack_id IS NOT NULL
        GROUP BY pb.using_aging_rack_id
    ) x
    JOIN t_using_aging_rack r ON r.id = x.using_aging_rack_id
    JOIN t_press p            ON p.id = r.t_press_id
    WHERE x.released_at BETWEEN :from AND :to
";
$stmt = $pdo->prepare($sql);
$stmt->execute([":from" => $queryFrom, ":to" => $to]);

$periodDays  = [];
$monthlyDays = [];
$excluded    = 0;
foreach ($stmt as $row) {
    $days = (int)$row["days"];
    $inPeriod = $row["released_at"] >= $from;
    if ($days < 0) {
        if ($inPeriod) $excluded++;
        continue;
    }
    if ($inPeriod) $periodDays[] = $days;
    if ($row["released_at"] >= $monthlyFrom) {
        $monthlyDays[substr($row["released_at"], 0, 7)][] = $days;
    }
}

function stats(array $days): array {
    $n = count($days);
    if ($n === 0) return ["count" => 0, "avg" => null, "median" => null, "max" => null];
    sort($days);
    $median = $n % 2 ? $days[intdiv($n, 2)] : ($days[$n / 2 - 1] + $days[$n / 2]) / 2;
    return [
        "count"  => $n,
        "avg"    => round(array_sum($days) / $n, 1),
        "median" => $median,
        "max"    => $days[$n - 1],
    ];
}

// 分布（日数の区切り）
$bucketDefs = [
    ["label" => "0-3",   "min" => 0,  "max" => 3],
    ["label" => "4-7",   "min" => 4,  "max" => 7],
    ["label" => "8-14",  "min" => 8,  "max" => 14],
    ["label" => "15-30", "min" => 15, "max" => 30],
    ["label" => "31-60", "min" => 31, "max" => 60],
    ["label" => "61+",   "min" => 61, "max" => null],
];
$buckets = array_map(function ($b) use ($periodDays) {
    $b["count"] = count(array_filter($periodDays,
        fn($d) => $d >= $b["min"] && ($b["max"] === null || $d <= $b["max"])));
    return $b;
}, $bucketDefs);

// 月別（12か月分、データのない月も返す）
$monthly = [];
for ($m = substr($monthlyFrom, 0, 7); $m <= substr($to, 0, 7); $m = date("Y-m", strtotime($m . "-01 +1 month"))) {
    $monthly[] = ["month" => $m] + stats($monthlyDays[$m] ?? []);
}

echo json_encode([
    "from"     => $from,
    "to"       => $to,
    "summary"  => stats($periodDays) + ["excluded" => $excluded],
    "buckets"  => $buckets,
    "monthly"  => $monthly,
], JSON_UNESCAPED_UNICODE);
