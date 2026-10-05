<?php
// 押出のスケジュール表（日付・号機ごと）。現場の計画表（KHUÔN CHÍNH / DỰ BỊ / SỐ BILLET / THỜI GIAN / Timeline）と同じ形
// - 並びは計画の「順番」（ordinal）。「5.1」のように小数のある行は、整数部が同じメインの行の予備の金型として横に並べる
// - 時間 ＝ 自動運転までの時間 ＋ 本数 ×（ビレットの長さ ÷ ラム速度 ＋ ビレットの交換時間）
//   ラム速度・nBn は、その型のいちばん新しい押出指示書。長さは計画で決めていればその値、無ければ押出指示書
//   押出指示書が無い型は号機の「1本あたりの平均」
//   標準時間（m_press_time_standard）が未設定なら、実績からの推定を使う
// - 押出材の本数 ＝ ビレット本数 ÷ n × m × 穴数（nBn：n 本のビレットで m 本の製品）
// - 実績（TT）は、その日・その号機・その型（予備を含む）の押出（t_press）とラック（t_using_aging_rack）
// - 開始の時刻と昼休み（45分）は、日付・号機ごとの設定（t_press_schedule_day）。
//   昼休みの位置は指定した計画のあと。指定が無い（または消えた）ときは 12:00 にいちばん近い型の区切り
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

const DAY_START = "08:00";
const LUNCH_MIN = 45;
const LUNCH_TARGET = "12:00";

$date = (string)($_GET["date"] ?? "");
$machine = (int)($_GET["machine"] ?? 0);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $machine < 1 || $machine > 4) {
    echo json_encode(["rows" => []]);
    exit;
}

// 標準時間（未設定なら推定）と 1本あたりの平均
$st = $pdo->prepare("SELECT startup_sec, billet_change_sec FROM m_press_time_standard WHERE press_machine = :mc");
$st->execute([":mc" => $machine]);
$std = $st->fetch() ?: ["startup_sec" => null, "billet_change_sec" => null];

