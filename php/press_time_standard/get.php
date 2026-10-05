<?php
// 押出時間の標準（号機ごと）と、参考の実績（直近1年）
// - 推定：押出全体の時間 −（本数 × 長さ ÷ ラム速度）を「自動運転までの時間 ＋ 本数 × ビレットの交換」で当てはめた値
// - 1本あたりの平均：押出指示書の無い型のスケジュールに使う
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$rows = $pdo->query("
    SELECT t.press_machine, t.startup_sec, t.billet_change_sec,
           DATE_FORMAT(t.updated_at, '%Y-%m-%d %H:%i') AS updated_at, t.updated_staff_id, s.staff_name AS updated_staff_name
    FROM m_press_time_standard t
    LEFT JOIN m_staff s ON s.id = t.updated_staff_id
    ORDER BY t.press_machine
")->fetchAll();

$est = [];
foreach ($pdo->query("
    WITH x AS (
        SELECT p.press_machine_no AS mc, p.actual_billet_quantities AS q,
               TIME_TO_SEC(TIMEDIFF(p.press_finish_at, p.press_start_at)) - p.actual_billet_quantities * p.billet_length / d.ram_speed AS over_sec
        FROM t_press p
        JOIN t_press_directive d ON d.id = p.press_directive_id
        WHERE p.press_date_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
          AND d.ram_speed > 0 AND p.billet_length > 0 AND p.actual_billet_quantities >= 1
          AND p.press_finish_at > p.press_start_at
    )
    SELECT mc, COUNT(*) AS n,
           (SUM(q * over_sec) - SUM(q) * SUM(over_sec) / COUNT(*)) / NULLIF(SUM(q * q) - SUM(q) * SUM(q) / COUNT(*), 0) AS change_sec,
           SUM(over_sec) / COUNT(*) AS avg_over, SUM(q) / COUNT(*) AS avg_q
    FROM x WHERE over_sec BETWEEN -600 AND 7200
    GROUP BY mc
") as $r) {
    $change = $r["change_sec"] === null ? null : (float)$r["change_sec"];
    $est[(int)$r["mc"]] = [
        "billet_change_sec" => $change === null ? null : (int)round($change),
        "startup_sec"       => $change === null ? null : (int)round($r["avg_over"] - $change * $r["avg_q"]),
        "n"                 => (int)$r["n"],
    ];
}
$avg = [];
foreach ($pdo->query("
    SELECT press_machine_no AS mc,
           SUM(TIME_TO_SEC(TIMEDIFF(press_finish_at, press_start_at))) / SUM(actual_billet_quantities) AS sec_per_billet
    FROM t_press
    WHERE press_date_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND press_finish_at > press_start_at AND actual_billet_quantities > 0
    GROUP BY press_machine_no
") as $r) {
    $avg[(int)$r["mc"]] = (int)round($r["sec_per_billet"]);
}
foreach ($rows as &$r) {
    $mc = (int)$r["press_machine"];
    $r["estimate"] = $est[$mc] ?? null;
    $r["avg_sec_per_billet"] = $avg[$mc] ?? null;
}
unset($r);

$staff = $pdo->query("SELECT id, staff_name FROM m_staff WHERE leave_at IS NULL ORDER BY staff_name")->fetchAll();
echo json_encode(["rows" => $rows, "staff" => $staff], JSON_UNESCAPED_UNICODE);
