<?php
// 持ち時間（シフト）の設定を読む共通処理：t_press_schedule_day（日付・号機ごとの 開始・終了・昼休み）
//   入力を減らすため、週の単位で前の週を引き継ぐ（ユーザー決定 2026-10-25）：
//     ある号機の、ある週（月〜日）に1行も無いときは、その前の週の同じ曜日の値を使う（さかのぼって最大 OT_LOOKBACK_WEEKS 週）。
//     その週に1行でもあれば、その週の行だけを使う（行の無い日は設定なし）
//   引き継いだ値は DB には書かない（画面で週を直したときに、その週の分をまとめて書く）

const OT_LOOKBACK_WEEKS = 12;

function otMonday(string $date): string {
    $ts = strtotime($date);
    return date("Y-m-d", strtotime("-" . (((int)date("N", $ts)) - 1) . " day", $ts));
}

// その号機の、ある週（$monday から7日）の設定。戻り値：[0〜6（月〜日）=> ["start", "end", "has_lunch", "inherited", "from"] | null]
function otResolveWeek(PDO $pdo, string $monday, int $machine): array {
    $st = $pdo->prepare("
        SELECT plan_date, TIME_FORMAT(start_time, '%H:%i') AS s, TIME_FORMAT(end_time, '%H:%i') AS e, has_lunch
        FROM t_press_schedule_day
        WHERE press_machine = :m AND plan_date BETWEEN :f AND :t
        ORDER BY plan_date DESC
    ");
    $st->execute([":m" => $machine, ":f" => date("Y-m-d", strtotime("$monday -" . (7 * OT_LOOKBACK_WEEKS) . " day")), ":t" => date("Y-m-d", strtotime("$monday +6 day"))]);
    $rows = $st->fetchAll();
    $week = array_fill(0, 7, null);
    if (!$rows) return $week;
    // $monday の週に行があれば、その週。無ければ、終了の時刻まで入っている行のある、いちばん新しい週
    //（スケジュール表で開始だけを変えた行は、持ち時間の引き継ぎ元にしない）
    if (otMonday($rows[0]["plan_date"]) === $monday) {
        $srcMonday = $monday;
    } else {
        $complete = array_values(array_filter($rows, fn($r) => $r["s"] !== null && $r["e"] !== null));
        if (!$complete) return $week;
        $srcMonday = otMonday($complete[0]["plan_date"]);
    }
    $inherited = $srcMonday !== $monday;
    foreach ($rows as $r) {
        if (otMonday($r["plan_date"]) !== $srcMonday) continue;
        if ($inherited && ($r["s"] === null || $r["e"] === null)) continue;
        $wd = (int)date("N", strtotime($r["plan_date"])) - 1;
        $week[$wd] = ["start" => $r["s"], "end" => $r["e"], "has_lunch" => (int)$r["has_lunch"], "inherited" => $inherited, "from" => $inherited ? $r["plan_date"] : null];
    }
    return $week;
}

// 1日・1号機の設定（引き継ぎを含む）。無ければ null
function otResolveDay(PDO $pdo, string $date, int $machine): ?array {
    $week = otResolveWeek($pdo, otMonday($date), $machine);
    return $week[(int)date("N", strtotime($date)) - 1];
}

// 持ち時間（分）＝ 終了 − 開始 − 昼休み。終了か開始が無ければ null
function otAvailableMinutes(?array $s, int $lunchMin = 45): ?int {
    if (!$s || !$s["start"] || !$s["end"]) return null;
    $m = fn($t) => ((int)substr($t, 0, 2)) * 60 + (int)substr($t, 3, 2);
    return $m($s["end"]) - $m($s["start"]) - ($s["has_lunch"] ? $lunchMin : 0);
}
