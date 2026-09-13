<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$date = isset($_GET["date"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET["date"])
    ? $_GET["date"]
    : date("Y-m-d");

// 前後1日分を含めて集計し（日跨ぎのセッションを正しく判定するため）、
// 対象日に開始または終了しているセッションだけを返す。
$rangeStart = date("Y-m-d", strtotime($date . " -1 day"));
$rangeEnd   = date("Y-m-d", strtotime($date . " +2 day"));

$prepare = $pdo->prepare("
    SELECT s2.machine, s2.die_name, s2.start_time,
        -- 終了時刻は「次のセッションの開始時刻」と「このセッションの後で
        -- 最初に all_pump_on が0になった時刻」のうち、早い方を採用する。
        -- （ポンプが止まった時間は、どの金型の持ち時間にも含めない）
        CASE
            WHEN s2.lead_start IS NULL AND s2.next_pump_off IS NULL
                THEN DATE_FORMAT(s2.raw_max_time, '%Y-%m-%d %H:%i')
            WHEN s2.lead_start IS NULL
                THEN DATE_FORMAT(s2.next_pump_off, '%Y-%m-%d %H:%i')
            WHEN s2.next_pump_off IS NULL
                THEN s2.lead_start
            ELSE LEAST(s2.lead_start, DATE_FORMAT(s2.next_pump_off, '%Y-%m-%d %H:%i'))
        END AS end_time,
        s2.billet_increase,
        t.tempdieup, t.tempdiedown, t.tempstemup, t.tempstemdown
    FROM (
        SELECT s.machine, s.die_name, s.start_time, s.raw_min_time, s.raw_max_time,
               s.billet_increase, s.temp_sample_time,
            -- どの瞬間も必ずどれかの金型の時間に属するよう、まずは
            -- 同じ機械の「次のセッション」の開始時刻を終了候補として持っておく
            -- （次のセッションが無い＝集計範囲の最後はNULL）。
            LEAD(s.start_time) OVER (PARTITION BY s.machine ORDER BY s.grp) AS lead_start,
            -- このセッションの最終ログ行より後で、最初に all_pump_on=0 になった時刻。
            (
                SELECT MIN(p.date_time) FROM t_plc_web_log p
                WHERE p.machine = s.machine
                  AND p.date_time > s.raw_max_time
                  AND p.date_time < :range_end
                  AND p.all_pump_on = 0
            ) AS next_pump_off
        FROM (
            SELECT machine, die_name, grp,
                -- 開始時刻は「型替え直後の位置決め中」ではなく、
                -- die_casette_press_position が最初に1になった（=金型がプレス位置にセットされた）時刻とする。
                -- 万一そのセッション内で一度も1にならない場合は従来通り先頭行の時刻にフォールバック。
                DATE_FORMAT(
                    COALESCE(
                        MIN(CASE WHEN die_casette_press_position = 1 THEN date_time END),
                        MIN(date_time)
                    ),
                    '%Y-%m-%d %H:%i'
                ) AS start_time,
                MIN(date_time) AS raw_min_time,
                MAX(date_time) AS raw_max_time,
                (MAX(billet_counter) - MIN(billet_counter)) AS billet_increase,
                MIN(CASE WHEN is_first_inc = 1 THEN date_time END) AS temp_sample_time
            FROM (
                SELECT machine, die_name, date_time, billet_counter, all_pump_on, die_casette_press_position, grp,
                    CASE WHEN all_pump_on = 1
                              AND billet_counter = LAG(billet_counter) OVER (PARTITION BY machine, grp ORDER BY date_time, id) + 1
                         THEN 1 ELSE 0 END AS is_first_inc
                FROM (
                    SELECT machine, die_name, date_time, billet_counter, all_pump_on, die_casette_press_position, id,
                        SUM(changed) OVER (PARTITION BY machine ORDER BY date_time, id) AS grp
                    FROM (
                        SELECT id, machine, die_name, date_time, all_pump_on, billet_counter, die_casette_press_position,
                            CASE
                                WHEN NOT (die_name <=> LAG(die_name) OVER (PARTITION BY machine ORDER BY date_time, id)) THEN 1
                                WHEN TIMESTAMPDIFF(MINUTE, LAG(date_time) OVER (PARTITION BY machine ORDER BY date_time, id), date_time) >= 30
                                     AND all_pump_on = 0 THEN 1
                                ELSE 0
                            END AS changed
                        FROM t_plc_web_log
                        WHERE date_time >= :range_start AND date_time < :range_end
                    ) x
                ) y
            ) z
            GROUP BY machine, grp, die_name
        ) s
    ) s2
    LEFT JOIN t_plc_web_log t ON t.machine = s2.machine AND t.date_time = s2.temp_sample_time
    WHERE DATE(s2.raw_min_time) = :target_date OR DATE(s2.raw_max_time) = :target_date
    ORDER BY s2.machine, s2.start_time
");
$prepare->bindValue(":range_start", $rangeStart . " 00:00:00");
$prepare->bindValue(":range_end", $rangeEnd . " 00:00:00");
$prepare->bindValue(":target_date", $date);
$prepare->execute();

echo json_encode($prepare->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
