<?php
// 稼働日報：1日の号機ごとの時間の使い方（手入力の日報だけで集計する）
//   GET date = YYYY-MM-DD
//   持ち時間 ＝ t_press_schedule_day の 終了 − 開始 − 昼休み（45分）。設定が無い号機は null（未入力）
//   日報の時間 ＝ その日・号機の押出（t_press）の 開始〜終了 の合計
//   コードごとの時間 ＝ 押出に入力された時間の記録（t_time_press）。700（押出）は押出の全時間なので数えない
//   押出（実質）＝ 日報の時間 − コードの時間の合計
//   記録なし ＝ 持ち時間 − 開始〜終了 の中の日報の時間（gaps は 開始〜終了 の中のすき間。昼休みを含むことがある）
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../operation_time/common.php";

const OR_MACHINES = [1, 2, 3];
const OR_LUNCH_MIN = 45;
const OR_CODE_EXTRUDE = 700;

$date = (string)($_GET["date"] ?? "");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    http_response_code(400);
    echo json_encode(["error" => "date が不正です"], JSON_UNESCAPED_UNICODE);
    exit;
}

$toMin = fn(?string $t) => $t === null ? null : ((int)substr($t, 0, 2)) * 60 + (int)substr($t, 3, 2);

// [a, b) の区間の集まりから、重なりを除いた合計（分）
function orUnionMinutes(array $iv): int {
    usort($iv, fn($x, $y) => $x[0] <=> $y[0]);
    $sum = 0; $cur = null;
    foreach ($iv as [$a, $b]) {
        if ($b <= $a) continue;
        if ($cur === null || $a > $cur[1]) { if ($cur) $sum += $cur[1] - $cur[0]; $cur = [$a, $b]; }
        else $cur[1] = max($cur[1], $b);
    }
    if ($cur) $sum += $cur[1] - $cur[0];
    return $sum;
}