$st = $pdo->prepare("
    WITH x AS (
        SELECT p.actual_billet_quantities AS q,
               TIME_TO_SEC(TIMEDIFF(p.press_finish_at, p.press_start_at)) - p.actual_billet_quantities * p.billet_length / d.ram_speed AS over_sec
        FROM t_press p JOIN t_press_directive d ON d.id = p.press_directive_id
        WHERE p.press_machine_no = :mc AND p.press_date_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
          AND d.ram_speed > 0 AND p.billet_length > 0 AND p.actual_billet_quantities >= 1 AND p.press_finish_at > p.press_start_at
    )
    SELECT (SUM(q * over_sec) - SUM(q) * SUM(over_sec) / COUNT(*)) / NULLIF(SUM(q * q) - SUM(q) * SUM(q) / COUNT(*), 0) AS change_sec,
           SUM(over_sec) / COUNT(*) AS avg_over, SUM(q) / COUNT(*) AS avg_q
    FROM x WHERE over_sec BETWEEN -600 AND 7200
");
$st->execute([":mc" => $machine]);
$e = $st->fetch();
$hasEst = $e && $e["change_sec"] !== null;
$estChange  = $hasEst ? (int)round($e["change_sec"]) : 0;
$estStartup = $hasEst ? (int)round($e["avg_over"] - $e["change_sec"] * $e["avg_q"]) : 0;
$usingEstimate = $std["startup_sec"] === null || $std["billet_change_sec"] === null;
$startupSec = $std["startup_sec"] !== null ? (int)$std["startup_sec"] : $estStartup;
$changeSec  = $std["billet_change_sec"] !== null ? (int)$std["billet_change_sec"] : $estChange;

$st = $pdo->prepare("
    SELECT SUM(TIME_TO_SEC(TIMEDIFF(press_finish_at, press_start_at))) / SUM(actual_billet_quantities)
    FROM t_press
    WHERE press_machine_no = :mc AND press_date_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
      AND press_finish_at > press_start_at AND actual_billet_quantities > 0
");
$st->execute([":mc" => $machine]);
$avgSecPerBillet = (int)round((float)$st->fetchColumn());

// その日・その号機の計画と、型ごとのいちばん新しい押出指示書
$st = $pdo->prepare("
    WITH last_dir AS (
        SELECT * FROM (
            SELECT d.dies_id, d.ram_speed, d.billet_length, nb.nbn,
                   ROW_NUMBER() OVER (PARTITION BY d.dies_id ORDER BY d.plan_date_at DESC, d.id DESC) AS rn
            FROM t_press_directive d LEFT JOIN m_nbn nb ON nb.id = d.nbn_id
        ) x WHERE rn = 1
    )
    SELECT pl.id, pl.dies_id, md.die_number, md.hole, pl.ordinal, pl.quantity, pl.pressing_type_id, pl.nitride_use, pl.note, pl.billet_origin,
           ld.ram_speed, COALESCE(pl.billet_length, ld.billet_length) AS billet_length, ld.nbn
    FROM t_press_plan pl
    LEFT JOIN m_dies md ON md.id = pl.dies_id
    LEFT JOIN last_dir ld ON ld.dies_id = pl.dies_id
    WHERE pl.plan_date = :date AND pl.press_machine = :mc
    ORDER BY pl.ordinal + 0, pl.ordinal, pl.id
");
$st->execute([":date" => $date, ":mc" => $machine]);
$plans = $st->fetchAll();

// 実績：型ごとのビレット本数・押出材の本数・時刻
$st = $pdo->prepare("
    SELECT p.dies_id,
           SUM(p.actual_billet_quantities) AS billets,
           SUM(IFNULL((SELECT SUM(r.work_quantity) FROM t_using_aging_rack r WHERE r.t_press_id = p.id), 0)) AS bars,
           DATE_FORMAT(MIN(p.press_start_at), '%H:%i') AS start_at,
           DATE_FORMAT(MAX(p.press_finish_at), '%H:%i') AS finish_at
    FROM t_press p
    WHERE p.press_date_at = :date AND p.press_machine_no = :mc
    GROUP BY p.dies_id
");
$st->execute([":date" => $date, ":mc" => $machine]);
$actual = [];
foreach ($st->fetchAll() as $a) $actual[(int)$a["dies_id"]] = $a;

// メインと予備に分ける（「5.1」は「5」の予備）
$mains = [];
$spares = [];
foreach ($plans as $p) {
    $ord = trim((string)$p["ordinal"]);
    if (preg_match('/^(\d+)\.(\d+)$/', $ord, $m) && (int)$m[2] > 0) {
        $spares[(int)$m[1]][] = $p;
    } else {
        $mains[] = $p;
    }
}
// メインが見つからない予備は、そのまま1行として出す
$mainOrds = [];
foreach ($mains as $p) {
    if (preg_match('/^\d+$/', trim((string)$p["ordinal"]))) $mainOrds[] = (int)$p["ordinal"];
}
foreach ($spares as $ord => $list) {
    if (!in_array($ord, $mainOrds, true)) {
        foreach ($list as $p) $mains[] = $p;
        unset($spares[$ord]);
    }
}
usort($mains, fn($a, $b) => ((float)$a["ordinal"] <=> (float)$b["ordinal"]) ?: ($a["id"] <=> $b["id"]));

// 日付・号機ごとの設定
$st = $pdo->prepare("SELECT TIME_FORMAT(start_time, '%H:%i') AS start_time, has_lunch, lunch_after_plan_id FROM t_press_schedule_day WHERE plan_date = :date AND press_machine = :mc");
$st->execute([":date" => $date, ":mc" => $machine]);
$day = $st->fetch() ?: ["start_time" => null, "has_lunch" => 0, "lunch_after_plan_id" => null];
$dayStart = $day["start_time"] ?: DAY_START;

$rows = [];
foreach ($mains as $p) {
    $ord = preg_match('/^\d+$/', trim((string)$p["ordinal"])) ? (int)$p["ordinal"] : null;
    $spareList = $ord !== null ? ($spares[$ord] ?? []) : [];
    $q = (int)$p["quantity"];

    // 時間（秒）
    $usingAverage = false;
    if ($q <= 0) {
        $sec = 0;
    } elseif ((float)$p["ram_speed"] > 0 && (int)$p["billet_length"] > 0) {
        $sec = $startupSec + $q * ((int)$p["billet_length"] / (float)$p["ram_speed"] + $changeSec);
    } else {
        $sec = $startupSec + $q * $avgSecPerBillet;
        $usingAverage = true;
    }
    $min = (int)ceil($sec / 60);

    // 押出材の本数
    $bars = null;
    if ($p["nbn"] && preg_match('/^(\d+)B(\d+)$/', $p["nbn"], $nm) && (int)$p["hole"] > 0) {
        $bars = (int)floor($q / (int)$nm[1] * (int)$nm[2] * (int)$p["hole"]);
    }

    // 実績（予備の型で押した分も足す）
    $dieIds = array_merge([(int)$p["dies_id"]], array_map(fn($s) => (int)$s["dies_id"], $spareList));
    $actBillets = 0; $actBars = 0; $actStart = null; $actFinish = null; $hasActual = false;
    foreach ($dieIds as $id) {
        if (!isset($actual[$id])) continue;
        $a = $actual[$id];
        $hasActual = true;
        $actBillets += (int)$a["billets"];
        $actBars += (int)$a["bars"];
        if ($actStart === null || $a["start_at"] < $actStart) $actStart = $a["start_at"];
        if ($actFinish === null || $a["finish_at"] > $actFinish) $actFinish = $a["finish_at"];
    }

    $rows[] = [
        "type"           => "plan",
        "plan_id"        => (int)$p["id"],
        "ordinal"        => $p["ordinal"],
        "die_number"     => $p["die_number"],
        "spares"         => array_map(fn($s) => ["die_number" => $s["die_number"], "note" => $s["note"]], $spareList),
        "note"           => $p["note"],
        "nitride_use"    => (int)$p["nitride_use"],
        "billet_length"  => $p["billet_length"] !== null ? (int)$p["billet_length"] : null,
        "billet_origin"  => (int)$p["billet_origin"],
        "pressing_type_id" => (int)$p["pressing_type_id"],
        "ram_speed"      => $p["ram_speed"] !== null ? (float)$p["ram_speed"] : null,
        "nbn"            => $p["nbn"],
        "plan_billets"   => $q,
        "plan_bars"      => $bars,
        "actual_billets" => $hasActual ? $actBillets : null,
        "actual_bars"    => $hasActual ? $actBars : null,
        "actual_start"   => $actStart,
        "actual_finish"  => $actFinish,
        "minutes"        => $min,
        "using_average"  => $usingAverage,
    ];
}

// 昼休みの位置（この行のあと）
$lunchAfter = null;
$lunchAuto = false;
if ((int)$day["has_lunch"] === 1 && $rows) {
    foreach ($rows as $i => $r) {
        if ($day["lunch_after_plan_id"] !== null && $r["plan_id"] === (int)$day["lunch_after_plan_id"]) $lunchAfter = $i;
    }
    if ($lunchAfter === null) {
        // 12:00 にいちばん近い型の区切り
        $lunchAuto = true;
        $target = strtotime("{$date} " . LUNCH_TARGET);
        $clock = strtotime("{$date} {$dayStart}");
        $best = null;
        foreach ($rows as $i => $r) {
            $clock += $r["minutes"] * 60;
            $diff = abs($clock - $target);
            if ($best === null || $diff < $best) { $best = $diff; $lunchAfter = $i; }
        }
    }
}

// 時刻を割り当てる（昼休みを入れて）
$out = [];
$clock = strtotime("{$date} {$dayStart}");
foreach ($rows as $i => $r) {
    $r["start"] = date("H:i", $clock);
    $clock += $r["minutes"] * 60;
    $r["end"] = date("H:i", $clock);
    $out[] = $r;
    if ($i === $lunchAfter) {
        $out[] = ["type" => "lunch", "minutes" => LUNCH_MIN, "start" => date("H:i", $clock), "end" => date("H:i", $clock + LUNCH_MIN * 60)];
        $clock += LUNCH_MIN * 60;
    }
}
$rows = $out;

echo json_encode([
    "rows" => $rows,
    "day" => [
        "start_time" => $dayStart, "has_lunch" => (int)$day["has_lunch"],
        "lunch_after_plan_id" => $day["lunch_after_plan_id"] !== null ? (int)$day["lunch_after_plan_id"] : null,
        "lunch_auto" => $lunchAuto,
    ],
    "standard" => [
        "startup_sec" => $startupSec, "billet_change_sec" => $changeSec,
        "using_estimate" => $usingEstimate, "avg_sec_per_billet" => $avgSecPerBillet,
    ],
], JSON_UNESCAPED_UNICODE);
