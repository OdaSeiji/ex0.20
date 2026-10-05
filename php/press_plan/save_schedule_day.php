<?php
// 押出スケジュール表の、日付・号機ごとの設定（開始の時刻・昼休み）を保存
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$b = json_decode(file_get_contents("php://input"), true) ?? [];
$date = (string)($b["plan_date"] ?? "");
$mc = (int)($b["press_machine"] ?? 0);
$start = (string)($b["start_time"] ?? "");
$hasLunch = ((int)($b["has_lunch"] ?? 0)) === 1 ? 1 : 0;
$after = (int)($b["lunch_after_plan_id"] ?? 0);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $mc < 1 || $mc > 4 || !preg_match('/^\d{2}:\d{2}$/', $start)) {
    echo json_encode(["ok" => false]);
    exit;
}
$stmt = $pdo->prepare("
    INSERT INTO t_press_schedule_day (plan_date, press_machine, start_time, has_lunch, lunch_after_plan_id, updated_at)
    VALUES (:date, :mc, :start, :lunch, :after, NOW())
    ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), has_lunch = VALUES(has_lunch),
                            lunch_after_plan_id = VALUES(lunch_after_plan_id), updated_at = NOW()
");
$stmt->execute([":date" => $date, ":mc" => $mc, ":start" => $start, ":lunch" => $hasLunch, ":after" => $after > 0 ? $after : null]);
echo json_encode(["ok" => true]);
