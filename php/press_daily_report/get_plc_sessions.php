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
    SELECT s.machine, s.die_name, s.start_time, s.end_time, s.billet_increase,
           t.tempdieup, t.tempdiedown, t.tempstemup, t.tempstemdown
    FROM (
        SELECT machine, die_name, grp,
            DATE_FORMAT(MIN(date_time), '%Y-%m-%d %H:%i') AS start_time,
            DATE_FORMAT(MAX(date_time), '%Y-%m-%d %H:%i') AS end_time,
            (MAX(billet_counter) - MIN(billet_counter)) AS billet_increase,
            MIN(CASE WHEN is_first_inc = 1 THEN date_time END) AS temp_sample_time
        FROM (
            SELECT machine, die_name, date_time, billet_counter, all_pump_on, grp,
                CASE WHEN all_pump_on = 1
                          AND billet_counter = LAG(billet_counter) OVER (PARTITION BY machine, grp ORDER BY date_time, id) + 1
                     THEN 1 ELSE 0 END AS is_first_inc
            FROM (
                SELECT machine, die_name, date_time, billet_counter, all_pump_on, id,
                    SUM(changed) OVER (PARTITION BY machine ORDER BY date_time, id) AS grp
                FROM (
                    SELECT id, machine, die_name, date_time, all_pump_on, billet_counter,
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
        HAVING DATE(MIN(date_time)) = :target_date OR DATE(MAX(date_time)) = :target_date
    ) s
    LEFT JOIN t_plc_web_log t ON t.machine = s.machine AND t.date_time = s.temp_sample_time
    ORDER BY s.machine, s.start_time
");
$prepare->bindValue(":range_start", $rangeStart . " 00:00:00");
$prepare->bindValue(":range_end", $rangeEnd . " 00:00:00");
$prepare->bindValue(":target_date", $date);
$prepare->execute();

echo json_encode($prepare->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
