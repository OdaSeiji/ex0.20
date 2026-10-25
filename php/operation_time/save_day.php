<?php
// 持ち時間の入力：1日・1号機の 開始・終了・昼休み を保存する（押出スケジュール表と同じ t_press_schedule_day）
//   POST JSON: { date, machine, start: "HH:MM", end: "HH:MM", has_lunch: 0 | 1 }  → 保存
//              { date, machine, clear: true }                                   → その日の設定を消す（休み・まだ決めていない）
//   入力を減らすための決まり（ユーザー決定 2026-10-25）：
//     - その週が前の週からの引き継ぎ（行が無い）なら、先に引き継いだ値をその週に書いてから直す
//     - 月曜を直したら、その週の火〜日のうち「月曜と同じ値だった日」と「設定の無い日」も同じ値にする（別の値にしていた日はそのまま）
//   昼休みを入れる位置（lunch_after_plan_id）はスケジュール表で決めるので、ここでは変えない
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/common.php";

function otFail(string $msg): void {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$b = json_decode(file_get_contents("php://input"), true) ?? [];
$date = (string)($b["date"] ?? "");
$mc = (int)($b["machine"] ?? 0);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) otFail("日付が不正です");
if ($mc < 1 || $mc > 4) otFail("号機が不正です");
$clear = !empty($b["clear"]);
if (!$clear) {
    $new = ["start" => (string)($b["start"] ?? ""), "end" => (string)($b["end"] ?? ""), "has_lunch" => ((int)($b["has_lunch"] ?? 0)) === 1 ? 1 : 0];
    if (!preg_match('/^\d{2}:\d{2}$/', $new["start"]) || !preg_match('/^\d{2}:\d{2}$/', $new["end"])) otFail("開始と終了を入れてください");
    if ($new["end"] <= $new["start"]) otFail("終了は開始より後にしてください");
}

$upsert = $pdo->prepare("
    INSERT INTO t_press_schedule_day (plan_date, press_machine, start_time, end_time, has_lunch, updated_at)
    VALUES (:d, :m, :s, :e, :l, NOW())
    ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), has_lunch = VALUES(has_lunch), updated_at = NOW()
");
$put = fn(string $d, array $s) => $upsert->execute([":d" => $d, ":m" => $mc, ":s" => $s["start"], ":e" => $s["end"], ":l" => $s["has_lunch"]]);
$same = fn(?array $a, ?array $b) => $a && $b && $a["start"] === $b["start"] && $a["end"] === $b["end"] && (int)$a["has_lunch"] === (int)$b["has_lunch"];

try {
    $pdo->beginTransaction();
    $monday = otMonday($date);
    $week = otResolveWeek($pdo, $monday, $mc);
    $dayOf = fn(int $i) => date("Y-m-d", strtotime("$monday +$i day"));

    // 引き継ぎの週なら、まずその週に書く
    if (array_filter($week, fn($s) => $s && $s["inherited"])) {
        foreach ($week as $i => $s) if ($s && $s["start"] && $s["end"]) $put($dayOf($i), $s);
    }

    $wd = (int)date("N", strtotime($date)) - 1;
    if ($clear) {
        $pdo->prepare("DELETE FROM t_press_schedule_day WHERE plan_date = :d AND press_machine = :m")->execute([":d" => $date, ":m" => $mc]);
    } else {
        if ($wd === 0) {
            $old = $week[0];
            for ($i = 1; $i < 7; $i++) {
                if ($week[$i] === null || !$week[$i]["start"] || !$week[$i]["end"] || $same($week[$i], $old)) $put($dayOf($i), $new);
            }
        }
        $put($date, $new);
    }
    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