try {
    // コードの名前（m_code.description はベトナム語。description_ja は文字化けしているので使わない）
    $codeNames = [];
    foreach ($pdo->query("SELECT code, description FROM m_code") as $r) $codeNames[(int)$r["code"]] = $r["description"];

    // 持ち時間の設定（その週に無ければ前の週から引き継ぐ。php/operation_time/common.php）
    $setting = [];
    foreach (OR_MACHINES as $m) $setting[$m] = otResolveDay($pdo, $date, $m);

    $st = $pdo->prepare("SELECT press_machine AS m, COUNT(*) AS n FROM t_press_plan WHERE plan_date = :d GROUP BY m");
    $st->execute([":d" => $date]);
    $plans = [];
    foreach ($st as $r) $plans[(int)$r["m"]] = (int)$r["n"];

    $st = $pdo->prepare("
        SELECT p.id, p.press_machine_no AS m, d.die_number, p.actual_billet_quantities AS billets,
               TIME_FORMAT(p.press_start_at, '%H:%i') AS s, TIME_FORMAT(p.press_finish_at, '%H:%i') AS e
        FROM t_press p LEFT JOIN m_dies d ON d.id = p.dies_id
        WHERE p.press_date_at >= :d AND p.press_date_at < :d2
        ORDER BY p.press_machine_no, p.press_start_at, p.id
    ");
    $st->execute([":d" => $date, ":d2" => date("Y-m-d", strtotime("$date +1 day"))]);
    $presses = $st->fetchAll();

    $codesByPress = [];
    if ($presses) {
        $ids = implode(",", array_map(fn($p) => (int)$p["id"], $presses));
        foreach ($pdo->query("
            SELECT press_id, Code AS code, TIME_FORMAT(time_start, '%H:%i') AS s, TIME_FORMAT(time_end, '%H:%i') AS e, time_note
            FROM t_time_press WHERE press_id IN ({$ids}) ORDER BY time_start, id
        ") as $r) $codesByPress[(int)$r["press_id"]][] = $r;
    }

    $machines = [];
    foreach (OR_MACHINES as $m) {
        $set = $setting[$m];
        $hasPress = (bool)array_filter($presses, fn($p) => (int)$p["m"] === $m);
        // 押出計画も日報も無い日は休み（持ち時間 0）
        $holiday = ($plans[$m] ?? 0) === 0 && !$hasPress;
        if ($holiday) $set = null;
        $winS = $set && $set["start"] ? $toMin($set["start"]) : null;
        $winE = $set && $set["end"] ? $toMin($set["end"]) : null;
        $available = $holiday ? 0 : otAvailableMinutes($set, OR_LUNCH_MIN);

        $list = [];
        $intervals = [];
        $codeTotals = [];
        foreach ($presses as $p) {
            if ((int)$p["m"] !== $m) continue;
            $ps = $toMin($p["s"]); $pe = $toMin($p["e"]);
            $dur = ($ps !== null && $pe !== null && $pe > $ps) ? $pe - $ps : 0;
            if ($dur > 0) $intervals[] = [$ps, $pe];
            $codes = [];
            foreach ($codesByPress[(int)$p["id"]] ?? [] as $c) {
                $code = (int)$c["code"];
                if ($code === OR_CODE_EXTRUDE) continue;
                $cm = max(0, $toMin($c["e"]) - $toMin($c["s"]));
                $codes[] = ["code" => $code, "start" => $c["s"], "end" => $c["e"], "minutes" => $cm, "note" => $c["time_note"]];
                $codeTotals[$code] = ($codeTotals[$code] ?? 0) + $cm;
            }
            $list[] = ["id" => (int)$p["id"], "die_number" => $p["die_number"], "billets" => $p["billets"] === null ? null : (int)$p["billets"],
                       "start" => $p["s"], "end" => $p["e"], "minutes" => $dur, "codes" => $codes];
        }
        $reported = orUnionMinutes($intervals);
        $codeSum = array_sum($codeTotals);
        ksort($codeTotals);

        // 開始〜終了 の中で日報に入っていない時間と、外にはみ出した日報の時間
        $gaps = []; $noRecord = null; $outside = null;
        if ($winS !== null && $winE !== null) {
            $inside = orUnionMinutes(array_map(fn($iv) => [max($iv[0], $winS), min($iv[1], $winE)], $intervals));
            // 昼休みは日報に入れないので、持ち時間（昼休みを引いた時間）から日報の分を引く
            $noRecord = max(0, $available - $inside);
            $outside = $reported - $inside;
            usort($intervals, fn($x, $y) => $x[0] <=> $y[0]);
            $cur = $winS;
            foreach ($intervals as [$a, $b]) {
                if ($a > $cur && $cur < $winE) $gaps[] = [$cur, min($a, $winE)];
                $cur = max($cur, $b);
            }
            if ($cur < $winE) $gaps[] = [$cur, $winE];
            $gaps = array_map(fn($g) => ["start" => sprintf("%02d:%02d", intdiv($g[0], 60), $g[0] % 60), "end" => sprintf("%02d:%02d", intdiv($g[1], 60), $g[1] % 60), "minutes" => $g[1] - $g[0]], $gaps);
        }

        $machines[] = [
            "machine"    => $m,
            "setting"    => $set ? ["start" => $set["start"], "end" => $set["end"], "has_lunch" => (int)$set["has_lunch"], "inherited" => $set["inherited"]] : null,
            "holiday"    => $holiday,
            "available"  => $available,
            "plans"      => $plans[$m] ?? 0,
            "reported"   => $reported,
            "extruding"  => max(0, $reported - $codeSum),
            "codes"      => array_map(fn($c, $v) => ["code" => $c, "minutes" => $v], array_keys($codeTotals), array_values($codeTotals)),
            "no_record"  => $noRecord,
            "outside"    => $outside,
            "gaps"       => $gaps,
            "dies"       => count($list),
            "presses"    => $list,
        ];
    }

    echo json_encode(["date" => $date, "machines" => $machines, "code_names" => $codeNames, "lunch_minutes" => OR_LUNCH_MIN], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
