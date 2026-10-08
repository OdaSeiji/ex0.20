<?php
// 押出日報の入力確認：PLC で extruding が 0（押出していない）だった時間のうち、3分以上のものを返す
// ログは変化のあった時だけ記録されるので、終わりは「次のログの時刻」とする
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$machine = (int)($_GET["machine"] ?? 0);
$start   = (string)($_GET["start"] ?? "");
$days    = max(1, min(31, (int)($_GET["days"] ?? 7)));
$minSec  = max(0, (int)($_GET["min_sec"] ?? 180));
if ($machine < 1 || $machine > 4 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
    echo json_encode([]);
    exit;
}
$from = $start . " 00:00:00";
$to   = date("Y-m-d", strtotime("{$start} +{$days} day")) . " 00:00:00";
// 前の日から続いているものもつかむため、1日前から読む
$readFrom = date("Y-m-d", strtotime("{$start} -1 day")) . " 00:00:00";

$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(MIN(date_time), '%Y-%m-%d %H:%i:%s') AS off_start,
           DATE_FORMAT(COALESCE(MAX(next_time), MAX(date_time)), '%Y-%m-%d %H:%i:%s') AS off_end
    FROM (
        SELECT date_time, extruding,
               LEAD(date_time) OVER (ORDER BY date_time, id) AS next_time,
               SUM(chg) OVER (ORDER BY date_time, id) AS grp
        FROM (
            SELECT id, date_time, extruding,
                   CASE WHEN extruding <=> LAG(extruding) OVER (ORDER BY date_time, id) THEN 0 ELSE 1 END AS chg
            FROM t_plc_web_log
            WHERE machine = :mc AND date_time >= :rf AND date_time < :to1
        ) a
    ) b
    WHERE extruding = 0
    GROUP BY grp
    HAVING TIMESTAMPDIFF(SECOND, MIN(date_time), COALESCE(MAX(next_time), MAX(date_time))) >= :min_sec
       AND COALESCE(MAX(next_time), MAX(date_time)) > :from1
    ORDER BY off_start
");
$stmt->execute([":mc" => $machine, ":rf" => $readFrom, ":to1" => $to, ":min_sec" => $minSec, ":from1" => $from]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
